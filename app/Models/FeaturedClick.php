<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FeaturedClick extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'placement_id',
        'game_id',
        'user_id',
        'clicked_at',
    ];

    protected $casts = [
        'clicked_at' => 'datetime',
    ];
}
