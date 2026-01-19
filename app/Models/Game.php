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
        'title',
        'slug',
        'developer',
        'publisher',
        'release_date',
        'difficulty',
        'base_price',
        'psplus_price',
        'current_discount_price',
        'is_psplus_extra',
        'is_psplus_premium',
        'cover_url',
        'banner_url',
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
        'is_psplus_extra' => 'boolean',
        'is_psplus_premium' => 'boolean',
        'needs_review' => 'boolean',
        'last_scraped_at' => 'datetime',
        'base_price' => 'decimal:2',
        'psplus_price' => 'decimal:2',
        'current_discount_price' => 'decimal:2',
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
     * Get the best available score (metacritic or opencritic)
     */
    public function getBestScoreAttribute()
    {
        if ($this->metacritic_score && $this->opencritic_score) {
            return max($this->metacritic_score, $this->opencritic_score);
        }
        return $this->metacritic_score ?? $this->opencritic_score ?? null;
    }

    public function hasGuides(): bool
    {
        return $this->psnprofiles_url
            || $this->playstationtrophies_url
            || $this->powerpyx_url;
    }
}
