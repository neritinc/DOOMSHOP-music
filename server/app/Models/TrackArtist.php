<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\Pivot;

class TrackArtist extends Pivot
{
    public $timestamps = false;

    protected $table = 'track_artists';

    protected static function booted(): void
    {
        static::saved(function (): void {
            Track::syncCsvExports();
        });

        static::deleted(function (): void {
            Track::syncCsvExports();
        });
    }
}
