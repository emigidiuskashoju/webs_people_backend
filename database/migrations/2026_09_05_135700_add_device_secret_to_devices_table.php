<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Add device_secret_hash if it does not already exist.
     */
    public function up(): void
    {
        if (!Schema::hasColumn('devices', 'device_secret_hash')) {
            Schema::table('devices', function (Blueprint $table) {
                $table->string(
                    'device_secret_hash'
                )->nullable()->after('device_uuid');
            });
        }
    }

    /**
     * Do not remove the column here.
     *
     * The column may already be part of the original
     * devices table schema.
     */
    public function down(): void
    {
        // Intentionally left empty.
    }
};