<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create(
            'people_locations',
            function (Blueprint $table) {
                $table->id();

                $table->foreignId(
                    'user_id'
                )
                    ->unique()
                    ->constrained(
                        'users'
                    )
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

                $table->timestamp(
                    'expires_at'
                );

                $table->timestamps();

                $table->index(
                    'expires_at'
                );
            }
        );
    }

    public function down(): void
    {
        Schema::dropIfExists(
            'people_locations'
        );
    }
};