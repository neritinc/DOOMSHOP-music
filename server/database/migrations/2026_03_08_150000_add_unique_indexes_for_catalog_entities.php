<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('artists', function (Blueprint $table) {
            $table->unique('artist_name', 'artists_artist_name_unique');
        });

        Schema::table('genres', function (Blueprint $table) {
            $table->unique('genre_name', 'genres_genre_name_unique');
        });

        Schema::table('tracks', function (Blueprint $table) {
            $table->unique('track_title', 'tracks_track_title_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tracks', function (Blueprint $table) {
            $table->dropUnique('tracks_track_title_unique');
        });

        Schema::table('genres', function (Blueprint $table) {
            $table->dropUnique('genres_genre_name_unique');
        });

        Schema::table('artists', function (Blueprint $table) {
            $table->dropUnique('artists_artist_name_unique');
        });
    }
};
