<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\File;

class Genre extends Model
{
    use HasFactory;

    protected $primaryKey = 'genre_id';

    public $timestamps = false;

    protected $fillable = [
        'genre_name',
    ];

    protected static function booted(): void
    {
        static::saved(function (): void {
            self::syncGenresCsv();
        });

        static::deleted(function (): void {
            self::syncGenresCsv();
        });
    }

    private static function syncGenresCsv(): void
    {
        $rows = self::query()
            ->select(['genre_id', 'genre_name'])
            ->orderBy('genre_id')
            ->get();

        $lines = ['"genre_id";"genre_name"'];
        foreach ($rows as $row) {
            $id = (int) $row->genre_id;
            $name = str_replace('"', '""', (string) ($row->genre_name ?? ''));
            $lines[] = "\"{$id}\";\"{$name}\"";
        }

        $csvPath = database_path('csv/genres.csv');
        $dir = dirname($csvPath);
        if (! File::exists($dir)) {
            File::makeDirectory($dir, 0755, true);
        }

        File::put($csvPath, implode(PHP_EOL, $lines) . PHP_EOL);
    }
}
