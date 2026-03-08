<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\File;

class LiveshowLink extends Model
{
    use HasFactory;

    protected $table = 'liveshow_links';

    protected $fillable = [
        'title',
        'youtube_url',
        'sort_order',
    ];

    protected static function booted(): void
    {
        static::saved(function (): void {
            self::syncCsv();
        });

        static::deleted(function (): void {
            self::syncCsv();
        });
    }

    private static function syncCsv(): void
    {
        $rows = self::query()
            ->select(['id', 'title', 'youtube_url', 'sort_order'])
            ->orderBy('id')
            ->get();

        $lines = ['"id";"title";"youtube_url";"sort_order"'];
        foreach ($rows as $row) {
            $id = (int) $row->id;
            $title = str_replace('"', '""', (string) ($row->title ?? ''));
            $url = str_replace('"', '""', (string) ($row->youtube_url ?? ''));
            $sortOrder = (int) ($row->sort_order ?? 0);
            $lines[] = "\"{$id}\";\"{$title}\";\"{$url}\";\"{$sortOrder}\"";
        }

        $csvPath = database_path('csv/liveshow_links.csv');
        $dir = dirname($csvPath);
        if (! File::exists($dir)) {
            File::makeDirectory($dir, 0755, true);
        }

        File::put($csvPath, implode(PHP_EOL, $lines) . PHP_EOL);
    }
}
