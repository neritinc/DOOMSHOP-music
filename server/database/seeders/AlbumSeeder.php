<?php

namespace Database\Seeders;

use App\Helpers\CsvReader;
use App\Models\Album;
use Illuminate\Database\Seeder;

class AlbumSeeder extends Seeder
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
        $rows = CsvReader::csvToArray('csv/albums.csv', ';');

        foreach ($rows as $row) {
            $id = (int) $this->value($row, 'id', 0);
            $title = trim((string) $this->value($row, 'title', ''));
            $cover = trim((string) $this->value($row, 'cover', ''));
            $releaseDateRaw = trim((string) $this->value($row, 'release_date', ''));
            $releaseDate = $releaseDateRaw === '' ? null : str_replace('.', '-', $releaseDateRaw);
            $priceRaw = trim((string) $this->value($row, 'price_eur', '0'));
            $price = is_numeric($priceRaw) ? round((float) $priceRaw, 2) : 0.0;
            $description = trim((string) $this->value($row, 'description', ''));
            $isActiveRaw = trim((string) $this->value($row, 'is_active', '1'));
            $isActive = $isActiveRaw === '' ? true : (bool) ((int) $isActiveRaw);

            if ($id <= 0 || $title === '') {
                continue;
            }

            Album::query()->updateOrCreate(
                ['id' => $id],
                [
                    'title' => $title,
                    'cover' => $cover !== '' ? $cover : null,
                    'release_date' => $releaseDate,
                    'price_eur' => $price,
                    'description' => $description !== '' ? $description : null,
                    'is_active' => $isActive,
                ]
            );
        }
    }
}
