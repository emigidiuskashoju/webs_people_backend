<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
{
    Schema::create('custom_routes', function (Blueprint $table) {
        $table->id();

        $table->foreignId('location_request_id')
            ->unique()
            ->constrained('location_requests')
            ->cascadeOnDelete();

        $table->foreignId('owner_id')
            ->constrained('users')
            ->cascadeOnDelete();

        $table->foreignId('recipient_id')
            ->constrained('users')
            ->cascadeOnDelete();

        $table->json('points');

        $table->double('total_distance_meters');

        $table->timestamps();

        $table->index('owner_id');
        $table->index('recipient_id');
    });
}

    /**
     * Reverse the migrations.
     */
   public function down(): void
{
    Schema::dropIfExists('custom_routes');
}
};
