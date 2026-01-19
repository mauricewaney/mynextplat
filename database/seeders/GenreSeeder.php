<?php

namespace Database\Seeders;

use App\Models\Genre;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class GenreSeeder extends Seeder
{
    public function run(): void
    {
        $genres = [
            'Action',
            'Adventure',
            'RPG',
            'JRPG',
            'Shooter',
            'FPS',
            'Third-Person Shooter',
            'Platformer',
            'Puzzle',
            'Fighting',
            'Racing',
            'Sports',
            'Simulation',
            'Strategy',
            'Horror',
            'Survival',
            'Stealth',
            'Roguelike',
            'Metroidvania',
            'Souls-like',
        ];

        foreach ($genres as $genreName) {
            Genre::create([
                'name' => $genreName,
                'slug' => Str::slug($genreName),
            ]);
        }
    }
}
