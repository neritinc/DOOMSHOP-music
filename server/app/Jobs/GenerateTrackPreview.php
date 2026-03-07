<?php

namespace App\Jobs;

use App\Models\Track;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\Process\Process;
use Throwable;

class GenerateTrackPreview implements ShouldQueue
{
    use Queueable;

    public function __construct(public Track $track)
    {
    }

    public function handle(): void
    {
        $trackPath = (string) ($this->track->track_path ?? '');

        if ($trackPath === '') {
            return;
        }

        $previewStart = max(0, (int) ($this->track->preview_start_at ?? 0));
        $previewEnd = max(0, (int) ($this->track->preview_end_at ?? 30));
        $duration = $previewEnd - $previewStart;

        if ($duration <= 0) {
            return;
        }

        $sourcePath = $this->resolveSourcePath($trackPath);

        if ($sourcePath === null || !is_file($sourcePath)) {
            return;
        }

        $oldPreviewPath = (string) ($this->track->preview_path ?? '');
        $outputRelativePath = "previews/preview_{$this->track->id}_" . Str::uuid()->toString() . ".mp3";
        $outputPath = Storage::disk('public')->path($outputRelativePath);
        Storage::disk('public')->makeDirectory('previews');

        try {
            $config = $this->ffmpegConfig();
            $ffmpegBinary = (string) ($config['ffmpeg.binaries'] ?? 'ffmpeg');

            $process = new Process([
                $ffmpegBinary,
                '-y',
                '-hide_banner',
                '-loglevel',
                'error',
                '-ss',
                (string) $previewStart,
                '-t',
                (string) $duration,
                '-i',
                $sourcePath,
                '-vn',
                '-acodec',
                'libmp3lame',
                '-b:a',
                '192k',
                $outputPath,
            ]);
            $process->setTimeout(120);
            $process->run();

            if (! is_file($outputPath) || filesize($outputPath) <= 0) {
                $errorOutput = trim($process->getErrorOutput() . "\n" . $process->getOutput());
                throw new \RuntimeException('ffmpeg preview generation failed: ' . $errorOutput);
            }

            $this->track->forceFill([
                'preview_path' => $outputRelativePath,
            ])->saveQuietly();

            if ($oldPreviewPath !== '' && $oldPreviewPath !== $outputRelativePath && Storage::disk('public')->exists($oldPreviewPath)) {
                Storage::disk('public')->delete($oldPreviewPath);
            }
        } catch (Throwable $e) {
            Log::warning('Track preview generation skipped.', [
                'track_id' => $this->track->id,
                'track_path' => $trackPath,
                'error' => $e->getMessage(),
            ]);
        }
    }

    private function ffmpegConfig(): array
    {
        return [
            'ffmpeg.binaries' => $this->resolveBinary('FFMPEG_BINARIES', 'ffmpeg'),
            'ffprobe.binaries' => $this->resolveBinary('FFPROBE_BINARIES', 'ffprobe'),
            'timeout' => 120,
            'ffmpeg.threads' => 4,
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

    private function canExecuteBinary(string $binary): bool
    {
        try {
            $process = new Process([$binary, '-version']);
            $process->setTimeout(5);
            $process->run();

            return $process->isSuccessful();
        } catch (Throwable) {
            return false;
        }
    }

    private function resolveSourcePath(string $trackPath): ?string
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

