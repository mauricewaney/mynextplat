<?php

namespace App\Services;

use App\Models\DirectorySection;
use App\Models\Game;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class DirectorySectionService
{
    public function __construct(
        private GameFilterService $gameFilterService
    ) {}

    /**
     * Resolve the games for a section based on its configuration.
     * - Manual only (game_ids set, filter_definition null): pinned games in order
     * - Filter only (filter_definition set, game_ids null): run filter, take limit
     * - Hybrid (both set): pinned first, filter fills remaining up to limit
     */
    public function resolveSection(DirectorySection $section): Collection
    {
        $pinnedIds = $section->game_ids ?? [];
        $limit = $section->limit ?? 6;

        // Load pinned games preserving order
        $pinnedGames = collect();
        if (!empty($pinnedIds)) {
            $pinned = Game::with(['genres:id,name,slug', 'platforms:id,name,slug,short_name'])
                ->whereIn('id', $pinnedIds)
                ->get()
                ->keyBy('id');

            // Preserve the order from game_ids
            foreach ($pinnedIds as $id) {
                if ($pinned->has($id)) {
                    $pinnedGames->push($pinned->get($id));
                }
            }
        }

        // If no filter definition, return pinned only
        if (empty($section->filter_definition)) {
            return $pinnedGames->take($limit);
        }

        // Run filter to fill remaining slots
        $remaining = $limit - $pinnedGames->count();
        if ($remaining <= 0) {
            return $pinnedGames->take($limit);
        }

        $syntheticRequest = Request::create('/', 'GET', $section->filter_definition);
        $query = Game::with(['genres:id,name,slug', 'platforms:id,name,slug,short_name']);
        $this->gameFilterService->applyFilters($query, $syntheticRequest);

        // Exclude pinned IDs from filter results
        if (!empty($pinnedIds)) {
            $query->whereNotIn('id', $pinnedIds);
        }

        $filteredGames = $query
            ->orderByRaw('critic_score IS NULL')
            ->orderBy('critic_score', 'desc')
            ->limit($remaining)
            ->get();

        return $pinnedGames->concat($filteredGames);
    }

    /**
     * Compute aggregate stats from a collection of games.
     */
    public function computeStats(Collection $games): array
    {
        $withDifficulty = $games->whereNotNull('difficulty');
        $withTime = $games->filter(fn ($g) => $g->time_min !== null || $g->time_max !== null);
        $withOnline = $games->whereNotNull('has_online_trophies');

        return [
            'avg_difficulty' => $withDifficulty->count() >= 3
                ? round($withDifficulty->avg('difficulty'), 1)
                : null,
            'avg_time' => $withTime->count() >= 3
                ? round($withTime->avg(fn ($g) => ($g->time_min ?? 0) + ($g->time_max ?? $g->time_min ?? 0)) / 2)
                : null,
            'pct_no_online' => $withOnline->count() >= 3
                ? round($withOnline->where('has_online_trophies', false)->count() / $withOnline->count() * 100)
                : null,
        ];
    }
}
