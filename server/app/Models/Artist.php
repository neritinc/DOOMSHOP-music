<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\File;

class Artist extends Model
{
    use HasFactory;

    protected $primaryKey = 'artist_id';

    public $timestamps = false;

    protected $fillable = [
        'artist_name',
        'artist_picture',
    ];

    protected static function booted(): void
    {
        static::saved(function (): void {
            self::syncArtistsCsv();
        });

        static::deleted(function (): void {
            self::syncArtistsCsv();
        });
    }

    private static function syncArtistsCsv(): void
    {
        $rows = self::query()
            ->select(['artist_id', 'artist_name', 'artist_picture'])
            ->orderBy('artist_id')
            ->get();

        $lines = ['"artist_id";"artist_name";"artist_picture"'];
        foreach ($rows as $row) {
            $id = (int) $row->artist_id;
            $name = str_replace('"', '""', (string) ($row->artist_name ?? ''));
            $picture = str_replace('"', '""', (string) ($row->artist_picture ?? ''));
            $lines[] = "\"{$id}\";\"{$name}\";\"{$picture}\"";
        }

        $csvPath = database_path('csv/artists.csv');
        $dir = dirname($csvPath);
        if (! File::exists($dir)) {
            File::makeDirectory($dir, 0755, true);
        }

        File::put($csvPath, implode(PHP_EOL, $lines) . PHP_EOL);
    }
}
