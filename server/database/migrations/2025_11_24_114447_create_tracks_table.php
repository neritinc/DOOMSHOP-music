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
        Schema::create('tracks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('genre_id')->constrained('genres', 'genre_id')->cascadeOnDelete();
            $table->string('track_title');
            $table->integer('bpm_value')->nullable();
            $table->date('release_date')->nullable();
            $table->integer('track_length_sec')->nullable();
            $table->string('track_cover')->nullable();
            $table->string('track_path')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tracks');
    }
};
