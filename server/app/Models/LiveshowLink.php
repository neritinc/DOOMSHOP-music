<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LiveshowLink extends Model
{
    use HasFactory;

    protected $table = 'liveshow_links';

    protected $fillable = [
        'title',
        'youtube_url',
        'sort_order',
    ];
}

