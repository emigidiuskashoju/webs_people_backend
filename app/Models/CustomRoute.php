<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CustomRoute extends Model
{
    protected $fillable = [
        'location_request_id',
        'owner_id',
        'recipient_id',
        'points',
        'total_distance_meters',
    ];

    protected $casts = [
        'points' => 'array',
        'total_distance_meters' => 'float',
    ];

    public function locationRequest(): BelongsTo
    {
        return $this->belongsTo(
            LocationRequest::class
        );
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'owner_id'
        );
    }

    public function recipient(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'recipient_id'
        );
    }
}