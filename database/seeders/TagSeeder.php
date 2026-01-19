<?php

namespace Database\Seeders;

use App\Models\Tag;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class TagSeeder extends Seeder
{
    public function run(): void
    {
        $tags = [
            'Co-op',
            'Multiplayer',
            'Online',
            'Split-screen',
            'Single-player',
            'Story-rich',
            'Open World',
            'Linear',
            'Indie',
            'AAA',
            'Exclusive',
            'Cross-platform',
            'VR Support',
            'PS Plus Collection',
            'Free-to-Play',
        ];

        foreach ($tags as $tagName) {
            Tag::create([
                'name' => $tagName,
                'slug' => Str::slug($tagName),
            ]);
        }
    }
}
