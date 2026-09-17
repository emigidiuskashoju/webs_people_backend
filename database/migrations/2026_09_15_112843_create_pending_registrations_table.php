<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create(
            'pending_registrations',
            function (Blueprint $table) {
                $table->id();

                $table->string('name', 100);

                $table->string('email')
                    ->index();

                $table->string('phone_number', 20)
                    ->index();

                $table->string(
                    'verification_code_hash'
                );

                $table->unsignedTinyInteger(
                    'attempts'
                )->default(0);

                $table->timestamp(
                    'code_sent_at'
                )->nullable();

                $table->timestamp(
                    'expires_at'
                )->nullable();

                $table->timestamps();

                $table->index([
                    'email',
                    'expires_at',
                ]);
            }
        );
    }

    public function down(): void
    {
        Schema::dropIfExists(
            'pending_registrations'
        );
    }
};