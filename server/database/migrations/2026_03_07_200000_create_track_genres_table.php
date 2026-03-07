<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('track_genres', function (Blueprint $table): void {
            $table->unsignedBigInteger('track_id');
            $table->unsignedBigInteger('genre_id');

            $table->primary(['track_id', 'genre_id']);
            $table->foreign('track_id')->references('id')->on('tracks')->cascadeOnDelete();
            $table->foreign('genre_id')->references('genre_id')->on('genres')->cascadeOnDelete();
        });

        // Backfill existing single genre_id associations.
        DB::statement('
            INSERT INTO track_genres (track_id, genre_id)
            SELECT id, genre_id
            FROM tracks
            WHERE genre_id IS NOT NULL
        ');
    }

    public function down(): void
    {
        Schema::dropIfExists('track_genres');
    }
};

