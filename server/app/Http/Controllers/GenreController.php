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
        $validated = $request->validate([
            'genre_name' => 'required|string|max:255|unique:genres,genre_name',
        ]);

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

        $validated = $request->validate([
            'genre_name' => "required|string|max:255|unique:genres,genre_name,{$id},genre_id",
        ]);

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
