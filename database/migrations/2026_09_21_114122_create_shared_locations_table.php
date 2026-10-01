<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('shared_locations', function (Blueprint $table) {
            $table->id();

            $table->foreignId('location_request_id')
                ->constrained('location_requests')
                ->cascadeOnDelete();

            // The user whose coordinates are stored in this row.
            $table->foreignId('user_id')
                ->constrained('users')
                ->cascadeOnDelete();

            $table->decimal('latitude', 10, 7);
            $table->decimal('longitude', 10, 7);
            $table->decimal('accuracy', 8, 2)->nullable();

            $table->timestamp('location_updated_at')->nullable();

            $table->timestamps();

            // One row per user per request. Both parties can
            // have their own coordinates on the same request.
            $table->unique(
                ['location_request_id', 'user_id'],
                'shared_locations_request_user_unique'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shared_locations');
    }
};