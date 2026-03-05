<?php

namespace Database\Factories;

use App\Models\Genre;
use App\Models\Track;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Track>
 */
class TrackFactory extends Factory
{
    protected $model = Track::class;

    public function definition(): array
    {
        return [
            'genre_id' => Genre::factory(),
            'track_title' => fake()->sentence(3),
            'bpm_value' => fake()->numberBetween(80, 180),
            'release_date' => fake()->date(),
            'track_length_sec' => fake()->numberBetween(90, 360),
            'track_cover' => fake()->imageUrl(),
            'track_path' => 'tracks/' . fake()->slug() . '.mp3',
        ];
    }
}
