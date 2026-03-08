<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0');
        DB::table('cart_items')->truncate();
        DB::table('carts')->truncate();
        DB::table('track_artists')->truncate();
        DB::table('liveshow_links')->truncate();
        DB::table('recommendation_links')->truncate();
        DB::table('tracks')->truncate();
        DB::table('artists')->truncate();
        DB::table('genres')->truncate();
        DB::table('users')->truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1');

        $this->call([
            UserSeeder::class,
            GenreSeeder::class,
            ArtistSeeder::class,
            TrackSeeder::class,
            LiveshowLinkSeeder::class,
            RecommendationLinkSeeder::class,
        ]);
    }
}
