<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

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
        'track_cover',
        'track_path',
        'preview_start_at',
        'preview_end_at',
        'preview_path',
    ];

    public function genre(): BelongsTo
    {
        return $this->belongsTo(Genre::class, 'genre_id', 'genre_id');
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
