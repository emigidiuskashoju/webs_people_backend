<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create(
            'pending_read_receipts',
            function (Blueprint $table) {
                $table->id();

                $table->foreignId('message_id')
                    ->constrained(
                        'pending_messages'
                    )
                    ->cascadeOnDelete();

                $table->foreignId('sender_id')
                    ->constrained('users')
                    ->cascadeOnDelete();

                $table->foreignId('reader_id')
                    ->constrained('users')
                    ->cascadeOnDelete();

                $table->timestamp('created_at');

                $table->unique([
                    'message_id',
                    'reader_id',
                ]);

                $table->index([
                    'sender_id',
                    'id',
                ]);
            }
        );
    }

    public function down(): void
    {
        Schema::dropIfExists(
            'pending_read_receipts'
        );
    }
};