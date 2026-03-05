<?php

namespace Database\Seeders;

use App\Helpers\CsvReader;
use App\Models\Artist;
use Illuminate\Database\Seeder;

class ArtistSeeder extends Seeder
{
    public function run(): void
    {
        $rows = CsvReader::csvToArray('csv/artists.csv', ';');

        foreach ($rows as $row) {
            Artist::query()->updateOrCreate(
                ['artist_id' => (int) $row['artist_id']],
                [
                    'artist_name' => $row['artist_name'],
                    'artist_picture' => $row['artist_picture'] ?: null,
                ]
            );
        }
    }
}
