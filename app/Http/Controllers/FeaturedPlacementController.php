<?php

namespace App\Http\Controllers;

use App\Models\FeaturedPlacement;
use App\Models\SiteSetting;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;

class FeaturedPlacementController extends Controller
{
    public function index(): JsonResponse
    {
        $version = Cache::get('games:cache_version', 1);

        $placements = Cache::remember("featured_placements:v{$version}", 300, function () {
            $all = FeaturedPlacement::active()
                ->with(['game:id,title,slug,cover_url,base_price,current_discount_price,psplus_price,is_psplus_extra,is_psplus_premium,has_platinum,bronze_count,silver_count,gold_count,platinum_count,psn_store_url'])
                ->get();

            if ($all->isEmpty()) {
                return null;
            }

            $selected = $this->weightedRandomSelect($all, min(3, $all->count()));

            // Increment impressions for selected placements
            FeaturedPlacement::whereIn('id', $selected->pluck('id'))->increment('impressions');

            // Shuffle so no game is always in slot 1
            return $selected->shuffle()->values()->map(function ($placement) {
                $game = $placement->game;
                $totalTrophies = ($game->bronze_count ?? 0) + ($game->silver_count ?? 0)
                    + ($game->gold_count ?? 0) + ($game->platinum_count ?? 0);

                return [
                    'id' => $placement->id,
                    'tagline' => $placement->tagline,
                    'game' => [
                        'id' => $game->id,
                        'title' => $game->title,
                        'slug' => $game->slug,
                        'cover_url' => $game->cover_url,
                        'base_price' => $game->base_price,
                        'current_discount_price' => $game->current_discount_price,
                        'psplus_price' => $game->psplus_price,
                        'is_psplus_extra' => (bool) $game->is_psplus_extra,
                        'is_psplus_premium' => (bool) $game->is_psplus_premium,
                        'has_platinum' => (bool) $game->has_platinum,
                        'bronze_count' => $game->bronze_count ?? 0,
                        'silver_count' => $game->silver_count ?? 0,
                        'gold_count' => $game->gold_count ?? 0,
                        'platinum_count' => $game->platinum_count ?? 0,
                        'total_trophies' => $totalTrophies,
                        'psn_store_url' => $game->psn_store_url,
                    ],
                ];
            });
        });

        if (!$placements) {
            return response()->json(['label' => '', 'games' => []]);
        }

        return response()->json([
            'label' => SiteSetting::get('featured_label', 'Featured'),
            'games' => $placements,
        ]);
    }

    /**
     * Select $count items from $collection, weighted by inverse impressions.
     * Placements with fewer impressions get higher selection weight.
     */
    private function weightedRandomSelect($collection, int $count)
    {
        if ($collection->count() <= $count) {
            return $collection;
        }

        $maxImpressions = $collection->max('impressions') ?: 1;
        $selected = collect();

        $pool = $collection->values()->all();

        for ($i = 0; $i < $count; $i++) {
            // Calculate weights: inverse of impressions ratio
            $weights = array_map(function ($item) use ($maxImpressions) {
                return $maxImpressions - $item->impressions + 1;
            }, $pool);

            $totalWeight = array_sum($weights);
            $random = mt_rand(1, $totalWeight);

            $cumulative = 0;
            foreach ($pool as $idx => $item) {
                $cumulative += $weights[$idx];
                if ($random <= $cumulative) {
                    $selected->push($item);
                    array_splice($pool, $idx, 1);
                    break;
                }
            }
        }

        return $selected;
    }
}
