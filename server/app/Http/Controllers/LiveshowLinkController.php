<?php

namespace App\Http\Controllers;

use App\Models\LiveshowLink;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LiveshowLinkController extends Controller
{
    public function index(): JsonResponse
    {
        $items = LiveshowLink::query()
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
            'youtube_url' => 'required|string|max:2048',
            'sort_order' => 'nullable|integer|min:0|max:127',
        ]);

        $mediaUrl = trim((string) ($validated['youtube_url'] ?? ''));
        if (! $this->isSupportedMediaUrl($mediaUrl)) {
            return response()->json([
                'message' => 'Please provide a valid YouTube or SoundCloud URL.',
                'data' => null,
            ], 422);
        }

        $title = trim((string) ($validated['title'] ?? ''));
        if ($title === '') {
            $title = $this->buildDefaultTitle($mediaUrl);
        }

        $item = LiveshowLink::query()->create([
            'title' => $title,
            'youtube_url' => $mediaUrl,
            'sort_order' => (int) ($validated['sort_order'] ?? 0),
        ]);

        return response()->json([
            'message' => 'Link created successfully',
            'data' => $item,
        ], 201);
    }

    public function destroy(int $id): JsonResponse
    {
        $item = LiveshowLink::query()->find($id);
        if (! $item) {
            return response()->json([
                'message' => "Link not found: {$id}",
                'data' => null,
            ], 404);
        }

        $item->delete();

        return response()->json([
            'message' => 'Link deleted successfully',
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

        return str_contains($host, 'youtube.com')
            || str_contains($host, 'youtu.be')
            || str_contains($host, 'soundcloud.com');
    }

    private function extractYoutubeVideoId(string $url): ?string
    {
        $parts = parse_url($url);
        $host = strtolower((string) ($parts['host'] ?? ''));
        $path = (string) ($parts['path'] ?? '');

        if (str_contains($host, 'youtu.be')) {
            $id = trim(ltrim($path, '/'));
            return $id !== '' ? $id : null;
        }

        if (! str_contains($host, 'youtube.com')) {
            return null;
        }

        if ($path === '/watch') {
            parse_str((string) ($parts['query'] ?? ''), $query);
            $id = trim((string) ($query['v'] ?? ''));
            return $id !== '' ? $id : null;
        }

        if (str_starts_with($path, '/shorts/')) {
            $segments = explode('/', trim($path, '/'));
            $id = trim((string) ($segments[1] ?? ''));
            return $id !== '' ? $id : null;
        }

        if (str_starts_with($path, '/embed/')) {
            $segments = explode('/', trim($path, '/'));
            $id = trim((string) ($segments[1] ?? ''));
            return $id !== '' ? $id : null;
        }

        return null;
    }

    private function buildDefaultTitle(string $url): string
    {
        $parts = parse_url($url);
        $host = strtolower((string) ($parts['host'] ?? ''));

        if (str_contains($host, 'youtube.com') || str_contains($host, 'youtu.be')) {
            $videoId = $this->extractYoutubeVideoId($url);
            return $videoId !== null ? "YouTube video ({$videoId})" : 'YouTube video';
        }

        if (str_contains($host, 'soundcloud.com')) {
            return 'SoundCloud track';
        }

        return 'Media link';
    }
}
