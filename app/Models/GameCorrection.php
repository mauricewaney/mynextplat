<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GameCorrection extends Model
{
    protected $fillable = [
        'game_id',
        'user_id',
        'category',
        'description',
        'source_url',
        'status',
        'admin_notes',
        'ip_address',
        'email',
        'resolved_at',
        'resolved_by',
    ];

    protected $casts = [
        'resolved_at' => 'datetime',
    ];

    public const CATEGORIES = [
        'trophy_data' => 'Trophy Data',
        'game_info' => 'Game Info',
        'guide_links' => 'Guide Links',
        'images' => 'Images',
        'other' => 'Other',
    ];

    public const STATUSES = [
        'pending' => 'Pending',
        'in_review' => 'In Review',
        'applied' => 'Applied',
        'rejected' => 'Rejected',
    ];

    public function game(): BelongsTo
    {
        return $this->belongsTo(Game::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function resolvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'resolved_by');
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeUnresolved($query)
    {
        return $query->whereIn('status', ['pending', 'in_review']);
    }

    public function getCategoryLabelAttribute(): string
    {
        return self::CATEGORIES[$this->category] ?? $this->category;
    }

    public function getStatusLabelAttribute(): string
    {
        return self::STATUSES[$this->status] ?? $this->status;
    }
}
