<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'google_id',
        'avatar',
        'is_admin',
        'notify_new_guides',
        'last_notified_at',
        'profile_public',
        'profile_slug',
        'profile_name',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_admin' => 'boolean',
            'notify_new_guides' => 'boolean',
            'last_notified_at' => 'datetime',
            'profile_public' => 'boolean',
        ];
    }

    /**
     * Get the profile URL identifier (slug or ID).
     */
    public function getProfileIdentifier(): string
    {
        return $this->profile_slug ?? (string) $this->id;
    }

    /**
     * Generate a unique profile slug from the user's profile name.
     */
    public function generateProfileSlug(): string
    {
        $base = \Illuminate\Support\Str::slug($this->profile_name ?: $this->name);
        if (empty($base)) {
            $base = 'user';
        }

        $slug = $base;
        $counter = 1;

        while (static::where('profile_slug', $slug)->where('id', '!=', $this->id)->exists()) {
            $slug = $base . '-' . $counter;
            $counter++;
        }

        return $slug;
    }

    /**
     * Get the games in this user's list.
     */
    public function games(): BelongsToMany
    {
        return $this->belongsToMany(Game::class, 'user_game')
            ->withPivot(['status', 'notes', 'guide_notified_at', 'preferred_guide'])
            ->withTimestamps();
    }

    /**
     * Check if the user is an admin.
     */
    public function isAdmin(): bool
    {
        return $this->is_admin === true;
    }
}
