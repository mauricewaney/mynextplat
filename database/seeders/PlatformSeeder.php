<?php

namespace Database\Seeders;

use App\Models\Platform;
use Illuminate\Database\Seeder;

class PlatformSeeder extends Seeder
{
    public function run(): void
    {
        $platforms = [
            ['name' => 'PlayStation 5', 'slug' => 'ps5', 'short_name' => 'PS5'],
            ['name' => 'PlayStation 4', 'slug' => 'ps4', 'short_name' => 'PS4'],
            ['name' => 'PlayStation 3', 'slug' => 'ps3', 'short_name' => 'PS3'],
            ['name' => 'PlayStation Vita', 'slug' => 'ps-vita', 'short_name' => 'PS Vita'],
            ['name' => 'PlayStation VR', 'slug' => 'ps-vr', 'short_name' => 'PSVR'],
            ['name' => 'PlayStation VR2', 'slug' => 'ps-vr2', 'short_name' => 'PSVR2'],
        ];

        foreach ($platforms as $platform) {
            Platform::firstOrCreate(
                ['slug' => $platform['slug']],
                $platform
            );
        }
    }
}
