<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;

class Track extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'genre_id',
        'track_title',
        'bpm_value',
        'release_date',
        'track_length_sec',
        'track_price_eur',
        'track_cover',
        'track_path',
        'preview_start_at',
        'preview_end_at',
        'preview_path',
    ];

    protected static function booted(): void
    {
        static::saved(function (Track $track): void {
            if ($track->wasChanged('preview_path')) {
                $oldPreviewPath = (string) ($track->getOriginal('preview_path') ?? '');
                self::deletePreviewIfUnused($oldPreviewPath, (int) $track->id);
            }

            $shouldSyncTracksCsv = $track->wasRecentlyCreated
                || $track->wasChanged('genre_id')
                || $track->wasChanged('track_title')
                || $track->wasChanged('bpm_value')
                || $track->wasChanged('release_date')
                || $track->wasChanged('track_length_sec')
                || $track->wasChanged('track_price_eur')
                || $track->wasChanged('track_cover')
                || $track->wasChanged('track_path')
                || $track->wasChanged('preview_start_at')
                || $track->wasChanged('preview_end_at')
                || $track->wasChanged('preview_path');

            if (
                $track->wasRecentlyCreated
                || $track->wasChanged('preview_path')
                || $track->wasChanged('preview_start_at')
                || $track->wasChanged('preview_end_at')
            ) {
                self::syncPreviewCsv();
            }

            if ($shouldSyncTracksCsv) {
                self::syncTracksCsv();
            }
        });

        static::deleted(function (Track $track): void {
            $deletedPreviewPath = (string) ($track->getOriginal('preview_path') ?: $track->preview_path ?: '');
            self::deletePreviewIfUnused($deletedPreviewPath);
            self::syncPreviewCsv();
            self::syncTracksCsv();
        });
    }

    public static function syncCsvExports(bool $includePreview = true): void
    {
        if ($includePreview) {
            self::syncPreviewCsv();
        }

        self::syncTracksCsv();
    }

    private static function deletePreviewIfUnused(string $previewPath, ?int $excludeTrackId = null): void
    {
        $normalized = ltrim(trim($previewPath), '/');
        if ($normalized === '' || ! str_starts_with($normalized, 'previews/')) {
            return;
        }

        $query = self::query()->where('preview_path', $normalized);
        if ($excludeTrackId !== null && $excludeTrackId > 0) {
            $query->where('id', '!=', $excludeTrackId);
        }

        $stillUsed = $query->exists();
        if (! $stillUsed && Storage::disk('public')->exists($normalized)) {
            Storage::disk('public')->delete($normalized);
        }
    }

    private static function syncPreviewCsv(): void
    {
        $rows = self::query()
            ->select(['id', 'preview_path', 'preview_start_at', 'preview_end_at'])
            ->whereNotNull('preview_path')
            ->where('preview_path', '!=', '')
            ->orderBy('id')
            ->get();

        $lines = ['"id";"preview_path";"preview_start_at";"preview_end_at"'];
        foreach ($rows as $row) {
            $id = (int) $row->id;
            $previewPath = str_replace('"', '""', (string) ($row->preview_path ?? ''));
            $previewStart = (int) ($row->preview_start_at ?? 0);
            $previewEnd = (int) ($row->preview_end_at ?? 30);
            $lines[] = "\"{$id}\";\"{$previewPath}\";\"{$previewStart}\";\"{$previewEnd}\"";
        }

        $csvPath = database_path('csv/track_previews.csv');
        $dir = dirname($csvPath);
        if (! File::exists($dir)) {
            File::makeDirectory($dir, 0755, true);
        }

        File::put($csvPath, implode(PHP_EOL, $lines) . PHP_EOL);
    }

    private static function syncTracksCsv(): void
    {
        $rows = self::query()
            ->select([
                'tracks.id',
                'tracks.genre_id',
                'tracks.track_title',
                'tracks.bpm_value',
                'tracks.release_date',
                'tracks.track_length_sec',
                'tracks.track_price_eur',
                'tracks.track_cover',
                'tracks.track_path',
                'tracks.preview_start_at',
                'tracks.preview_end_at',
                'tracks.preview_path',
            ])
            ->orderBy('tracks.id')
            ->get();

        $artistMap = DB::table('track_artists')
            ->select('track_id', DB::raw('MIN(artist_id) as artist_id'))
            ->groupBy('track_id')
            ->pluck('artist_id', 'track_id');

        $lines = ['"id";"genre_id";"track_title";"bpm_value";"artist_id";"release_date";"track_length";"track_price_eur";"track_cover";"track_path";"preview_start_at";"preview_end_at";"preview_path"'];

        foreach ($rows as $row) {
            $id = (int) $row->id;
            $genreId = (int) ($row->genre_id ?? 0);
            $title = str_replace('"', '""', (string) ($row->track_title ?? ''));
            $bpm = (int) ($row->bpm_value ?? 0);
            $artistId = (int) ($artistMap[$id] ?? 0);
            $releaseDate = (string) ($row->release_date ?? '');
            if ($releaseDate !== '') {
                $releaseDate = str_replace('-', '.', $releaseDate);
            }
            $trackLength = (int) ($row->track_length_sec ?? 0);
            $trackPrice = number_format((float) ($row->track_price_eur ?? 1.99), 2, '.', '');
            $trackCover = str_replace('"', '""', (string) ($row->track_cover ?? ''));
            $trackPath = str_replace('"', '""', (string) ($row->track_path ?? ''));
            $previewStart = (int) ($row->preview_start_at ?? 0);
            $previewEnd = (int) ($row->preview_end_at ?? 30);
            $previewPath = str_replace('"', '""', (string) ($row->preview_path ?? ''));

            $lines[] = "\"{$id}\";\"{$genreId}\";\"{$title}\";\"{$bpm}\";\"{$artistId}\";\"{$releaseDate}\";\"{$trackLength}\";\"{$trackPrice}\";\"{$trackCover}\";\"{$trackPath}\";\"{$previewStart}\";\"{$previewEnd}\";\"{$previewPath}\"";
        }

        $csvPath = database_path('csv/tracks.csv');
        $dir = dirname($csvPath);
        if (! File::exists($dir)) {
            File::makeDirectory($dir, 0755, true);
        }

        File::put($csvPath, implode(PHP_EOL, $lines) . PHP_EOL);
    }

    public function genre(): BelongsTo
    {
        return $this->belongsTo(Genre::class, 'genre_id', 'genre_id');
    }

    public function genres(): BelongsToMany
    {
        return $this->belongsToMany(
            Genre::class,
            'track_genres',
            'track_id',
            'genre_id',
            'id',
            'genre_id'
        );
    }

    public function artists(): BelongsToMany
    {
        return $this->belongsToMany(
            Artist::class,
            'track_artists',
            'track_id',
            'artist_id',
            'id',
            'artist_id'
        );
    }
}
