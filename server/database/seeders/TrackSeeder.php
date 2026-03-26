<?php

namespace Database\Seeders;

use App\Helpers\CsvReader;
use App\Jobs\GenerateTrackPreview;
use App\Models\Album;
use App\Models\Track;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class TrackSeeder extends Seeder
{
    private function value(array $row, string $key, mixed $default = null): mixed
    {
        if (array_key_exists($key, $row)) {
            return $row[$key];
        }

        foreach ($row as $k => $v) {
            $normalized = preg_replace('/^\xEF\xBB\xBF/', '', (string) $k);
            $normalized = trim((string) $normalized, " \t\n\r\0\x0B\"'");
            if ($normalized === $key) {
                return $v;
            }
        }

        return $default;
    }

    public function run(): void
    {
        $rows = CsvReader::csvToArray('csv/tracks.csv', ';');

        foreach ($rows as $row) {
            $id = (int) $this->value($row, 'id', 0);
            $genreId = (int) $this->value($row, 'genre_id', 0);
            $albumId = (int) $this->value($row, 'album_id', 0);
            $title = (string) $this->value($row, 'track_title', '');
            $releaseDateRaw = trim((string) $this->value($row, 'release_date', ''));
            $releaseDate = null;
            if ($releaseDateRaw !== '') {
                $normalizedDate = str_replace('.', '-', $releaseDateRaw);
                if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $normalizedDate)) {
                    $releaseDate = $normalizedDate;
                }
            }
            $bpm = (int) $this->value($row, 'bpm_value', 0);
            $trackLength = (int) $this->value($row, 'track_length', 0);
            $trackCover = $this->value($row, 'track_cover');
            $trackPath = $this->value($row, 'track_path');
            $previewPath = trim((string) $this->value($row, 'preview_path', ''));
            $previewStartAt = (int) $this->value($row, 'preview_start_at', 0);
            $previewEndAt = (int) $this->value($row, 'preview_end_at', 30);
            $artistId = (int) $this->value($row, 'artist_id', 0);

            if ($id <= 0 || $genreId <= 0 || $title === '') {
                continue;
            }

            $resolvedAlbumId = null;
            if ($albumId > 0) {
                $exists = Album::query()->where('id', $albumId)->exists();
                $resolvedAlbumId = $exists ? $albumId : null;
            }

            $track = Track::query()->updateOrCreate(
                ['id' => $id],
                [
                    'genre_id' => $genreId,
                    'album_id' => $resolvedAlbumId,
                    'track_title' => $title,
                    'bpm_value' => $bpm,
                    'release_date' => $releaseDate,
                    'track_length_sec' => $trackLength,
                    'track_price_eur' => 1.99,
                    'track_cover' => $trackCover ?: null,
                    'track_path' => $trackPath ?: null,
                    'preview_start_at' => max(0, $previewStartAt),
                    'preview_end_at' => max(1, $previewEndAt),
                    'preview_path' => $previewPath !== '' ? $previewPath : null,
                ]
            );

            $usedCustomPreview = false;
            if ($previewPath !== '') {
                $candidate = storage_path('app/public/' . ltrim($previewPath, '/'));
                if (is_file($candidate)) {
                    $track->preview_path = $previewPath;
                    $track->preview_start_at = max(0, $previewStartAt);
                    $track->preview_end_at = max(1, $previewEndAt);
                    $track->save();
                    $usedCustomPreview = true;
                }
            }

            if ($artistId > 0) {
                DB::table('track_artists')->updateOrInsert([
                    'track_id' => (int) $track->id,
                    'artist_id' => $artistId,
                ]);
            }

            if (! $usedCustomPreview && ! empty($track->track_path)) {
                try {
                    (new GenerateTrackPreview($track))->handle();
                } catch (\Throwable) {
                    // Preview generation is optional during seeding (e.g. missing ffmpeg).
                }
            }
        }
    }
}
