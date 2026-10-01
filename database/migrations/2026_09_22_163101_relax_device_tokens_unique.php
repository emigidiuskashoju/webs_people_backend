<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('device_tokens', function (Blueprint $table) {
            // Drop the single-column unique index on fcm_token.
            // It prevents the same physical device from being
            // registered against two accounts.
            $table->dropUnique(['fcm_token']);

            // Add a composite unique index so a single
            // (user, token) pair stays unique, but the same
            // token can exist for different users.
            $table->unique(
                ['user_id', 'fcm_token'],
                'device_tokens_user_token_unique'
            );
        });
    }

    public function down(): void
    {
        Schema::table('device_tokens', function (Blueprint $table) {
            $table->dropUnique('device_tokens_user_token_unique');
            $table->unique('fcm_token');
        });
    }
};