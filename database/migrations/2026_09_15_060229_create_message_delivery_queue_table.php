<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create(
            'message_delivery_queue',
            function (Blueprint $table) {
                $table->id();

                $table->string(
                    'client_message_id',
                    100
                )->unique();

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
            }
        );
    }

    public function down(): void
    {
        Schema::dropIfExists(
            'message_delivery_queue'
        );
    }
};