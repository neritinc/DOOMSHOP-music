<?php

namespace Database\Seeders;

use App\Helpers\CsvReader;
use App\Models\LiveshowLink;
use Illuminate\Database\Seeder;

class LiveshowLinkSeeder extends Seeder
{
    public function run(): void
    {
        $rows = CsvReader::csvToArray('csv/liveshow_links.csv', ';');

        foreach ($rows as $row) {
            LiveshowLink::query()->updateOrCreate(
                ['id' => (int) ($row['id'] ?? 0)],
                [
                    'title' => (string) ($row['title'] ?? ''),
                    'youtube_url' => (string) ($row['youtube_url'] ?? ''),
                    'sort_order' => (int) ($row['sort_order'] ?? 0),
                ]
            );
        }
    }
}
