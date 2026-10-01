<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('location_requests', function (Blueprint $table) {
            $table->id();

            $table->foreignId('requester_id')
                ->constrained('users')
                ->cascadeOnDelete();

            $table->foreignId('owner_id')
                ->constrained('users')
                ->cascadeOnDelete();

            $table->string('status', 20)
                ->default('pending');

            $table->timestamp('expires_at');

            $table->timestamp('responded_at')
                ->nullable();

            $table->timestamps();

            $table->index([
                'owner_id',
                'status',
            ]);

            $table->index([
                'requester_id',
                'status',
            ]);

            $table->index('expires_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('location_requests');
    }
};