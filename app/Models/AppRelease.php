<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AppRelease extends Model
{
    protected $fillable = [
        'version_code',
        'version_name',
        'download_url',
        'release_notes',
        'critical',
    ];

    protected $casts = [
        'version_code' => 'integer',
        'critical' => 'boolean',
    ];
}