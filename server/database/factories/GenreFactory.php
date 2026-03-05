<?php

namespace Database\Factories;

use App\Models\Genre;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Genre>
 */
class GenreFactory extends Factory
{
    protected $model = Genre::class;

    public function definition(): array
    {
        return [
            'genre_name' => fake()->unique()->randomElement([
                'Hip-Hop',
                'Trap',
                'Drill',
                'Synthwave',
                'Lo-Fi',
                'House',
                'Techno',
            ]),
        ];
    }
}
