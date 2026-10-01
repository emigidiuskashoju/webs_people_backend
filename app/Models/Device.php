<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Device extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'device_uuid',
        'device_secret_hash',
        'name',
        'platform',
        'model',
        'manufacturer',
        'os_version',
        'app_version',
        'battery_level',
        'is_charging',
        'network_type',
        'location_enabled',
        'last_seen_at',
        'is_active',
        'is_lost',
    ];

    protected function casts(): array
    {
        return [
            'battery_level' => 'integer',
            'is_charging' => 'boolean',
            'location_enabled' => 'boolean',
            'last_seen_at' => 'datetime',
            'is_active' => 'boolean',
            'is_lost' => 'boolean',
        ];
    }

    // ============================================================
    // USER
    // ============================================================

    public function user(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'user_id'
        );
    }

    // ============================================================
    // ALL LOCATIONS
    // ============================================================

    public function locations(): HasMany
    {
        return $this->hasMany(
            DeviceLocation::class,
            'device_id'
        );
    }

    // ============================================================
    // LATEST LOCATION
    // ============================================================

    public function latestLocation(): HasOne
    {
        return $this->hasOne(
            DeviceLocation::class,
            'device_id'
        )->latestOfMany('recorded_at');
    }

    // ============================================================
    // DEVICE COMMANDS
    // ============================================================

    public function commands(): HasMany
    {
        return $this->hasMany(
            DeviceCommand::class,
            'device_id'
        );
    }
}