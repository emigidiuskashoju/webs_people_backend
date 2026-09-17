<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CallSignaling extends Model
{
    use HasFactory;

    protected $table =
        'call_signaling';

    protected $fillable = [
        'call_id',
        'sender_id',
        'receiver_id',
        'type',
        'payload',
        'expires_at',
    ];

    protected function casts(): array
    {
        return [
            'expires_at' => 'datetime',
        ];
    }
}