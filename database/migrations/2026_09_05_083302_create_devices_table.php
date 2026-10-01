<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('devices', function (Blueprint $table) {
            $table->id();

            $table->foreignId('user_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->uuid('device_uuid')
                ->unique();

            $table->string(
                'device_secret_hash'
            )->nullable();

            $table->string('name');

            $table->string('platform')
                ->default('android');

            $table->string('model')
                ->nullable();

            $table->string('manufacturer')
                ->nullable();

            $table->string('os_version')
                ->nullable();

            $table->string('app_version')
                ->nullable();

            $table->unsignedTinyInteger(
                'battery_level'
            )->nullable();

            $table->boolean(
                'is_charging'
            )->nullable();

            $table->string(
                'network_type'
            )->nullable();

            $table->boolean(
                'location_enabled'
            )->nullable();

            $table->timestamp(
                'last_seen_at'
            )->nullable();

            $table->boolean(
                'is_active'
            )->default(true);

            $table->boolean(
                'is_lost'
            )->default(false);

            $table->timestamps();

            $table->index(
                'user_id'
            );

            $table->index(
                'last_seen_at'
            );

            $table->index(
                'is_lost'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists(
            'devices'
        );
    }
};