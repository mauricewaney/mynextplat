<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Game extends Model
{
    use HasFactory, SoftDeletes;

    protected $appends = [
        'time_range',
        'best_score',  // Als je deze accessor ook hebt
    ];

    protected $fillable = [
        'igdb_id',
        'np_communication_ids',
        'title',
        'slug',
        'developer',
        'publisher',
        'release_date',
        'difficulty',
        'time_min',
        'time_max',
        'playthroughs_required',
        'has_online_trophies',
        'missable_trophies',
        'is_unobtainable',
        'bronze_count',
        'silver_count',
        'gold_count',
        'platinum_count',
        'has_platinum',
        'critic_score',
        'critic_score_count',
        'user_score',
        'user_score_count',
        'opencritic_score',
        'base_price',
        'psplus_price',
        'current_discount_price',
        'is_psplus_extra',
        'is_psplus_premium',
        'cover_url',
        'banner_url',
        'trophy_icon_url',
        'psnprofiles_url',
        'playstationtrophies_url',
        'powerpyx_url',
        'last_scraped_at',
        'needs_review',
    ];

    protected $casts = [
        'release_date' => 'date',
        'has_online_trophies' => 'boolean',
        'missable_trophies' => 'boolean',
        'is_unobtainable' => 'boolean',
        'has_platinum' => 'boolean',
        'is_psplus_extra' => 'boolean',
        'is_psplus_premium' => 'boolean',
        'needs_review' => 'boolean',
        'last_scraped_at' => 'datetime',
        'base_price' => 'decimal:2',
        'psplus_price' => 'decimal:2',
        'current_discount_price' => 'decimal:2',
        'np_communication_ids' => 'array',
        'bronze_count' => 'integer',
        'silver_count' => 'integer',
        'gold_count' => 'integer',
        'platinum_count' => 'integer',
    ];

    // Relationships
    public function genres(): BelongsToMany
    {
        return $this->belongsToMany(Genre::class);
    }

    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(Tag::class);
    }

    public function platforms(): BelongsToMany
    {
        return $this->belongsToMany(Platform::class);
    }

    /**
     * Get users who have this game in their list.
     */
    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'user_game')
            ->withPivot(['status', 'notes'])
            ->withTimestamps();
    }

    /**
     * Get all PSN titles linked to this game
     */
    public function psnTitles(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(PsnTitle::class);
    }

    // Helper methods
    public function getTimeRangeAttribute(): string
    {
        if (!$this->time_min && !$this->time_max) {
            return 'Unknown';
        }

        if ($this->time_min === $this->time_max) {
            return "{$this->time_min}h";
        }

        return "{$this->time_min}-{$this->time_max}h";
    }

    /**
     * Get the best available score (critic or opencritic)
     */
    public function getBestScoreAttribute()
    {
        if ($this->critic_score && $this->opencritic_score) {
            return max($this->critic_score, $this->opencritic_score);
        }
        return $this->critic_score ?? $this->opencritic_score ?? null;
    }

    public function hasGuides(): bool
    {
        return $this->psnprofiles_url
            || $this->playstationtrophies_url
            || $this->powerpyx_url;
    }

    /**
     * Get total trophy count
     */
    public function getTotalTrophiesAttribute(): int
    {
        return ($this->bronze_count ?? 0)
            + ($this->silver_count ?? 0)
            + ($this->gold_count ?? 0)
            + ($this->platinum_count ?? 0);
    }

    /**
     * Get the best available image (cover or trophy icon fallback)
     */
    public function getImageUrlAttribute(): ?string
    {
        return $this->cover_url ?? $this->trophy_icon_url;
    }

    /**
     * Sync trophy data from a linked PSN title
     * Only updates fields that are currently empty
     */
    public function syncFromPsnTitle(PsnTitle $psnTitle): void
    {
        $updated = false;

        // Sync trophy counts if not set
        if ($this->bronze_count === null && $psnTitle->bronze_count !== null) {
            $this->bronze_count = $psnTitle->bronze_count;
            $updated = true;
        }
        if ($this->silver_count === null && $psnTitle->silver_count !== null) {
            $this->silver_count = $psnTitle->silver_count;
            $updated = true;
        }
        if ($this->gold_count === null && $psnTitle->gold_count !== null) {
            $this->gold_count = $psnTitle->gold_count;
            $updated = true;
        }

        // Sync has_platinum
        if (!$this->has_platinum && $psnTitle->has_platinum) {
            $this->has_platinum = true;
            $this->platinum_count = 1;
            $updated = true;
        }

        // Use trophy icon as fallback if no cover
        if (!$this->cover_url && !$this->trophy_icon_url && $psnTitle->icon_url) {
            $this->trophy_icon_url = $psnTitle->icon_url;
            $updated = true;
        }

        // Try to sync platform from PSN title
        if ($psnTitle->platform) {
            $this->syncPlatformFromPsn($psnTitle->platform);
        }

        if ($updated) {
            $this->save();
        }
    }

    /**
     * Get recommended games based on collaborative filtering (co-occurrence)
     * "Players who have this game also have..."
     *
     * @param int $limit Maximum number of recommendations
     * @param int $minOverlap Minimum number of users that must have both games
     * @return \Illuminate\Support\Collection
     */
    public function getRecommendations(int $limit = 6, int $minOverlap = 2): \Illuminate\Support\Collection
    {
        // Get total users who have this game (for percentage calculation)
        $totalUsersWithGame = $this->users()->count();

        if ($totalUsersWithGame < $minOverlap) {
            return collect([]);
        }

        // Find games that co-occur in user libraries
        $recommendations = \DB::table('user_game as ug1')
            ->join('user_game as ug2', function ($join) {
                $join->on('ug1.user_id', '=', 'ug2.user_id')
                     ->whereColumn('ug1.game_id', '!=', 'ug2.game_id');
            })
            ->join('games', 'games.id', '=', 'ug2.game_id')
            ->where('ug1.game_id', $this->id)
            ->whereNull('games.deleted_at')
            ->select(
                'ug2.game_id',
                'games.title',
                'games.slug',
                'games.cover_url',
                'games.difficulty',
                'games.time_min',
                'games.time_max',
                'games.psnprofiles_url',
                'games.playstationtrophies_url',
                'games.powerpyx_url',
                \DB::raw('COUNT(*) as overlap_count')
            )
            ->groupBy(
                'ug2.game_id',
                'games.title',
                'games.slug',
                'games.cover_url',
                'games.difficulty',
                'games.time_min',
                'games.time_max',
                'games.psnprofiles_url',
                'games.playstationtrophies_url',
                'games.powerpyx_url'
            )
            ->having('overlap_count', '>=', $minOverlap)
            ->orderByDesc('overlap_count')
            ->limit($limit)
            ->get();

        // Calculate percentage and add has_guide flag
        return $recommendations->map(function ($rec) use ($totalUsersWithGame) {
            $rec->percentage = round(($rec->overlap_count / $totalUsersWithGame) * 100);
            $rec->has_guide = !empty($rec->psnprofiles_url)
                || !empty($rec->playstationtrophies_url)
                || !empty($rec->powerpyx_url);
            return $rec;
        });
    }

    /**
     * Add platform based on PSN platform string
     */
    protected function syncPlatformFromPsn(string $psnPlatform): void
    {
        $platformMap = [
            'PS5' => ['slug' => 'ps5', 'name' => 'PlayStation 5', 'short_name' => 'PS5'],
            'PS4' => ['slug' => 'ps4', 'name' => 'PlayStation 4', 'short_name' => 'PS4'],
            'PS3' => ['slug' => 'ps3', 'name' => 'PlayStation 3', 'short_name' => 'PS3'],
            'PSVITA' => ['slug' => 'ps-vita', 'name' => 'PlayStation Vita', 'short_name' => 'Vita'],
            'VITA' => ['slug' => 'ps-vita', 'name' => 'PlayStation Vita', 'short_name' => 'Vita'],
        ];

        $platformData = $platformMap[strtoupper($psnPlatform)] ?? null;
        if (!$platformData) {
            return;
        }

        $platform = Platform::firstOrCreate(
            ['slug' => $platformData['slug']],
            ['name' => $platformData['name'], 'short_name' => $platformData['short_name']]
        );

        // Attach if not already attached
        if (!$this->platforms()->where('platform_id', $platform->id)->exists()) {
            $this->platforms()->attach($platform->id);
        }
    }
}
