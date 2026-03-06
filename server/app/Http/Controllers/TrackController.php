<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreTrackRequest;
use App\Models\Artist;
use App\Models\Genre;
use App\Models\Track;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class TrackController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $isAdmin = $this->isAdmin($request);
        $tracks = Track::with(['genre', 'artists'])->get()->map(
            fn (Track $track) => $this->transformTrackForViewer($track, $isAdmin)
        );

        return response()->json([
            'message' => 'ok',
            'data' => $tracks,
        ]);
    }

    public function show(Request $request, int $id): JsonResponse
    {
        $track = Track::with(['genre', 'artists'])->find($id);

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

        $genreId = $data['genre_id'] ?? null;
        if (! $genreId) {
            $genre = Genre::query()->firstOrCreate([
                'genre_name' => $data['genre_name'],
            ]);
            $genreId = $genre->genre_id;
        }

        $track = Track::query()->create([
            'genre_id' => $genreId,
            'track_title' => $data['track_title'],
            'bpm_value' => $data['bpm_value'] ?? null,
            'release_date' => $data['release_date'] ?? null,
            'track_length_sec' => $data['track_length_sec'] ?? null,
            'track_cover' => $data['track_cover'] ?? null,
            'track_path' => $data['track_path'] ?? null,
        ]);

        $artistIds = $data['artist_ids'] ?? [];
        foreach (($data['artist_names'] ?? []) as $artistName) {
            $artist = Artist::query()->firstOrCreate(['artist_name' => $artistName]);
            $artistIds[] = $artist->artist_id;
        }
        $artistIds = array_values(array_unique($artistIds));

        if (! empty($artistIds)) {
            $track->artists()->sync($artistIds);
        }

        return response()->json([
            'message' => 'Track created successfully',
            'data' => $track->load(['genre', 'artists']),
        ], 201);
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
        }, $status, [
            'Content-Type' => 'audio/mpeg',
            'Content-Length' => (string) $length,
            'Accept-Ranges' => 'bytes',
            'Content-Range' => "bytes {$start}-{$end}/{$fileSize}",
            'Cache-Control' => 'no-store, no-cache, must-revalidate, max-age=0',
        ]);
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

        if (! $isAdmin) {
            $payload['track_path'] = null;
        }

        return $payload;
    }
}
