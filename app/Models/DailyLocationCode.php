<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DailyLocationCode extends Model
{
    protected $fillable = [
        'user_id',
        'code_hash',
        'code_date',
        'expires_at',
    ];

    protected $casts = [
        'code_date' => 'date',
        'expires_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}