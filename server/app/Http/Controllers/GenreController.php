<?php

namespace App\Http\Controllers;

use App\Models\Genre;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class GenreController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json(['message' => 'ok', 'data' => Genre::all()]);
    }

    public function store(Request $request): JsonResponse
    {
        $normalizedGenreName = trim((string) $request->input('genre_name', ''));
        $validated = $request->validate([
            'genre_name' => 'required|string|max:255',
        ]);

        $exists = Genre::query()
            ->whereRaw('LOWER(genre_name) = ?', [mb_strtolower($normalizedGenreName)])
            ->exists();
        if ($exists) {
            return response()->json([
                'message' => 'Genre already exists',
                'data' => null,
            ], 422);
        }

        $validated['genre_name'] = $normalizedGenreName;
        $genre = Genre::create($validated);
        return response()->json(['message' => 'Genre created', 'data' => $genre], 201);
    }

    public function show(int $id): JsonResponse
    {
        $genre = Genre::find($id);
        if (! $genre) {
            return response()->json(['message' => 'Genre not found', 'data' => null], 404);
        }
        return response()->json(['message' => 'ok', 'data' => $genre]);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $genre = Genre::find($id);
        if (! $genre) {
            return response()->json(['message' => 'Genre not found', 'data' => null], 404);
        }

        $normalizedGenreName = trim((string) $request->input('genre_name', ''));
        $validated = $request->validate([
            'genre_name' => 'required|string|max:255',
        ]);

        $exists = Genre::query()
            ->whereRaw('LOWER(genre_name) = ?', [mb_strtolower($normalizedGenreName)])
            ->where('genre_id', '!=', $id)
            ->exists();
        if ($exists) {
            return response()->json([
                'message' => 'Genre already exists',
                'data' => null,
            ], 422);
        }

        $validated['genre_name'] = $normalizedGenreName;
        $genre->update($validated);
        return response()->json(['message' => 'Genre updated', 'data' => $genre]);
    }

    public function destroy(int $id): JsonResponse
    {
        $genre = Genre::find($id);
        if (! $genre) {
            return response()->json(['message' => 'Genre not found', 'data' => null], 404);
        }
        $genre->delete();
        return response()->json(['message' => 'Genre deleted', 'data' => ['genre_id' => $id]]);
    }
}
