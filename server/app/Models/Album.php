<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\File;

class Album extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'title',
        'cover',
        'release_date',
        'price_eur',
        'description',
        'is_active',
    ];

    protected static function booted(): void
    {
        static::saved(function (): void {
            self::syncAlbumsCsv();
        });

        static::deleted(function (): void {
            self::syncAlbumsCsv();
        });
    }

    public function tracks(): HasMany
    {
        return $this->hasMany(Track::class, 'album_id', 'id');
    }

    private static function syncAlbumsCsv(): void
    {
        $rows = self::query()
            ->select(['id', 'title', 'cover', 'release_date', 'price_eur', 'description', 'is_active'])
            ->orderBy('id')
            ->get();

        $lines = ['"id";"title";"cover";"release_date";"price_eur";"description";"is_active"'];
        foreach ($rows as $row) {
            $id = (int) $row->id;
            $title = str_replace('"', '""', (string) ($row->title ?? ''));
            $cover = str_replace('"', '""', (string) ($row->cover ?? ''));
            $releaseDate = (string) ($row->release_date ?? '');
            $price = number_format((float) ($row->price_eur ?? 0), 2, '.', '');
            $description = str_replace('"', '""', (string) ($row->description ?? ''));
            $isActive = (int) ((bool) ($row->is_active ?? true));

            $lines[] = "\"{$id}\";\"{$title}\";\"{$cover}\";\"{$releaseDate}\";\"{$price}\";\"{$description}\";\"{$isActive}\"";
        }

        $csvPath = database_path('csv/albums.csv');
        $dir = dirname($csvPath);
        if (! File::exists($dir)) {
            File::makeDirectory($dir, 0755, true);
        }

        File::put($csvPath, implode(PHP_EOL, $lines) . PHP_EOL);
    }
}
