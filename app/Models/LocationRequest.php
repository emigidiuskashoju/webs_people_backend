<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LocationRequest extends Model
{
    use HasFactory;

    protected $fillable = [
        'requester_id',
        'owner_id',
        'status',
        'expires_at',
        'latitude',
        'longitude',
        'accuracy',
        'location_updated_at',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
        'location_updated_at' => 'datetime',
        'latitude' => 'float',
        'longitude' => 'float',
        'accuracy' => 'float',
    ];

    public function requester()
    {
        return $this->belongsTo(
            User::class,
            'requester_id'
        );
    }

    public function owner()
    {
        return $this->belongsTo(
            User::class,
            'owner_id'
        );
    }

    /**
     * Every user who has posted a location on this request.
     *
     * In two-way mode this is two rows (requester and owner).
     */
    public function sharedLocations(): HasMany
    {
        return $this->hasMany(
            SharedLocation::class,
            'location_request_id'
        );
    }

    /**
     * Return the shared location row for a specific user,
     * if one exists.
     */
    public function sharedLocationFor(
        int $userId
    ): ?SharedLocation {
        return $this->sharedLocations
            ->firstWhere('user_id', $userId);
    }

    /**
     * Return the shared location row that is NOT the given
     * user's — i.e. "the other person's current location".
     */
    public function otherSharedLocationFor(
        int $userId
    ): ?SharedLocation {
        return $this->sharedLocations
            ->firstWhere(
                fn (SharedLocation $row) =>
                    $row->user_id !== $userId
            );
    }
}