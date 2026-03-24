<?php

namespace App\Http\Controllers;

use App\Models\Album;
use App\Models\Track;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use ZipArchive;
use Illuminate\Support\Facades\File;

class DownloadController extends Controller
{
    public function download(string $type, int $id): JsonResponse|StreamedResponse|BinaryFileResponse
    {
        if ($type === 'track') {
            $track = Track::find($id);
            if (! $track) {
                return response()->json(['message' => 'Track not found', 'data' => null], 404);
            }

            $sourcePath = $this->resolveTrackSourcePath((string) ($track->track_path ?? ''));
            if (! $sourcePath || ! is_file($sourcePath)) {
                return response()->json(['message' => 'Track file not found', 'data' => null], 404);
            }

            $extension = strtolower((string) pathinfo($sourcePath, PATHINFO_EXTENSION));
            if ($extension === '' || $extension === 'bin') {
                $extension = 'mp3';
            }

            $downloadName = $this->safeDownloadName((string) ($track->track_title ?? 'track'), $extension);
            return $this->streamAudioFile($sourcePath, $downloadName);
        }

        if ($type === 'album') {
            $album = Album::with('tracks')->find($id);
            if (! $album) {
                return response()->json(['message' => 'Album not found', 'data' => null], 404);
            }

            $zipPath = $this->buildAlbumZip($album);
            if (! $zipPath) {
                return response()->json(['message' => 'Album download not available', 'data' => null], 404);
            }

            $zipName = $this->safeDownloadName((string) ($album->title ?? 'album'), 'zip');
            return response()->download($zipPath, $zipName)->deleteFileAfterSend(true);
        }

        return response()->json(['message' => 'Invalid download type', 'data' => null], 404);
    }

    private function buildAlbumZip(Album $album): ?string
    {
        $tracks = $album->tracks;
        if ($tracks->isEmpty()) {
            return null;
        }

        $tmpDir = storage_path('app/tmp');
        if (! File::exists($tmpDir)) {
            File::makeDirectory($tmpDir, 0755, true);
        }

        $zipPath = $tmpDir . '/album_' . $album->id . '_' . Str::random(8) . '.zip';
        $zip = new ZipArchive();
        if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            return null;
        }

        $added = 0;
        $usedNames = [];
        foreach ($tracks as $track) {
            $sourcePath = $this->resolveTrackSourcePath((string) ($track->track_path ?? ''));
            if (! $sourcePath || ! is_file($sourcePath)) {
                continue;
            }

            $extension = strtolower((string) pathinfo($sourcePath, PATHINFO_EXTENSION));
            if ($extension === '' || $extension === 'bin') {
                $extension = 'mp3';
            }

            $entryName = $this->safeDownloadName((string) ($track->track_title ?? 'track'), $extension);
            if (isset($usedNames[$entryName])) {
                $base = pathinfo($entryName, PATHINFO_FILENAME);
                $entryName = $base . '_' . $track->id . '.' . $extension;
            }
            $usedNames[$entryName] = true;
            $zip->addFile($sourcePath, $entryName);
            $added++;
        }

        $zip->close();

        if ($added === 0) {
            if (is_file($zipPath)) {
                @unlink($zipPath);
            }
            return null;
        }

        return $zipPath;
    }

    private function streamAudioFile(string $fullPath, ?string $downloadName = null): StreamedResponse
    {
        $fileSize = filesize($fullPath);
        if (! $fileSize || $fileSize <= 0) {
            return response()->stream(function (): void {}, 500);
        }

        $start = 0;
        $end = $fileSize - 1;
        $status = 200;

        $rangeHeader = request()->header('Range');
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

        $base = preg_replace('/[\\\\\/\:\*\?\"\<\>\|]+/', ' ', $base) ?? $base;
        $base = preg_replace('/\s+/', ' ', $base) ?? $base;
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
}
