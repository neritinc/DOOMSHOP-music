<?php

namespace App\Http\Controllers;

use App\Models\Album;
use App\Models\Track;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class AlbumController extends Controller
{
    public function index(): JsonResponse
    {
        $albums = Album::query()->with(['tracks.artists', 'tracks.genre'])->orderBy('title')->get();
        return response()->json(['message' => 'ok', 'data' => $albums]);
    }

    public function show(int $id): JsonResponse
    {
        $album = Album::query()->with(['tracks.artists', 'tracks.genre'])->find($id);
        if (! $album) {
            return response()->json(['message' => 'Album not found', 'data' => null], 404);
        }

        return response()->json(['message' => 'ok', 'data' => $album]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'cover' => 'nullable|string|max:255',
            'cover_file' => 'nullable|image|max:5120',
            'release_date' => 'nullable|date',
            'price_eur' => 'nullable|numeric|min:0|max:9999.99',
            'description' => 'nullable|string|max:5000',
            'is_active' => 'nullable|boolean',
        ]);

        $normalizedTitle = trim((string) ($validated['title'] ?? ''));
        $exists = Album::query()->whereRaw('LOWER(title) = ?', [mb_strtolower($normalizedTitle)])->exists();
        if ($exists) {
            return response()->json(['message' => 'Album already exists', 'data' => null], 422);
        }

        $validated['title'] = $normalizedTitle;
        $validated['price_eur'] = isset($validated['price_eur']) ? round((float) $validated['price_eur'], 2) : 0;
        $validated['is_active'] = array_key_exists('is_active', $validated) ? (bool) $validated['is_active'] : true;
        $validated['release_date'] = ! empty($validated['release_date']) ? $validated['release_date'] : null;
        if ($request->hasFile('cover_file')) {
            $validated['cover'] = $request->file('cover_file')->store('album-covers', 'public');
        }
        unset($validated['cover_file']);

        $album = Album::query()->create($validated);
        return response()->json(['message' => 'Album created', 'data' => $album], 201);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $album = Album::query()->find($id);
        if (! $album) {
            return response()->json(['message' => 'Album not found', 'data' => null], 404);
        }

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'cover' => 'nullable|string|max:255',
            'cover_file' => 'nullable|image|max:5120',
            'release_date' => 'nullable|date',
            'price_eur' => 'nullable|numeric|min:0|max:9999.99',
            'description' => 'nullable|string|max:5000',
            'is_active' => 'nullable|boolean',
        ]);

        $normalizedTitle = trim((string) ($validated['title'] ?? ''));
        $exists = Album::query()
            ->whereRaw('LOWER(title) = ?', [mb_strtolower($normalizedTitle)])
            ->where('id', '!=', $id)
            ->exists();
        if ($exists) {
            return response()->json(['message' => 'Album already exists', 'data' => null], 422);
        }

        $validated['title'] = $normalizedTitle;
        $validated['price_eur'] = isset($validated['price_eur']) ? round((float) $validated['price_eur'], 2) : ($album->price_eur ?? 0);
        $validated['release_date'] = ! empty($validated['release_date']) ? $validated['release_date'] : null;
        if (array_key_exists('is_active', $validated)) {
            $validated['is_active'] = (bool) $validated['is_active'];
        }
        $oldCover = (string) ($album->cover ?? '');
        if ($request->hasFile('cover_file')) {
            $validated['cover'] = $request->file('cover_file')->store('album-covers', 'public');
        }
        unset($validated['cover_file']);

        $album->update($validated);
        if ($request->hasFile('cover_file') && $oldCover !== '' && $oldCover !== (string) ($album->cover ?? '') && Storage::disk('public')->exists($oldCover)) {
            Storage::disk('public')->delete($oldCover);
        }
        return response()->json(['message' => 'Album updated', 'data' => $album]);
    }

    public function destroy(int $id): JsonResponse
    {
        $album = Album::query()->find($id);
        if (! $album) {
            return response()->json(['message' => 'Album not found', 'data' => null], 404);
        }

        $oldCover = (string) ($album->cover ?? '');
        $album->delete();
        if ($oldCover !== '' && Storage::disk('public')->exists($oldCover)) {
            Storage::disk('public')->delete($oldCover);
        }
        Track::syncCsvExports();
        return response()->json(['message' => 'Album deleted', 'data' => ['id' => $id]]);
    }

    public function syncTracks(Request $request, int $id): JsonResponse
    {
        $album = Album::query()->find($id);
        if (! $album) {
            return response()->json(['message' => 'Album not found', 'data' => null], 404);
        }

        $validated = $request->validate([
            'track_ids' => 'required|array|min:1',
            'track_ids.*' => 'integer|exists:tracks,id',
        ]);

        $trackIds = array_values(array_unique(array_map(static fn ($x) => (int) $x, $validated['track_ids'])));

        Track::query()->where('album_id', $id)->whereNotIn('id', $trackIds)->update(['album_id' => null]);
        Track::query()->whereIn('id', $trackIds)->update(['album_id' => $id]);
        Track::syncCsvExports();

        return response()->json([
            'message' => 'Album tracks synced',
            'data' => Album::query()->with(['tracks.artists', 'tracks.genre'])->find($id),
        ]);
    }
}
