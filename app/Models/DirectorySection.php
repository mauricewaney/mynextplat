<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DirectorySection extends Model
{
    protected $fillable = [
        'directory_page_id',
        'title',
        'sort_order',
        'filter_definition',
        'game_ids',
        'limit',
    ];

    protected $casts = [
        'filter_definition' => 'array',
        'game_ids' => 'array',
        'limit' => 'integer',
    ];

    public function directoryPage(): BelongsTo
    {
        return $this->belongsTo(DirectoryPage::class);
    }
}
