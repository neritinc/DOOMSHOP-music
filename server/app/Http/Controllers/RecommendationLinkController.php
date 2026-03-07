<?php

namespace App\Http\Controllers;

use App\Models\RecommendationLink;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class RecommendationLinkController extends Controller
{
    public function index(): JsonResponse
    {
        $items = RecommendationLink::query()
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();

        return response()->json([
            'message' => 'ok',
            'data' => $items,
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'title' => 'nullable|string|max:255',
            'media_url' => 'required|string|max:2048',
            'sort_order' => 'nullable|integer|min:0|max:127',
        ]);

        $mediaUrl = trim((string) ($validated['media_url'] ?? ''));
        if (! $this->isSupportedMediaUrl($mediaUrl)) {
            return response()->json([
                'message' => 'Please provide a valid SoundCloud or YouTube URL.',
                'data' => null,
            ], 422);
        }

        $title = trim((string) ($validated['title'] ?? ''));
        if ($title === '') {
            $title = $this->buildDefaultTitle($mediaUrl);
        }

        $item = RecommendationLink::query()->create([
            'title' => $title,
            'media_url' => $mediaUrl,
            'sort_order' => (int) ($validated['sort_order'] ?? 0),
        ]);

        return response()->json([
            'message' => 'Recommendation created successfully',
            'data' => $item,
        ], 201);
    }

    public function destroy(int $id): JsonResponse
    {
        $item = RecommendationLink::query()->find($id);
        if (! $item) {
            return response()->json([
                'message' => "Recommendation not found: {$id}",
                'data' => null,
            ], 404);
        }

        $item->delete();

        return response()->json([
            'message' => 'Recommendation deleted successfully',
            'data' => null,
        ]);
    }

    private function isSupportedMediaUrl(string $url): bool
    {
        if ($url === '') {
            return false;
        }

        $parts = parse_url($url);
        $host = strtolower((string) ($parts['host'] ?? ''));
        if ($host === '') {
            return false;
        }

        return str_contains($host, 'soundcloud.com')
            || str_contains($host, 'youtube.com')
            || str_contains($host, 'youtu.be');
    }

    private function buildDefaultTitle(string $url): string
    {
        $parts = parse_url($url);
        $host = strtolower((string) ($parts['host'] ?? ''));

        if (str_contains($host, 'soundcloud.com')) {
            return 'SoundCloud recommendation';
        }
        if (str_contains($host, 'youtube.com') || str_contains($host, 'youtu.be')) {
            return 'YouTube recommendation';
        }

        return 'Recommendation';
    }
}

