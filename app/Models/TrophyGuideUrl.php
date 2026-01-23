<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TrophyGuideUrl extends Model
{
    protected $fillable = [
        'source',
        'url',
        'extracted_slug',
        'extracted_title',
        'game_id',
        'matched_at',
    ];

    protected $casts = [
        'matched_at' => 'datetime',
    ];

    public function game(): BelongsTo
    {
        return $this->belongsTo(Game::class);
    }

    public function scopeUnmatched($query)
    {
        return $query->whereNull('game_id');
    }

    public function scopeMatched($query)
    {
        return $query->whereNotNull('game_id');
    }

    public function scopeSource($query, string $source)
    {
        return $query->where('source', $source);
    }
}
