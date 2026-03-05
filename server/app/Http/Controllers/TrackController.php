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
    public function index(): JsonResponse
    {
        return response()->json([
            'message' => 'ok',
            'data' => Track::with(['genre', 'artists'])->get(),
        ]);
    }

    public function show(int $id): JsonResponse
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
            'data' => $track,
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

        $relativePath = ltrim((string) $track->track_path, '/');
        $fullPath = storage_path('app/public/' . $relativePath);

        if (! is_file($fullPath)) {
            return response()->json(['message' => 'Audio file not found', 'data' => null], 404);
        }

        $durationSec = (int) $track->track_length_sec;
        if ($durationSec <= 0) {
            return response()->json([
                'message' => 'Track duration is required for preview slicing',
                'data' => null,
            ], 422);
        }

        $startSec = (int) $request->query('start', 30);
        $endSec = (int) $request->query('end', 60);

        $startSec = max(0, min($startSec, $durationSec - 1));
        $endSec = max($startSec + 1, min($endSec, $durationSec));

        $maxPreviewLength = 45;
        if (($endSec - $startSec) > $maxPreviewLength) {
            $endSec = $startSec + $maxPreviewLength;
        }

        $fileSize = filesize($fullPath);
        if (! $fileSize || $fileSize <= 0) {
            return response()->json(['message' => 'Invalid audio file size', 'data' => null], 500);
        }

        $startByte = (int) floor(($startSec / $durationSec) * $fileSize);
        $endByte = (int) floor(($endSec / $durationSec) * $fileSize) - 1;

        $startByte = max(0, min($startByte, $fileSize - 1));
        $endByte = max($startByte, min($endByte, $fileSize - 1));
        $length = $endByte - $startByte + 1;

        return response()->stream(function () use ($fullPath, $startByte, $length): void {
            $handle = fopen($fullPath, 'rb');
            if (! $handle) {
                return;
            }

            fseek($handle, $startByte);
            $remaining = $length;
            while (! feof($handle) && $remaining > 0) {
                $chunkSize = min(8192, $remaining);
                $buffer = fread($handle, $chunkSize);
                if ($buffer === false) {
                    break;
                }
                echo $buffer;
                $remaining -= strlen($buffer);
                flush();
            }

            fclose($handle);
        }, 206, [
            'Content-Type' => 'audio/mpeg',
            'Content-Length' => (string) $length,
            'Accept-Ranges' => 'bytes',
            'Cache-Control' => 'no-store, no-cache, must-revalidate, max-age=0',
        ]);
    }
}
