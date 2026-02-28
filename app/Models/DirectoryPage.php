<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DirectoryPage extends Model
{
    protected $fillable = [
        'directory_type',
        'slug',
        'intro_text',
        'featured_game_ids',
        'related_pages',
    ];

    protected $casts = [
        'featured_game_ids' => 'array',
        'related_pages' => 'array',
    ];

    public function sections(): HasMany
    {
        return $this->hasMany(DirectorySection::class)->orderBy('sort_order');
    }

    public static function findByKey(string $type, string $slug): ?self
    {
        return static::where('directory_type', $type)
            ->where('slug', $slug)
            ->first();
    }
}
