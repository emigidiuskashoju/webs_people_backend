<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create(
            'pending_call_events',
            function (Blueprint $table) {
                $table->id();

                $table->uuid('call_id');

                $table->foreignId('sender_id')
                    ->constrained('users')
                    ->cascadeOnDelete();

                $table->foreignId('recipient_id')
                    ->constrained('users')
                    ->cascadeOnDelete();

                $table->string(
                    'event',
                    30
                );

                $table->json('payload')
                    ->nullable();

                /*
                 * These timestamps are supplied explicitly
                 * by the application.
                 *
                 * We use nullable() so MySQL does not require
                 * a database-level default value.
                 */
                $table->timestamp('created_at')
                    ->nullable();

                $table->timestamp('expires_at')
                    ->nullable();

                $table->index([
                    'recipient_id',
                    'expires_at',
                ]);

                $table->index([
                    'call_id',
                    'created_at',
                ]);
            }
        );
    }

    public function down(): void
    {
        Schema::dropIfExists(
            'pending_call_events'
        );
    }
};