<?php

namespace App\Services;

use App\Models\Game;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

class GameFilterService
{
    /**
     * Apply filters to a game query
     *
     * @param Builder $query
     * @param Request $request
     * @param bool $isAdmin Whether to include admin-only filters
     * @return Builder
     */
    public function applyFilters(Builder $query, Request $request, bool $isAdmin = false): Builder
    {
        // My Library filter (restrict to user's game list)
        $this->applyMyLibraryFilter($query, $request);

        // PSN library filter (restrict to specific game IDs)
        $this->applyGameIdsFilter($query, $request);

        // Common filters (available to all)
        $this->applySearchFilter($query, $request);
        $this->applyRelationshipFilters($query, $request);
        $this->applyDifficultyFilters($query, $request);
        $this->applyTimeFilters($query, $request);
        $this->applyPlaythroughFilter($query, $request);
        $this->applyScoreFilter($query, $request);
        $this->applyBooleanFilters($query, $request);

        // Admin-only filters
        if ($isAdmin) {
            $this->applyAdminFilters($query, $request);
        }

        // Apply sorting
        $this->applySorting($query, $request);

        return $query;
    }

    /**
     * Filter to only games in the authenticated user's library
     */
    protected function applyMyLibraryFilter(Builder $query, Request $request): void
    {
        if ($request->filled('my_library') && $this->isTruthy($request->my_library)) {
            $user = $request->user();
            if ($user) {
                $userGameIds = $user->games()->pluck('game_id')->toArray();
                $query->whereIn('id', $userGameIds);
            } else {
                // Not authenticated - return no results
                $query->whereRaw('1 = 0');
            }
        }
    }

    /**
     * Filter by specific game IDs (for PSN library filtering)
     */
    protected function applyGameIdsFilter(Builder $query, Request $request): void
    {
        if ($request->filled('game_ids')) {
            $gameIds = $request->game_ids;
            // Handle both array and comma-separated string
            if (is_string($gameIds)) {
                $gameIds = explode(',', $gameIds);
            }
            $query->whereIn('id', $gameIds);
        }
    }

    /**
     * Search filter (title and slug)
     */
    protected function applySearchFilter(Builder $query, Request $request): void
    {
        if ($request->filled('search')) {
            $searchTerm = $request->search;
            $query->where(function ($q) use ($searchTerm) {
                $q->where('title', 'LIKE', '%' . $searchTerm . '%')
                  ->orWhere('slug', 'LIKE', '%' . $searchTerm . '%');
            });
        }
    }

    /**
     * Genre, tag, and platform relationship filters
     */
    protected function applyRelationshipFilters(Builder $query, Request $request): void
    {
        if ($request->filled('genre_ids')) {
            $query->whereHas('genres', function ($q) use ($request) {
                $q->whereIn('genres.id', $request->genre_ids);
            });
        }

        if ($request->filled('tag_ids')) {
            $query->whereHas('tags', function ($q) use ($request) {
                $q->whereIn('tags.id', $request->tag_ids);
            });
        }

        if ($request->filled('platform_ids')) {
            $query->whereHas('platforms', function ($q) use ($request) {
                $q->whereIn('platforms.id', $request->platform_ids);
            });
        }
    }

    /**
     * Difficulty range filters
     */
    protected function applyDifficultyFilters(Builder $query, Request $request): void
    {
        $diffMin = $request->input('difficulty_min');
        $diffMax = $request->input('difficulty_max');

        // Use simple WHERE for index usage — NULL values (unknown difficulty) pass through
        if ($request->filled('difficulty_min') && $diffMin > 1) {
            $query->where(function ($q) use ($diffMin) {
                $q->where('difficulty', '>=', $diffMin)
                  ->orWhereNull('difficulty');
            });
        }

        if ($request->filled('difficulty_max') && $diffMax < 10) {
            $query->where(function ($q) use ($diffMax) {
                $q->where('difficulty', '<=', $diffMax)
                  ->orWhereNull('difficulty');
            });
        }
    }

    /**
     * Time range filters
     */
    protected function applyTimeFilters(Builder $query, Request $request): void
    {
        $timeMin = $request->input('time_min');
        $timeMax = $request->input('time_max');

        if ($request->filled('time_min') && $timeMin > 0) {
            $query->where(function ($q) use ($timeMin) {
                $q->where('time_max', '>=', $timeMin)->orWhereNull('time_max');
            });
        }

        if ($request->filled('time_max') && $timeMax < 200) {
            $query->where(function ($q) use ($timeMax) {
                $q->where('time_min', '<=', $timeMax)->orWhereNull('time_min');
            });
        }
    }

    /**
     * Max playthroughs filter
     */
    protected function applyPlaythroughFilter(Builder $query, Request $request): void
    {
        if ($request->filled('max_playthroughs')) {
            $query->where('playthroughs_required', '<=', $request->max_playthroughs);
        }
    }

    /**
     * Score range filters (user score and critic score)
     */
    protected function applyScoreFilter(Builder $query, Request $request): void
    {
        // IGDB User Score range filter
        if ($request->filled('user_score_min') && $request->input('user_score_min') > 0) {
            $query->where('user_score', '>=', $request->input('user_score_min'));
        }
        if ($request->filled('user_score_max') && $request->input('user_score_max') < 100) {
            $query->where('user_score', '<=', $request->input('user_score_max'));
        }

        // IGDB Critic Score range filter
        if ($request->filled('critic_score_min') && $request->input('critic_score_min') > 0) {
            $query->where('critic_score', '>=', $request->input('critic_score_min'));
        }
        if ($request->filled('critic_score_max') && $request->input('critic_score_max') < 100) {
            $query->where('critic_score', '<=', $request->input('critic_score_max'));
        }

        // Legacy min_score filter (checks any score) - kept for backwards compatibility
        if ($request->filled('min_score')) {
            $query->where(function ($q) use ($request) {
                $q->where('critic_score', '>=', $request->min_score)
                  ->orWhere('user_score', '>=', $request->min_score)
                  ->orWhere('opencritic_score', '>=', $request->min_score);
            });
        }
    }

    /**
     * Boolean filters (online trophies, missable trophies, has guide)
     */
    protected function applyBooleanFilters(Builder $query, Request $request): void
    {
        if ($request->filled('has_online_trophies')) {
            $query->where('has_online_trophies', $this->isTruthy($request->has_online_trophies));
        }

        if ($request->filled('missable_trophies')) {
            $query->where('missable_trophies', $this->isTruthy($request->missable_trophies));
        }

        // Has platinum filter
        if ($request->filled('has_platinum')) {
            $query->where('has_platinum', $this->isTruthy($request->has_platinum));
        }

        // Has guide filter — uses indexed boolean column
        if ($request->filled('has_guide')) {
            $query->where('has_guide', $this->isTruthy($request->has_guide));
        }

        // Guide source filters (available to public)
        if ($request->filled('guide_psnp') && $this->isTruthy($request->guide_psnp)) {
            $query->whereNotNull('psnprofiles_url');
        }
        if ($request->filled('guide_pst') && $this->isTruthy($request->guide_pst)) {
            $query->whereNotNull('playstationtrophies_url');
        }
        if ($request->filled('guide_ppx') && $this->isTruthy($request->guide_ppx)) {
            $query->whereNotNull('powerpyx_url');
        }
    }

    /**
     * Admin-only filters
     */
    protected function applyAdminFilters(Builder $query, Request $request): void
    {
        // No genres/tags/platforms filters
        if ($request->filled('no_genres') && $this->isTruthy($request->no_genres)) {
            $query->doesntHave('genres');
        }
        if ($request->filled('no_tags') && $this->isTruthy($request->no_tags)) {
            $query->doesntHave('tags');
        }
        if ($request->filled('no_platforms') && $this->isTruthy($request->no_platforms)) {
            $query->doesntHave('platforms');
        }

        // Needs data filter (has guide but NO key fields filled)
        if ($request->filled('needs_data') && $this->isTruthy($request->needs_data)) {
            $query->where('has_guide', true)
                  ->whereNull('difficulty')
                  ->whereNull('time_min')
                  ->whereNull('playthroughs_required');
        }

        // Verified filter (show only verified games)
        if ($request->filled('is_verified') && $this->isTruthy($request->is_verified)) {
            $query->where('is_verified', true);
        }

        // PSNP only filter (has psnprofiles guide but no other guides)
        if ($request->filled('psnp_only') && $this->isTruthy($request->psnp_only)) {
            $query->whereNotNull('psnprofiles_url')
                  ->whereNull('playstationtrophies_url')
                  ->whereNull('powerpyx_url');
        }

        // Needs verification filter (has guide + not verified)
        if ($request->filled('needs_verification') && $this->isTruthy($request->needs_verification)) {
            $query->where('has_guide', true)
                  ->where(function ($q) {
                $q->where('is_verified', false)
                  ->orWhereNull('is_verified');
            });
        }

        // Semi filled filter (has guide and SOME but not ALL key fields filled)
        if ($request->filled('semi_filled') && $this->isTruthy($request->semi_filled)) {
            $query->where('has_guide', true)
                  ->where(function ($q) {
                // At least one field is filled
                $q->whereNotNull('difficulty')
                  ->orWhereNotNull('time_min')
                  ->orWhereNotNull('playthroughs_required');
            })->where(function ($q) {
                // But at least one field is still missing
                $q->whereNull('difficulty')
                  ->orWhereNull('time_min')
                  ->orWhereNull('playthroughs_required');
            });
        }
    }

    /**
     * Apply sorting to query
     */
    protected function applySorting(Builder $query, Request $request): void
    {
        $sortBy = $request->get('sort_by', 'created_at');
        $sortOrder = $request->get('sort_order', 'desc');

        // When searching, prioritize shorter titles (base games before DLC)
        // unless explicit sorting is requested
        if ($request->filled('search') && !$request->filled('sort_by')) {
            $query->orderByRaw('LENGTH(title) ASC');
            return;
        }

        // Handle NULL values for nullable sort columns - push NULLs to the end
        $nullableColumns = ['difficulty', 'time_min', 'time_max', 'critic_score', 'user_score', 'opencritic_score', 'release_date', 'playthroughs_required', 'missable_trophies', 'user_score_count'];

        if (in_array($sortBy, $nullableColumns)) {
            $query->orderByRaw("{$sortBy} IS NULL")
                  ->orderBy($sortBy, $sortOrder);
        } else {
            $query->orderBy($sortBy, $sortOrder);
        }
    }

    /**
     * Get paginated results
     */
    public function paginate(Builder $query, Request $request, int $defaultPerPage = 24, int $maxPerPage = 100): array
    {
        $perPage = min($request->input('per_page', $defaultPerPage), $maxPerPage);
        $games = $query->paginate($perPage);

        return [
            'data' => $games->items(),
            'total' => $games->total(),
            'current_page' => $games->currentPage(),
            'last_page' => $games->lastPage(),
        ];
    }

    /**
     * Check if a value is truthy (handles 'true', true, '1', 1)
     */
    protected function isTruthy($value): bool
    {
        return $value === 'true' || $value === true || $value === '1' || $value === 1;
    }
}
