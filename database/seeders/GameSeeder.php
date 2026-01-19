<?php

namespace Database\Seeders;

use App\Models\Game;
use App\Models\Genre;
use App\Models\Platform;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class GameSeeder extends Seeder
{
    public function run(): void
    {
        // Get platform IDs
        $ps5 = Platform::where('slug', 'ps5')->first();
        $ps4 = Platform::where('slug', 'ps4')->first();

        // Get genre IDs
        $genres = Genre::all()->keyBy('slug');

        $games = [
            [
                'title' => 'Ghost of Tsushima',
                'developer' => 'Sucker Punch Productions',
                'publisher' => 'Sony Interactive Entertainment',
                'release_date' => '2020-07-17',
                'difficulty' => 3,
                'time_min' => 40,
                'time_max' => 60,
                'playthroughs_required' => 1,
                'has_online_trophies' => true,
                'missable_trophies' => false,
                'metacritic_score' => 83,
                'platforms' => [$ps4->id, $ps5->id],
                'genres' => ['action', 'adventure'],
            ],
            [
                'title' => 'God of War Ragnarok',
                'developer' => 'Santa Monica Studio',
                'publisher' => 'Sony Interactive Entertainment',
                'release_date' => '2022-11-09',
                'difficulty' => 3,
                'time_min' => 50,
                'time_max' => 70,
                'playthroughs_required' => 1,
                'has_online_trophies' => false,
                'missable_trophies' => false,
                'metacritic_score' => 94,
                'platforms' => [$ps4->id, $ps5->id],
                'genres' => ['action', 'adventure'],
            ],
            [
                'title' => 'Elden Ring',
                'developer' => 'FromSoftware',
                'publisher' => 'Bandai Namco Entertainment',
                'release_date' => '2022-02-25',
                'difficulty' => 7,
                'time_min' => 80,
                'time_max' => 120,
                'playthroughs_required' => 3,
                'has_online_trophies' => false,
                'missable_trophies' => true,
                'metacritic_score' => 96,
                'platforms' => [$ps4->id, $ps5->id],
                'genres' => ['action', 'rpg', 'souls-like'],
            ],
            [
                'title' => 'Horizon Forbidden West',
                'developer' => 'Guerrilla Games',
                'publisher' => 'Sony Interactive Entertainment',
                'release_date' => '2022-02-18',
                'difficulty' => 3,
                'time_min' => 60,
                'time_max' => 80,
                'playthroughs_required' => 1,
                'has_online_trophies' => false,
                'missable_trophies' => false,
                'metacritic_score' => 88,
                'platforms' => [$ps4->id, $ps5->id],
                'genres' => ['action', 'adventure', 'rpg'],
            ],
            [
                'title' => 'Spider-Man 2',
                'developer' => 'Insomniac Games',
                'publisher' => 'Sony Interactive Entertainment',
                'release_date' => '2023-10-20',
                'difficulty' => 2,
                'time_min' => 25,
                'time_max' => 35,
                'playthroughs_required' => 1,
                'has_online_trophies' => false,
                'missable_trophies' => false,
                'metacritic_score' => 90,
                'platforms' => [$ps5->id],
                'genres' => ['action', 'adventure'],
            ],
            [
                'title' => 'Final Fantasy XVI',
                'developer' => 'Square Enix',
                'publisher' => 'Square Enix',
                'release_date' => '2023-06-22',
                'difficulty' => 4,
                'time_min' => 70,
                'time_max' => 100,
                'playthroughs_required' => 2,
                'has_online_trophies' => false,
                'missable_trophies' => false,
                'metacritic_score' => 87,
                'platforms' => [$ps5->id],
                'genres' => ['action', 'rpg', 'jrpg'],
            ],
            [
                'title' => 'Returnal',
                'developer' => 'Housemarque',
                'publisher' => 'Sony Interactive Entertainment',
                'release_date' => '2021-04-30',
                'difficulty' => 8,
                'time_min' => 50,
                'time_max' => 100,
                'playthroughs_required' => 1,
                'has_online_trophies' => false,
                'missable_trophies' => false,
                'metacritic_score' => 86,
                'platforms' => [$ps5->id],
                'genres' => ['action', 'shooter', 'roguelike'],
            ],
            [
                'title' => 'Ratchet & Clank: Rift Apart',
                'developer' => 'Insomniac Games',
                'publisher' => 'Sony Interactive Entertainment',
                'release_date' => '2021-06-11',
                'difficulty' => 3,
                'time_min' => 15,
                'time_max' => 25,
                'playthroughs_required' => 2,
                'has_online_trophies' => false,
                'missable_trophies' => false,
                'metacritic_score' => 88,
                'platforms' => [$ps5->id],
                'genres' => ['action', 'adventure', 'platformer'],
            ],
            [
                'title' => 'The Last of Us Part II',
                'developer' => 'Naughty Dog',
                'publisher' => 'Sony Interactive Entertainment',
                'release_date' => '2020-06-19',
                'difficulty' => 4,
                'time_min' => 25,
                'time_max' => 35,
                'playthroughs_required' => 1,
                'has_online_trophies' => false,
                'missable_trophies' => true,
                'metacritic_score' => 93,
                'platforms' => [$ps4->id, $ps5->id],
                'genres' => ['action', 'adventure', 'survival', 'horror'],
            ],
            [
                'title' => 'Bloodborne',
                'developer' => 'FromSoftware',
                'publisher' => 'Sony Interactive Entertainment',
                'release_date' => '2015-03-24',
                'difficulty' => 8,
                'time_min' => 40,
                'time_max' => 80,
                'playthroughs_required' => 3,
                'has_online_trophies' => true,
                'missable_trophies' => true,
                'metacritic_score' => 92,
                'platforms' => [$ps4->id],
                'genres' => ['action', 'rpg', 'souls-like', 'horror'],
            ],
        ];

        foreach ($games as $gameData) {
            $platformIds = $gameData['platforms'];
            $genreSlugs = $gameData['genres'];
            unset($gameData['platforms'], $gameData['genres']);

            $gameData['slug'] = Str::slug($gameData['title']);

            $game = Game::create($gameData);

            // Attach platforms
            $game->platforms()->attach($platformIds);

            // Attach genres
            $genreIds = [];
            foreach ($genreSlugs as $slug) {
                if (isset($genres[$slug])) {
                    $genreIds[] = $genres[$slug]->id;
                }
            }
            $game->genres()->attach($genreIds);
        }
    }
}
