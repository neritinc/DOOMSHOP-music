<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RecommendationLink extends Model
{
    use HasFactory;

    protected $table = 'recommendation_links';

    protected $fillable = [
        'title',
        'media_url',
        'sort_order',
    ];
}

