<?php

namespace App\Http\Controllers;

use App\Models\FeaturedPlacement;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;

class FeaturedPlacementController extends Controller
{
    public function index(): JsonResponse
    {
        $version = Cache::get('games:cache_version', 1);

        $placements = Cache::remember("featured_placements:v{$version}", 3600, function () {
            return FeaturedPlacement::active()
                ->with(['game:id,title,slug,cover_url,difficulty,time_min,time_max,psnprofiles_url,playstationtrophies_url,powerpyx_url'])
                ->limit(3)
                ->get()
                ->map(function ($placement) {
                    $game = $placement->game;
                    return [
                        'id' => $placement->id,
                        'label' => $placement->label,
                        'game' => [
                            'id' => $game->id,
                            'title' => $game->title,
                            'slug' => $game->slug,
                            'cover_url' => $game->cover_url,
                            'difficulty' => $game->difficulty,
                            'time_range' => $game->time_range,
                            'has_psnprofiles' => (bool) $game->psnprofiles_url,
                            'has_playstationtrophies' => (bool) $game->playstationtrophies_url,
                            'has_powerpyx' => (bool) $game->powerpyx_url,
                        ],
                    ];
                });
        });

        return response()->json($placements);
    }
}
