<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('recovery_events', function (Blueprint $table) {
            $table->id();

            $table->foreignId('device_id')
                ->constrained('devices')
                ->cascadeOnDelete();

            $table->string('type');

            $table->json('data')
                ->nullable();

            $table->timestamp('occurred_at');

            $table->timestamps();

            $table->index([
                'device_id',
                'occurred_at',
            ]);

            $table->index([
                'device_id',
                'type',
            ]);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('recovery_events');
    }
};