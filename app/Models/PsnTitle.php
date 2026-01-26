<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PsnTitle extends Model
{
    protected $fillable = [
        'np_communication_id',
        'psn_title',
        'platform',
        'icon_url',
        'game_id',
        'discovered_from',
        'times_seen',
        'skipped_at',
        'bronze_count',
        'silver_count',
        'gold_count',
        'has_platinum',
    ];

    protected $casts = [
        'times_seen' => 'integer',
        'bronze_count' => 'integer',
        'silver_count' => 'integer',
        'gold_count' => 'integer',
        'has_platinum' => 'boolean',
        'skipped_at' => 'datetime',
    ];

    /**
     * Get the linked game (if matched)
     */
    public function game(): BelongsTo
    {
        return $this->belongsTo(Game::class);
    }

    /**
     * Scope: only unmatched titles
     */
    public function scopeUnmatched($query)
    {
        return $query->whereNull('game_id');
    }

    /**
     * Scope: only matched titles
     */
    public function scopeMatched($query)
    {
        return $query->whereNotNull('game_id');
    }

    /**
     * Scope: order by popularity (times_seen)
     */
    public function scopePopular($query)
    {
        return $query->orderByDesc('times_seen');
    }

    /**
     * Scope: filter by platform
     */
    public function scopeForPlatform($query, string $platform)
    {
        return $query->where('platform', $platform);
    }

    /**
     * Scope: not skipped
     */
    public function scopeNotSkipped($query)
    {
        return $query->whereNull('skipped_at');
    }

    /**
     * Scope: skipped
     */
    public function scopeSkipped($query)
    {
        return $query->whereNotNull('skipped_at');
    }

    /**
     * Scope: order with skipped at end
     */
    public function scopeSkippedLast($query)
    {
        return $query->orderByRaw('skipped_at IS NOT NULL')->orderBy('skipped_at');
    }

    /**
     * Check if this title is skipped
     */
    public function isSkipped(): bool
    {
        return $this->skipped_at !== null;
    }

    /**
     * Skip this title
     */
    public function skip(): void
    {
        $this->skipped_at = now();
        $this->save();
    }

    /**
     * Unskip this title
     */
    public function unskip(): void
    {
        $this->skipped_at = null;
        $this->save();
    }

    /**
     * Check if this title is matched to a game
     */
    public function isMatched(): bool
    {
        return $this->game_id !== null;
    }

    /**
     * Get total trophy count
     */
    public function getTotalTrophiesAttribute(): int
    {
        return ($this->bronze_count ?? 0)
            + ($this->silver_count ?? 0)
            + ($this->gold_count ?? 0)
            + ($this->has_platinum ? 1 : 0);
    }

    /**
     * Record that this title was seen by another user
     */
    public function incrementSeen(): void
    {
        $this->increment('times_seen');
    }

    /**
     * Create or update a PSN title from trophy data
     */
    public static function upsertFromTrophy(array $trophyData, string $discoveredFrom): self
    {
        $npId = $trophyData['npCommunicationId'] ?? null;

        if (!$npId) {
            throw new \InvalidArgumentException('Trophy data missing npCommunicationId');
        }

        $existing = self::where('np_communication_id', $npId)->first();

        if ($existing) {
            // Already exists - just increment seen count
            $existing->incrementSeen();
            return $existing;
        }

        // Create new record
        $defined = $trophyData['definedTrophies'] ?? [];

        return self::create([
            'np_communication_id' => $npId,
            'psn_title' => $trophyData['trophyTitleName'] ?? 'Unknown',
            'platform' => $trophyData['trophyTitlePlatform'] ?? null,
            'icon_url' => $trophyData['trophyTitleIconUrl'] ?? null,
            'discovered_from' => $discoveredFrom,
            'times_seen' => 1,
            'bronze_count' => $defined['bronze'] ?? null,
            'silver_count' => $defined['silver'] ?? null,
            'gold_count' => $defined['gold'] ?? null,
            'has_platinum' => ($defined['platinum'] ?? 0) > 0,
        ]);
    }

    /**
     * Link this PSN title to a game
     */
    public function linkToGame(Game $game): void
    {
        $this->game_id = $game->id;
        $this->save();

        // Also add NP ID to game's np_communication_ids array
        $ids = $game->np_communication_ids ?? [];
        if (!in_array($this->np_communication_id, $ids)) {
            $ids[] = $this->np_communication_id;
            $game->np_communication_ids = $ids;
            $game->save();
        }

        // Sync trophy data to the game
        $game->syncFromPsnTitle($this);
    }

    /**
     * Unlink this PSN title from its game
     */
    public function unlinkFromGame(): void
    {
        if ($this->game_id && $this->game) {
            // Remove NP ID from game's array
            $ids = $this->game->np_communication_ids ?? [];
            $ids = array_values(array_filter($ids, fn($id) => $id !== $this->np_communication_id));
            $this->game->np_communication_ids = empty($ids) ? null : $ids;
            $this->game->save();
        }

        $this->game_id = null;
        $this->save();
    }
}
