<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GuideClick extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'game_id',
        'guide_source',
        'clicked_at',
    ];

    protected $casts = [
        'clicked_at' => 'datetime',
    ];
}
