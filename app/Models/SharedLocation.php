<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SharedLocation extends Model
{
    use HasFactory;

    protected $fillable = [
        'location_request_id',
        'user_id',
        'latitude',
        'longitude',
        'accuracy',
        'location_updated_at',
    ];

    protected $casts = [
        'latitude' => 'float',
        'longitude' => 'float',
        'accuracy' => 'float',
        'location_updated_at' => 'datetime',
    ];

    public function locationRequest()
    {
        return $this->belongsTo(
            LocationRequest::class,
            'location_request_id'
        );
    }

    public function user()
    {
        return $this->belongsTo(
            User::class,
            'user_id'
        );
    }
}