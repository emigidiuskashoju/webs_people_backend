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
        Schema::table('location_requests', function (Blueprint $table) {

            if (!Schema::hasColumn(
                'location_requests',
                'latitude'
            )) {
                $table->decimal(
                    'latitude',
                    10,
                    7
                )->nullable();
            }

            if (!Schema::hasColumn(
                'location_requests',
                'longitude'
            )) {
                $table->decimal(
                    'longitude',
                    10,
                    7
                )->nullable();
            }

            if (!Schema::hasColumn(
                'location_requests',
                'accuracy'
            )) {
                $table->decimal(
                    'accuracy',
                    10,
                    2
                )->nullable();
            }

            if (!Schema::hasColumn(
                'location_requests',
                'location_updated_at'
            )) {
                $table->timestamp(
                    'location_updated_at'
                )->nullable();
            }

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('location_requests', function (Blueprint $table) {

            if (Schema::hasColumn(
                'location_requests',
                'latitude'
            )) {
                $table->dropColumn('latitude');
            }

            if (Schema::hasColumn(
                'location_requests',
                'longitude'
            )) {
                $table->dropColumn('longitude');
            }

            if (Schema::hasColumn(
                'location_requests',
                'accuracy'
            )) {
                $table->dropColumn('accuracy');
            }

            if (Schema::hasColumn(
                'location_requests',
                'location_updated_at'
            )) {
                $table->dropColumn(
                    'location_updated_at'
                );
            }

        });
    }
};