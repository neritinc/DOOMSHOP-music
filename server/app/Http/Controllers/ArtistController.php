<?php

namespace App\Http\Controllers;

use App\Models\Artist;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ArtistController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json(['message' => 'ok', 'data' => Artist::all()]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'artist_name' => 'required|string|max:255|unique:artists,artist_name',
            'artist_picture' => 'nullable|string|max:255',
        ]);

        $artist = Artist::create($validated);
        return response()->json(['message' => 'Artist created', 'data' => $artist], 201);
    }

    public function show(int $id): JsonResponse
    {
        $artist = Artist::find($id);
        if (! $artist) {
            return response()->json(['message' => 'Artist not found', 'data' => null], 404);
        }
        return response()->json(['message' => 'ok', 'data' => $artist]);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $artist = Artist::find($id);
        if (! $artist) {
            return response()->json(['message' => 'Artist not found', 'data' => null], 404);
        }

        $validated = $request->validate([
            'artist_name' => "required|string|max:255|unique:artists,artist_name,{$id},artist_id",
            'artist_picture' => 'nullable|string|max:255',
        ]);

        $artist->update($validated);
        return response()->json(['message' => 'Artist updated', 'data' => $artist]);
    }

    public function destroy(int $id): JsonResponse
    {
        $artist = Artist::find($id);
        if (! $artist) {
            return response()->json(['message' => 'Artist not found', 'data' => null], 404);
        }
        $artist->delete();
        return response()->json(['message' => 'Artist deleted', 'data' => ['artist_id' => $id]]);
    }
}
