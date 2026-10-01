<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Create device_locations table.
     */
    public function up(): void
    {
        Schema::create('device_locations', function (Blueprint $table) {
            $table->id();

            $table->foreignId('device_id')
                ->constrained('devices')
                ->cascadeOnDelete();

            $table->decimal(
                'latitude',
                10,
                7
            );

            $table->decimal(
                'longitude',
                10,
                7
            );

            $table->decimal(
                'accuracy',
                10,
                2
            )->nullable();

            $table->decimal(
                'altitude',
                10,
                2
            )->nullable();

            $table->decimal(
                'speed',
                10,
                2
            )->nullable();

            $table->decimal(
                'heading',
                10,
                2
            )->nullable();

            $table->timestamp(
                'recorded_at'
            );

            $table->timestamps();

            $table->index([
                'device_id',
                'recorded_at',
            ]);
        });
    }

    /**
     * Drop device_locations table.
     */
    public function down(): void
    {
        Schema::dropIfExists('device_locations');
    }
};