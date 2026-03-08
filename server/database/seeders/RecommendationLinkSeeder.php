<?php

namespace Database\Seeders;

use App\Helpers\CsvReader;
use App\Models\RecommendationLink;
use Illuminate\Database\Seeder;

class RecommendationLinkSeeder extends Seeder
{
    public function run(): void
    {
        $rows = CsvReader::csvToArray('csv/recommendation_links.csv', ';');

        foreach ($rows as $row) {
            RecommendationLink::query()->updateOrCreate(
                ['id' => (int) ($row['id'] ?? 0)],
                [
                    'title' => (string) ($row['title'] ?? ''),
                    'media_url' => (string) ($row['media_url'] ?? ''),
                    'sort_order' => (int) ($row['sort_order'] ?? 0),
                ]
            );
        }
    }
}
