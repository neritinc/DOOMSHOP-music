<?php

namespace Database\Seeders;

use App\Helpers\CsvReader;
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
            $title = (string) $this->value($row, 'track_title', '');
            $releaseDateRaw = (string) $this->value($row, 'release_date', '');
            $releaseDate = str_replace('.', '-', $releaseDateRaw);
            $bpm = (int) $this->value($row, 'bpm_value', 0);
            $trackLength = (int) $this->value($row, 'track_length', 0);
            $trackCover = $this->value($row, 'track_cover');
            $trackPath = $this->value($row, 'track_path');
            $artistId = (int) $this->value($row, 'artist_id', 0);

            if ($id <= 0 || $genreId <= 0 || $title === '') {
                continue;
            }

            $track = Track::query()->updateOrCreate(
                ['id' => $id],
                [
                    'genre_id' => $genreId,
                    'track_title' => $title,
                    'bpm_value' => $bpm,
                    'release_date' => $releaseDate,
                    'track_length_sec' => $trackLength,
                    'track_cover' => $trackCover ?: null,
                    'track_path' => $trackPath ?: null,
                ]
            );

            if ($artistId > 0) {
                DB::table('track_artists')->updateOrInsert([
                    'track_id' => (int) $track->id,
                    'artist_id' => $artistId,
                ]);
            }
        }
    }
}
