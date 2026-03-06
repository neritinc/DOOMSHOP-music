<?php

namespace App\Jobs;

use App\Models\Track;
use FFMpeg\FFMpeg;
use FFMpeg\Filters\Audio\SimpleFilter;
use FFMpeg\Format\Audio\Mp3;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
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

        $outputRelativePath = "previews/preview_{$this->track->id}.mp3";
        $outputPath = Storage::disk('public')->path($outputRelativePath);
        Storage::disk('public')->makeDirectory('previews');

        try {
            $ffmpeg = FFMpeg::create($this->ffmpegConfig());
            $audio = $ffmpeg->open($sourcePath);
            $audio->addFilter(new SimpleFilter([
                '-ss', (string) $previewStart,
                '-t', (string) $duration,
            ]));

            $format = new Mp3();
            $format->setAudioKiloBitrate(192);

            $audio->save($format, $outputPath);

            $this->track->forceFill([
                'preview_path' => $outputRelativePath,
            ])->saveQuietly();
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
        $defaultBinDir = 'C:\\Users\\NERIT INC\\AppData\\Local\\Microsoft\\WinGet\\Packages\\Gyan.FFmpeg_Microsoft.Winget.Source_8wekyb3d8bbwe\\ffmpeg-8.0.1-full_build\\bin';
        $ffmpegBinary = env('FFMPEG_BINARIES', $defaultBinDir . '\\ffmpeg.exe');
        $ffprobeBinary = env('FFPROBE_BINARIES', $defaultBinDir . '\\ffprobe.exe');

        return [
            'ffmpeg.binaries' => $ffmpegBinary,
            'ffprobe.binaries' => $ffprobeBinary,
            'timeout' => 120,
            'ffmpeg.threads' => 4,
        ];
    }

    private function resolveSourcePath(string $trackPath): ?string
    {
        if (is_file($trackPath)) {
            return $trackPath;
        }

        $normalized = ltrim(str_replace('\\', '/', $trackPath), '/');
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

        return null;
    }
}
