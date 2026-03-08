<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreTrackRequest;
use App\Http\Requests\UpdateTrackRequest;
use App\Jobs\GenerateTrackPreview;
use App\Models\Album;
use App\Models\Artist;
use App\Models\Genre;
use App\Models\Track;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use FFMpeg\FFProbe;
use Symfony\Component\Process\Process;
use Symfony\Component\HttpFoundation\StreamedResponse;

class TrackController extends Controller
{
    public function analyzeUpload(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'track_audio' => 'required|file|max:51200',
        ]);

        /** @var UploadedFile $file */
        $file = $validated['track_audio'];
        $ext = strtolower((string) $file->getClientOriginalExtension());
        $allowed = ['mp3', 'wav', 'ogg', 'm4a', 'flac'];
        if ($ext === '' || ! in_array($ext, $allowed, true)) {
            return response()->json([
                'message' => 'The track audio field must be a file of type: mp3, wav, ogg, m4a, flac.',
                'data' => null,
            ], 422);
        }
        $path = $file->getRealPath();
        if (! is_string($path) || $path === '') {
            return response()->json([
                'message' => 'Unable to read uploaded file',
                'data' => null,
            ], 422);
        }

        $ffprobe = FFProbe::create($this->ffmpegConfig());
        $format = $ffprobe->format($path);
        $tags = (array) ($format->get('tags') ?? []);
        $streamTags = [];
        $streams = $ffprobe->streams($path);
        foreach ($streams as $stream) {
            $streamTagSet = (array) ($stream->get('tags') ?? []);
            if ($streamTagSet !== []) {
                $streamTags[] = $streamTagSet;
            }
        }
        $duration = (int) round((float) ($format->get('duration') ?? 0));

        $suggestedTitle = trim((string) $this->firstTagValue(['title'], $tags, ...$streamTags));
        $suggestedGenre = trim((string) $this->firstTagValue(['genre'], $tags, ...$streamTags));
        $suggestedDate = trim((string) $this->firstTagValue(['date', 'year'], $tags, ...$streamTags));
        $artistRaw = trim((string) $this->firstTagValue(['artist', 'album_artist'], $tags, ...$streamTags));
        $suggestedBpm = $this->extractBpmFromTags($tags, ...$streamTags);
        if ($suggestedBpm === null) {
            $suggestedBpm = $this->detectBpmFromAudio($path);
        }
        $coverDataUrl = $this->extractEmbeddedCoverDataUrl($path);

        [$artists, $cleanTitle] = $this->extractArtistsFromRaw($artistRaw, $suggestedTitle);
        $suggestedTitle = $cleanTitle;

        if ($suggestedTitle === '' || empty($artists)) {
            [$fromNameArtists, $fromNameTitle] = $this->parseFileNameArtistTitle($file->getClientOriginalName());
            if ($suggestedTitle === '' && $fromNameTitle !== '') {
                $suggestedTitle = $fromNameTitle;
            }
            if (empty($artists) && ! empty($fromNameArtists)) {
                $artists = $fromNameArtists;
            }
        }

        $releaseDate = null;
        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $suggestedDate)) {
            $releaseDate = $suggestedDate;
        } elseif (preg_match('/^\d{4}$/', $suggestedDate)) {
            $releaseDate = "{$suggestedDate}-01-01";
        }

        return response()->json([
            'message' => 'ok',
            'data' => [
                'track_title' => $suggestedTitle,
                'genre_name' => $suggestedGenre,
                'artist_names' => $artists,
                'release_date' => $releaseDate,
                'track_length_sec' => $duration > 0 ? $duration : null,
                'bpm_value' => $suggestedBpm,
                'cover_data_url' => $coverDataUrl,
            ],
        ]);
    }

    public function index(Request $request): JsonResponse
    {
        $isAdmin = $this->isAdmin($request);
        $tracks = Track::with(['genre', 'genres', 'artists', 'album'])->get()->map(
            fn (Track $track) => $this->transformTrackForViewer($track, $isAdmin)
        );

        return response()->json([
            'message' => 'ok',
            'data' => $tracks,
        ]);
    }

    public function show(Request $request, int $id): JsonResponse
    {
        $track = Track::with(['genre', 'genres', 'artists', 'album'])->find($id);

        if (! $track) {
            return response()->json([
                'message' => "Track not found: {$id}",
                'data' => null,
            ], 404);
        }

        return response()->json([
            'message' => 'ok',
            'data' => $this->transformTrackForViewer($track, $this->isAdmin($request)),
        ]);
    }

    public function store(StoreTrackRequest $request): JsonResponse
    {
        $data = $request->validated();

        $genreIds = $this->resolveGenreIds($data);
        $genreId = $genreIds[0] ?? null;
        $albumId = $this->resolveAlbumId($data);

        $coverPath = $data['track_cover'] ?? null;
        if ($request->hasFile('track_cover_file')) {
            $coverPath = $request->file('track_cover_file')->store('track-covers', 'public');
        }

        $trackPath = $data['track_path'] ?? null;
        if ($request->hasFile('track_audio')) {
            $trackPath = $request->file('track_audio')->store('tracks', 'public');
        }

        $previewStart = (int) ($data['preview_start_at'] ?? 0);
        $previewEnd = (int) ($data['preview_end_at'] ?? 30);
        $releaseDate = $this->normalizeNullableDate($data['release_date'] ?? null);

        $track = Track::query()->create([
            'genre_id' => $genreId,
            'album_id' => $albumId,
            'track_title' => $data['track_title'],
            'bpm_value' => $data['bpm_value'] ?? null,
            'release_date' => $releaseDate,
            'track_length_sec' => $data['track_length_sec'] ?? null,
            'track_price_eur' => isset($data['track_price_eur']) ? round((float) $data['track_price_eur'], 2) : 1.99,
            'track_cover' => $coverPath,
            'track_path' => $trackPath,
            'preview_start_at' => $previewStart,
            'preview_end_at' => $previewEnd,
        ]);

        $artistIds = $data['artist_ids'] ?? [];
        foreach (($data['artist_names'] ?? []) as $artistName) {
            $normalizedArtistName = trim((string) $artistName);
            if ($normalizedArtistName === '') {
                continue;
            }

            $artist = Artist::query()
                ->whereRaw('LOWER(artist_name) = ?', [mb_strtolower($normalizedArtistName)])
                ->first();

            if (! $artist) {
                $artist = Artist::query()->create(['artist_name' => $normalizedArtistName]);
            }

            $artistIds[] = $artist->artist_id;
        }
        $artistIds = array_values(array_unique($artistIds));

        if (! empty($artistIds)) {
            $track->artists()->sync($artistIds);
        }
        if (! empty($genreIds)) {
            $track->genres()->sync($genreIds);
        }

        if (! empty($trackPath) && ($previewEnd - $previewStart) > 0) {
            $oldPreviewPath = (string) ($track->preview_path ?? '');
            (new GenerateTrackPreview($track))->handle();
            $track->refresh();

            $newPreviewPath = (string) ($track->preview_path ?? '');
            $generated = $newPreviewPath !== '' && Storage::disk('public')->exists($newPreviewPath);
            if (! $generated || $newPreviewPath === $oldPreviewPath) {
                return response()->json([
                    'message' => 'Preview generation failed. Check ffmpeg/ffprobe configuration.',
                    'data' => null,
                ], 422);
            }
        }

        Track::syncCsvExports();

        return response()->json([
            'message' => 'Track created successfully',
            'data' => $track->load(['genre', 'genres', 'artists', 'album']),
        ], 201);
    }

    public function update(UpdateTrackRequest $request, int $id): JsonResponse
    {
        $track = Track::query()->find($id);
        if (! $track) {
            return response()->json([
                'message' => "Track not found: {$id}",
                'data' => null,
            ], 404);
        }

        $data = $request->validated();

        $genreIds = $this->resolveGenreIds($data);
        $genreId = $genreIds[0] ?? null;
        $albumId = $this->resolveAlbumId($data);

        $oldCoverPath = (string) ($track->track_cover ?? '');
        $oldTrackPath = (string) ($track->track_path ?? '');
        $oldPreviewPath = (string) ($track->preview_path ?? '');

        $coverPath = $data['track_cover'] ?? $track->track_cover;
        $coverReplaced = false;
        if ($request->hasFile('track_cover_file')) {
            $coverPath = $request->file('track_cover_file')->store('track-covers', 'public');
            $coverReplaced = true;
        }

        $trackPath = $data['track_path'] ?? $track->track_path;
        $audioReplaced = false;
        if ($request->hasFile('track_audio')) {
            $trackPath = $request->file('track_audio')->store('tracks', 'public');
            $audioReplaced = true;
        }

        $previewStart = (int) ($data['preview_start_at'] ?? 0);
        $previewEnd = (int) ($data['preview_end_at'] ?? 30);
        $releaseDate = $this->normalizeNullableDate($data['release_date'] ?? null);

        $track->fill([
            'genre_id' => $genreId,
            'album_id' => $albumId,
            'track_title' => $data['track_title'],
            'bpm_value' => $data['bpm_value'] ?? null,
            'release_date' => $releaseDate,
            'track_length_sec' => $data['track_length_sec'] ?? null,
            'track_price_eur' => isset($data['track_price_eur']) ? round((float) $data['track_price_eur'], 2) : ($track->track_price_eur ?? 1.99),
            'track_cover' => $coverPath,
            'track_path' => $trackPath,
            'preview_start_at' => $previewStart,
            'preview_end_at' => $previewEnd,
        ]);
        $track->save();

        $artistIds = $data['artist_ids'] ?? [];
        foreach (($data['artist_names'] ?? []) as $artistName) {
            $normalizedArtistName = trim((string) $artistName);
            if ($normalizedArtistName === '') {
                continue;
            }

            $artist = Artist::query()
                ->whereRaw('LOWER(artist_name) = ?', [mb_strtolower($normalizedArtistName)])
                ->first();

            if (! $artist) {
                $artist = Artist::query()->create(['artist_name' => $normalizedArtistName]);
            }

            $artistIds[] = $artist->artist_id;
        }

        $artistIds = array_values(array_unique($artistIds));
        $track->artists()->sync($artistIds);
        if (! empty($genreIds)) {
            $track->genres()->sync($genreIds);
        }

        if (! empty($trackPath) && ($previewEnd - $previewStart) > 0) {
            (new GenerateTrackPreview($track))->handle();
            $track->refresh();

            $newPreviewPath = (string) ($track->preview_path ?? '');
            $generated = $newPreviewPath !== '' && Storage::disk('public')->exists($newPreviewPath);
            if (! $generated || $newPreviewPath === $oldPreviewPath) {
                return response()->json([
                    'message' => 'Preview generation failed. Check ffmpeg/ffprobe configuration.',
                    'data' => null,
                ], 422);
            }
        }

        if ($coverReplaced && $oldCoverPath !== '' && $oldCoverPath !== (string) $coverPath && Storage::disk('public')->exists($oldCoverPath)) {
            Storage::disk('public')->delete($oldCoverPath);
        }

        if ($audioReplaced && $oldTrackPath !== '' && $oldTrackPath !== (string) $trackPath && Storage::disk('public')->exists($oldTrackPath)) {
            Storage::disk('public')->delete($oldTrackPath);
        }

        if ($audioReplaced && $oldPreviewPath !== '' && $oldPreviewPath !== (string) ($track->preview_path ?? '') && Storage::disk('public')->exists($oldPreviewPath)) {
            Storage::disk('public')->delete($oldPreviewPath);
        }

        Track::syncCsvExports();

        return response()->json([
            'message' => 'Track updated successfully',
            'data' => $track->load(['genre', 'genres', 'artists', 'album']),
        ]);
    }

    public function preview(Request $request, int $id): JsonResponse|StreamedResponse
    {
        $track = Track::find($id);
        if (! $track) {
            return response()->json(['message' => 'Track not found', 'data' => null], 404);
        }

        $relativePath = ltrim((string) $track->preview_path, '/');
        if ($relativePath === '') {
            return response()->json(['message' => 'Preview file not available', 'data' => null], 404);
        }

        $fullPath = storage_path('app/public/' . $relativePath);

        if (! is_file($fullPath)) {
            return response()->json(['message' => 'Preview file not found', 'data' => null], 404);
        }

        $fileSize = filesize($fullPath);
        if (! $fileSize || $fileSize <= 0) {
            return response()->json(['message' => 'Invalid preview file size', 'data' => null], 500);
        }

        $start = 0;
        $end = $fileSize - 1;
        $status = 200;

        $rangeHeader = $request->header('Range');
        if (is_string($rangeHeader) && preg_match('/bytes=(\d*)-(\d*)/i', $rangeHeader, $matches)) {
            $status = 206;
            if ($matches[1] !== '') {
                $start = (int) $matches[1];
            }
            if ($matches[2] !== '') {
                $end = (int) $matches[2];
            }
            $start = max(0, min($start, $fileSize - 1));
            $end = max($start, min($end, $fileSize - 1));
        }

        $length = ($end - $start) + 1;

        $headers = [
            'Content-Type' => 'audio/mpeg',
            'Content-Length' => (string) $length,
            'Accept-Ranges' => 'bytes',
            'Cache-Control' => 'no-store, no-cache, must-revalidate, max-age=0',
        ];
        if ($status === 206) {
            $headers['Content-Range'] = "bytes {$start}-{$end}/{$fileSize}";
        }

        return response()->stream(function () use ($fullPath, $start, $length): void {
            $handle = fopen($fullPath, 'rb');
            if (! $handle) {
                return;
            }

            fseek($handle, $start);
            $remaining = $length;

            while (! feof($handle) && $remaining > 0) {
                $chunk = min(8192, $remaining);
                $buffer = fread($handle, $chunk);
                if ($buffer === false) {
                    break;
                }
                echo $buffer;
                $remaining -= strlen($buffer);
                flush();
            }

            fclose($handle);
        }, $status, $headers);
    }

    public function source(Request $request, int $id): JsonResponse|StreamedResponse
    {
        $track = Track::find($id);
        if (! $track) {
            return response()->json(['message' => 'Track not found', 'data' => null], 404);
        }

        $sourcePath = $this->resolveTrackSourcePath((string) ($track->track_path ?? ''));
        if (! $sourcePath || ! is_file($sourcePath)) {
            return response()->json(['message' => 'Track source file not found', 'data' => null], 404);
        }

        $extension = strtolower((string) pathinfo((string) ($track->track_path ?? ''), PATHINFO_EXTENSION));
        if ($extension === '') {
            $extension = strtolower((string) pathinfo($sourcePath, PATHINFO_EXTENSION));
        }
        if ($extension === '') {
            $extension = 'mp3';
        }

        $downloadName = $this->safeDownloadName((string) ($track->track_title ?? 'track'), $extension);

        return $this->streamAudioFile($request, $sourcePath, $downloadName);
    }

    public function regeneratePreview(Request $request, int $id): JsonResponse
    {
        $track = Track::query()->find($id);
        if (! $track) {
            return response()->json([
                'message' => "Track not found: {$id}",
                'data' => null,
            ], 404);
        }

        $validated = $request->validate([
            'preview_start_at' => 'required|integer|min:0',
            'preview_end_at' => 'required|integer|gt:preview_start_at',
        ]);

        $previewStart = (int) $validated['preview_start_at'];
        $previewEnd = (int) $validated['preview_end_at'];

        if (($previewEnd - $previewStart) > 30) {
            return response()->json([
                'message' => 'Preview duration can be at most 30 seconds.',
                'data' => null,
            ], 422);
        }

        if (! $track->track_path) {
            return response()->json([
                'message' => 'Track source file is missing.',
                'data' => null,
            ], 422);
        }

        $oldPreviewPath = (string) ($track->preview_path ?? '');
        $track->preview_start_at = $previewStart;
        $track->preview_end_at = $previewEnd;
        $track->save();

        (new GenerateTrackPreview($track))->handle();
        $track->refresh();

        $newPreviewPath = (string) ($track->preview_path ?? '');
        $generated = $newPreviewPath !== '' && Storage::disk('public')->exists($newPreviewPath);
        if (! $generated || $newPreviewPath === $oldPreviewPath) {
            return response()->json([
                'message' => 'Preview regeneration failed. Check ffmpeg/ffprobe configuration.',
                'data' => null,
            ], 422);
        }

        Track::syncCsvExports();

        return response()->json([
            'message' => 'Preview regenerated successfully',
            'data' => $track->load(['genre', 'genres', 'artists', 'album']),
        ]);
    }

    private function streamAudioFile(Request $request, string $fullPath, ?string $downloadName = null): JsonResponse|StreamedResponse
    {
        $fileSize = filesize($fullPath);
        if (! $fileSize || $fileSize <= 0) {
            return response()->json(['message' => 'Invalid audio file size', 'data' => null], 500);
        }

        $start = 0;
        $end = $fileSize - 1;
        $status = 200;

        $rangeHeader = $request->header('Range');
        if (is_string($rangeHeader) && preg_match('/bytes=(\d*)-(\d*)/i', $rangeHeader, $matches)) {
            $status = 206;
            if ($matches[1] !== '') {
                $start = (int) $matches[1];
            }
            if ($matches[2] !== '') {
                $end = (int) $matches[2];
            }
            $start = max(0, min($start, $fileSize - 1));
            $end = max($start, min($end, $fileSize - 1));
        }

        $length = ($end - $start) + 1;

        $headers = [
            'Content-Type' => 'audio/mpeg',
            'Content-Length' => (string) $length,
            'Accept-Ranges' => 'bytes',
            'Cache-Control' => 'no-store, no-cache, must-revalidate, max-age=0',
        ];
        if ($status === 206) {
            $headers['Content-Range'] = "bytes {$start}-{$end}/{$fileSize}";
        }

        if (is_string($downloadName) && $downloadName !== '') {
            $headers['Content-Disposition'] = 'attachment; filename="' . str_replace('"', '', $downloadName) . '"';
        }

        return response()->stream(function () use ($fullPath, $start, $length): void {
            $handle = fopen($fullPath, 'rb');
            if (! $handle) {
                return;
            }

            fseek($handle, $start);
            $remaining = $length;

            while (! feof($handle) && $remaining > 0) {
                $chunk = min(8192, $remaining);
                $buffer = fread($handle, $chunk);
                if ($buffer === false) {
                    break;
                }
                echo $buffer;
                $remaining -= strlen($buffer);
                flush();
            }

            fclose($handle);
        }, $status, $headers);
    }

    private function safeDownloadName(string $title, string $extension): string
    {
        $base = trim($title);
        if ($base === '') {
            $base = 'track';
        }

        $base = preg_replace('/[\\\\\\/\\:\\*\\?\\"\\<\\>\\|]+/', ' ', $base) ?? $base;
        $base = preg_replace('/\\s+/', ' ', $base) ?? $base;
        $base = trim($base, " .\t\n\r\0\x0B");
        if ($base === '') {
            $base = 'track';
        }

        $ext = strtolower(trim($extension));
        $ext = preg_replace('/[^a-z0-9]+/', '', $ext) ?? '';
        if ($ext === '') {
            $ext = 'mp3';
        }

        return $base . '.' . $ext;
    }

    private function resolveTrackSourcePath(string $trackPath): ?string
    {
        $variants = array_values(array_unique(array_filter([
            $trackPath,
            html_entity_decode($trackPath, ENT_QUOTES | ENT_HTML5, 'UTF-8'),
        ], static fn ($v) => is_string($v) && trim($v) !== '')));

        foreach ($variants as $variant) {
            if (is_file($variant)) {
                return $variant;
            }

            $normalized = ltrim(str_replace('\\', '/', $variant), '/');
            $publicRelative = preg_replace('#^storage/#', '', $normalized) ?? $normalized;

            $candidates = [
                Storage::disk('public')->path($publicRelative),
                public_path($normalized),
                storage_path('app/public/' . $publicRelative),
                storage_path('app/' . $normalized),
            ];

            foreach ($candidates as $candidate) {
                if (is_file($candidate)) {
                    return $candidate;
                }
            }
        }

        return null;
    }

    private function isAdmin(Request $request): bool
    {
        $user = $request->user('sanctum') ?? $request->user();

        return (bool) ($user?->isAdmin() ?? false);
    }

    private function transformTrackForViewer(Track $track, bool $isAdmin): array
    {
        $payload = $track->toArray();
        $payload['track_title'] = html_entity_decode((string) ($payload['track_title'] ?? ''), ENT_QUOTES | ENT_HTML5, 'UTF-8');

        if (isset($payload['artists']) && is_array($payload['artists'])) {
            $payload['artists'] = array_map(function (array $artist): array {
                $artist['artist_name'] = html_entity_decode((string) ($artist['artist_name'] ?? ''), ENT_QUOTES | ENT_HTML5, 'UTF-8');

                return $artist;
            }, $payload['artists']);
        }

        if (isset($payload['genres']) && is_array($payload['genres'])) {
            $payload['genres'] = array_map(function (array $genre): array {
                $genre['genre_name'] = html_entity_decode((string) ($genre['genre_name'] ?? ''), ENT_QUOTES | ENT_HTML5, 'UTF-8');
                return $genre;
            }, $payload['genres']);
        }

        if (! $isAdmin) {
            $payload['track_path'] = null;
        }

        return $payload;
    }

    private function resolveGenreIds(array $data): array
    {
        $genreIds = [];

        if (! empty($data['genre_id'])) {
            $genreIds[] = (int) $data['genre_id'];
        }

        foreach (($data['genre_ids'] ?? []) as $genreId) {
            $genreIds[] = (int) $genreId;
        }

        if (! empty($data['genre_name'])) {
            $resolved = $this->resolveGenreNameToId((string) $data['genre_name']);
            if ($resolved !== null) {
                $genreIds[] = $resolved;
            }
        }

        foreach (($data['genre_names'] ?? []) as $genreName) {
            $resolved = $this->resolveGenreNameToId((string) $genreName);
            if ($resolved !== null) {
                $genreIds[] = $resolved;
            }
        }

        $genreIds = array_values(array_unique(array_filter($genreIds, static fn ($id) => (int) $id > 0)));

        return $genreIds;
    }

    private function resolveAlbumId(array $data): ?int
    {
        if (! empty($data['album_id'])) {
            return (int) $data['album_id'];
        }

        if (! empty($data['album_title'])) {
            $normalizedTitle = trim((string) $data['album_title']);
            if ($normalizedTitle === '') {
                return null;
            }

            $album = Album::query()
                ->whereRaw('LOWER(title) = ?', [mb_strtolower($normalizedTitle)])
                ->first();

            if (! $album) {
                $album = Album::query()->create([
                    'title' => $normalizedTitle,
                    'price_eur' => 0,
                    'is_active' => true,
                ]);
            }

            return (int) $album->id;
        }

        return null;
    }

    private function resolveGenreNameToId(string $genreName): ?int
    {
        $normalizedGenreName = trim($genreName);
        if ($normalizedGenreName === '') {
            return null;
        }

        $genre = Genre::query()
            ->whereRaw('LOWER(genre_name) = ?', [mb_strtolower($normalizedGenreName)])
            ->first();

        if (! $genre) {
            $genre = Genre::query()->create(['genre_name' => $normalizedGenreName]);
        }

        return (int) $genre->genre_id;
    }

    private function ffmpegConfig(): array
    {
        return [
            'ffmpeg.binaries' => $this->resolveBinary('FFMPEG_BINARIES', 'ffmpeg'),
            'ffprobe.binaries' => $this->resolveBinary('FFPROBE_BINARIES', 'ffprobe'),
            'timeout' => 120,
            'ffmpeg.threads' => 2,
        ];
    }

    private function resolveBinary(string $envKey, string $commandName): string
    {
        $envValue = env($envKey);
        if (is_string($envValue) && trim($envValue) !== '') {
            $candidate = trim($envValue);
            if ($this->canExecuteBinary($candidate)) {
                return $candidate;
            }
        }

        $resolved = $this->resolveFromPath($commandName);
        if ($resolved !== null) {
            return $resolved;
        }

        if ($this->canExecuteBinary($commandName)) {
            return $commandName;
        }

        return $commandName;
    }

    private function resolveFromPath(string $commandName): ?string
    {
        $finder = PHP_OS_FAMILY === 'Windows' ? 'where' : 'which';
        $output = @shell_exec($finder . ' ' . escapeshellarg($commandName));
        if (is_string($output) && trim($output) !== '') {
            $lines = preg_split('/\r\n|\r|\n/', trim($output));
            if (is_array($lines)) {
                foreach ($lines as $line) {
                    $candidate = trim((string) $line);
                    if ($candidate === '') {
                        continue;
                    }

                    if (PHP_OS_FAMILY === 'Windows' && str_contains(strtolower($candidate), '\\windowsapps\\')) {
                        continue;
                    }

                    if ($this->canExecuteBinary($candidate)) {
                        return $candidate;
                    }
                }
            }
        }

        return $this->resolveFromCommonLocations($commandName);
    }

    private function resolveFromCommonLocations(string $commandName): ?string
    {
        if (PHP_OS_FAMILY !== 'Windows') {
            return null;
        }

        $name = str_ends_with(strtolower($commandName), '.exe') ? $commandName : $commandName . '.exe';
        $candidates = [
            'C:\\ffmpeg\\bin\\' . $name,
            'C:\\Program Files\\ffmpeg\\bin\\' . $name,
            'C:\\Program Files (x86)\\ffmpeg\\bin\\' . $name,
        ];

        foreach ($candidates as $candidate) {
            if ($this->canExecuteBinary($candidate)) {
                return $candidate;
            }
        }

        return null;
    }

    private function splitArtistNames(string $raw): array
    {
        $normalized = str_ireplace([' feat. ', ' feat ', ' ft. ', ' ft ', ' featuring ', ' x ', ' & ', ' and ', ';', '/'], ',', $raw);
        $parts = array_map(
            static fn (string $name): string => trim($name),
            explode(',', $normalized)
        );
        $parts = array_values(array_filter($parts, static fn (string $name): bool => $name !== ''));

        return array_values(array_unique($parts));
    }

    private function extractArtistsFromRaw(string $artistRaw, string $title): array
    {
        $artists = $this->splitArtistNames($artistRaw);
        $cleanTitle = trim($title);

        if ($cleanTitle !== '') {
            if (preg_match('/\((?:feat\.?|ft\.?|featuring)\s+([^)]+)\)/i', $cleanTitle, $match)) {
                $artists = array_merge($artists, $this->splitArtistNames($match[1]));
                $cleanTitle = trim(preg_replace('/\s*\((?:feat\.?|ft\.?|featuring)\s+[^)]+\)\s*/i', ' ', $cleanTitle) ?? $cleanTitle);
            }

            if (preg_match('/\s(?:feat\.?|ft\.?|featuring)\s+(.+)$/i', $cleanTitle, $match)) {
                $artists = array_merge($artists, $this->splitArtistNames($match[1]));
                $cleanTitle = trim(preg_replace('/\s(?:feat\.?|ft\.?|featuring)\s+.+$/i', '', $cleanTitle) ?? $cleanTitle);
            }
        }

        return [array_values(array_unique(array_filter($artists))), $cleanTitle];
    }

    private function firstTagValue(array $keys, array ...$tagSets): ?string
    {
        foreach ($tagSets as $tagSet) {
            if ($tagSet === []) {
                continue;
            }

            $normalized = [];
            foreach ($tagSet as $key => $value) {
                if (! is_string($key)) {
                    continue;
                }
                $normalized[mb_strtolower($key)] = is_scalar($value) ? (string) $value : '';
            }

            foreach ($keys as $key) {
                $lookup = mb_strtolower($key);
                if (array_key_exists($lookup, $normalized) && trim($normalized[$lookup]) !== '') {
                    return $normalized[$lookup];
                }
            }
        }

        return null;
    }

    private function extractBpmFromTags(array ...$tagSets): ?int
    {
        foreach ($tagSets as $tagSet) {
            if ($tagSet === []) {
                continue;
            }

            foreach ($tagSet as $rawKey => $rawValue) {
                $key = mb_strtolower((string) $rawKey);
                $value = is_scalar($rawValue) ? trim((string) $rawValue) : '';

                if ($value === '') {
                    continue;
                }

                // Most reliable: BPM-like key names (TBPM, BPM, TXXX:BPM, bpm_start, tempo, ...)
                $keyLooksLikeBpm = (bool) preg_match('/(^|[:_\-\s])(tbpm|bpm|tempo)([:_\-\s]|$)/i', $key);
                if ($keyLooksLikeBpm) {
                    $numeric = (int) preg_replace('/\D+/', '', $value);
                    if ($numeric >= 60 && $numeric <= 230) {
                        return $numeric;
                    }
                }

                // Fallback: values explicitly containing "bpm" text
                if (preg_match('/\b([6-9]\d|1\d\d|2[0-3]\d)\s*bpm\b/i', $value, $match)) {
                    return (int) $match[1];
                }
            }
        }

        return null;
    }

    private function parseFileNameArtistTitle(string $fileName): array
    {
        $base = pathinfo($fileName, PATHINFO_FILENAME);
        $clean = trim(preg_replace('/\s+/', ' ', str_replace(['_', '.'], ' ', (string) $base)) ?? '');

        if (! str_contains($clean, ' - ')) {
            return [[], $clean];
        }

        $parts = explode(' - ', $clean, 2);
        $artistPart = trim($parts[0] ?? '');
        $titlePart = trim($parts[1] ?? '');

        return [$this->splitArtistNames($artistPart), $titlePart];
    }

    private function detectBpmFromAudio(string $audioPath): ?int
    {
        $config = $this->ffmpegConfig();
        $ffmpegBinary = (string) ($config['ffmpeg.binaries'] ?? 'ffmpeg');

        $process = new Process([
            $ffmpegBinary,
            '-hide_banner',
            '-nostats',
            '-i',
            $audioPath,
            '-vn',
            '-af',
            'bpm',
            '-f',
            'null',
            '-',
        ]);
        $process->setTimeout(120);
        $process->run();
        $output = $process->getErrorOutput() . "\n" . $process->getOutput();
        if (! is_string($output) || trim($output) === '') {
            return null;
        }

        $patterns = [
            '/\bbpm\s*[:=]\s*([0-9]+(?:\.[0-9]+)?)/i',
            '/\btempo\s*[:=]\s*([0-9]+(?:\.[0-9]+)?)/i',
            '/\bBPM\s+([0-9]+(?:\.[0-9]+)?)/i',
        ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $output, $match)) {
                $bpm = (int) round((float) $match[1]);
                if ($bpm >= 60 && $bpm <= 230) {
                    return $bpm;
                }
            }
        }

        return null;
    }

    private function extractEmbeddedCoverDataUrl(string $audioPath): ?string
    {
        $config = $this->ffmpegConfig();
        $ffmpegBinary = (string) ($config['ffmpeg.binaries'] ?? 'ffmpeg');

        try {
            $process = new Process([
                $ffmpegBinary,
                '-hide_banner',
                '-loglevel',
                'error',
                '-i',
                $audioPath,
                '-an',
                '-map',
                '0:v:0',
                '-frames:v',
                '1',
                '-f',
                'image2pipe',
                'pipe:1',
            ]);
            $process->setTimeout(20);
            $process->run();

            if (! $process->isSuccessful()) {
                return null;
            }

            $binary = $process->getOutput();
            if (! is_string($binary) || $binary === '') {
                return null;
            }

            $mime = $this->detectImageMimeFromBinary($binary);
            return 'data:' . $mime . ';base64,' . base64_encode($binary);
        } catch (\Throwable) {
            return null;
        }
    }

    private function detectImageMimeFromBinary(string $binary): string
    {
        // JPEG magic bytes: FF D8 FF
        if (str_starts_with($binary, "\xFF\xD8\xFF")) {
            return 'image/jpeg';
        }

        // PNG magic bytes: 89 50 4E 47 0D 0A 1A 0A
        if (str_starts_with($binary, "\x89\x50\x4E\x47\x0D\x0A\x1A\x0A")) {
            return 'image/png';
        }

        // WEBP starts with "RIFF....WEBP"
        if (strlen($binary) > 12 && substr($binary, 0, 4) === 'RIFF' && substr($binary, 8, 4) === 'WEBP') {
            return 'image/webp';
        }

        return 'image/jpeg';
    }

    private function normalizeNullableDate(mixed $value): ?string
    {
        $normalized = trim((string) $value);
        return $normalized !== '' ? $normalized : null;
    }

    private function canExecuteBinary(string $binary): bool
    {
        try {
            $process = new Process([$binary, '-version']);
            $process->setTimeout(5);
            $process->run();

            return $process->isSuccessful();
        } catch (\Throwable) {
            return false;
        }
    }
}
