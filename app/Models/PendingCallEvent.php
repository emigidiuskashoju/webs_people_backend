<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PendingCallEvent extends Model
{
    use HasFactory;

    protected $fillable = [
        'call_id',
        'sender_id',
        'recipient_id',
        'event',
        'payload',
        'created_at',
        'expires_at',
    ];

    protected function casts(): array
    {
        return [
            'payload' =>
                'array',

            'created_at' =>
                'datetime',

            'expires_at' =>
                'datetime',
        ];
    }

    public $timestamps = false;
}