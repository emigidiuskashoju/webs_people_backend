<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create(
            'call_signaling',
            function (Blueprint $table) {
                $table->id();

                $table->string(
                    'call_id',
                    100
                );

                $table->foreignId(
                    'sender_id'
                )
                    ->constrained(
                        'users'
                    )
                    ->cascadeOnDelete();

                $table->foreignId(
                    'receiver_id'
                )
                    ->constrained(
                        'users'
                    )
                    ->cascadeOnDelete();

                $table->string(
                    'type',
                    30
                );

                $table->text(
                    'payload'
                );

                $table->timestamp(
                    'expires_at'
                );

                $table->timestamps();

                $table->index([
                    'receiver_id',
                    'expires_at',
                ]);

                $table->index(
                    'call_id'
                );
            }
        );
    }

    public function down(): void
    {
        Schema::dropIfExists(
            'call_signaling'
        );
    }
};