<?php

namespace Database\Seeders;

use App\Helpers\CsvReader;
use App\Models\Genre;
use Illuminate\Database\Seeder;

class GenreSeeder extends Seeder
{
    public function run(): void
    {
        $rows = CsvReader::csvToArray('csv/genres.csv', ';');

        foreach ($rows as $row) {
            Genre::query()->updateOrCreate(
                ['genre_id' => (int) $row['genre_id']],
                ['genre_name' => $row['genre_name']]
            );
        }
    }
}
