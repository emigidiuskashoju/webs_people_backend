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
        Schema::table('users', function (Blueprint $table) {

            // --------------------------------------------------
            // Add `password` if it does not already exist.
            // --------------------------------------------------

            if (!Schema::hasColumn('users', 'password')) {
                $table->string('password')
                    ->nullable()
                    ->after('phone_number');
            }

            // --------------------------------------------------
            // Add `email` if it does not already exist.
            // (Your registration flow submits an email.)
            // --------------------------------------------------

            if (!Schema::hasColumn('users', 'email')) {
                $table->string('email')
                    ->nullable()
                    ->unique()
                    ->after('name');
            }

            // --------------------------------------------------
            // Add `email_verified_at` if missing.
            // --------------------------------------------------

            if (!Schema::hasColumn('users', 'email_verified_at')) {
                $table->timestamp('email_verified_at')
                    ->nullable()
                    ->after('email');
            }

            // --------------------------------------------------
            // Add `profile_photo_path` if missing.
            // --------------------------------------------------

            if (!Schema::hasColumn('users', 'profile_photo_path')) {
                $table->string('profile_photo_path')
                    ->nullable()
                    ->after('phone_verified_at');
            }

            // --------------------------------------------------
            // Add `track_security_password` if missing.
            // --------------------------------------------------

            if (!Schema::hasColumn('users', 'track_security_password')) {
                $table->string('track_security_password')
                    ->nullable()
                    ->after('profile_photo_path');
            }

            // --------------------------------------------------
            // Add `remember_token` if missing.
            // --------------------------------------------------

            if (!Schema::hasColumn('users', 'remember_token')) {
                $table->rememberToken();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {

            if (Schema::hasColumn('users', 'password')) {
                $table->dropColumn('password');
            }

            if (Schema::hasColumn('users', 'profile_photo_path')) {
                $table->dropColumn('profile_photo_path');
            }

            if (Schema::hasColumn('users', 'track_security_password')) {
                $table->dropColumn('track_security_password');
            }

            // Do NOT drop `email`, `email_verified_at`, or
            // `remember_token` in the down() method if they
            // already existed before this migration ran —
            // they belong to the original `users` migration
            // and dropping them would break other parts of
            // the app.
        });
    }
};