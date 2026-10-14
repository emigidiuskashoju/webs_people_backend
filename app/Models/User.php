<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'phone_number',
        'password',
        'profile_photo_path',
    ];

    protected $hidden = [
        'password',
        'track_security_password',
        'remember_token',
    ];

    /**
     * Fields automatically appended to every JSON representation.
     */
    protected $appends = [
        'profile_photo_url',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',

            'phone_verified_at' => 'datetime',

            'password' => 'hashed',
        ];
    }

    /**
     * Full public URL of the user's profile photo.
     * Returns null if no photo has been uploaded.
     */
    public function getProfilePhotoUrlAttribute(): ?string
    {
        if (empty($this->profile_photo_path)) {
            return null;
        }

        return url(
            '/storage/' .
            ltrim($this->profile_photo_path, '/')
        );
    }

    public function devices(): HasMany
    {
        return $this->hasMany(
            Device::class
        );
    }
}


