<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends Factory<\App\Models\User>
 */
class UserFactory extends Factory
{
    protected static ?string $password = null;

    public function definition(): array
    {
        return [
            'name' => fake()->name(),

            'phone_number' =>
                '+2557' .
                fake()->unique()->numerify(
                    '#######'
                ),

            'phone_verified_at' => null,

            'password' =>
                static::$password ??=
                    Hash::make('password'),

            'remember_token' =>
                Str::random(10),
        ];
    }
}