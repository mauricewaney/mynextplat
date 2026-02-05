<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ContactMessage extends Model
{
    use HasFactory;

    const CATEGORIES = [
        'general' => 'General',
        'bug_report' => 'Bug Report',
        'suggestion' => 'Suggestion',
        'other' => 'Other',
    ];

    const STATUSES = [
        'pending' => 'Pending',
        'read' => 'Read',
        'replied' => 'Replied',
        'closed' => 'Closed',
    ];

    protected $fillable = [
        'user_id',
        'name',
        'email',
        'category',
        'subject',
        'message',
        'status',
        'ip_address',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }
}
