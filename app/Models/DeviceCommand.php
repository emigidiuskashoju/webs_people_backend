<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DeviceCommand extends Model
{
    use HasFactory;

    protected $fillable = [
        'device_id',
        'command',
        'payload',
        'status',
        'expires_at',
        'acknowledged_at',
    ];

    protected function casts(): array
    {
        return [
            'payload' =>
                'array',

            'expires_at' =>
                'datetime',

            'acknowledged_at' =>
                'datetime',
        ];
    }

    public function device(): BelongsTo
    {
        return $this->belongsTo(
            Device::class,
            'device_id'
        );
    }
}