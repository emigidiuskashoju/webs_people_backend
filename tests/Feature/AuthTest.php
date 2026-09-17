<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_register(): void
    {
        $response = $this->postJson(
            '/api/v1/auth/register',
            [
                'name' => 'Test User',
                'phone_number' => '+255700000001',
                'password' => 'password123',
                'password_confirmation' => 'password123',
            ]
        );

        $response
            ->assertCreated()
            ->assertJsonPath(
                'user.name',
                'Test User'
            )
            ->assertJsonPath(
                'user.phone_number',
                '+255700000001'
            );

        $this->assertDatabaseHas(
            'users',
            [
                'phone_number' =>
                    '+255700000001',
            ]
        );
    }

    public function test_user_can_login(): void
    {
        $user = User::create([
            'name' => 'Test User',
            'phone_number' =>
                '+255700000002',
            'password' =>
                'password123',
        ]);

        $response = $this->postJson(
            '/api/v1/auth/login',
            [
                'phone_number' =>
                    '+255700000002',
                'password' =>
                    'password123',
            ]
        );

        $response
            ->assertOk()
            ->assertJsonStructure([
                'message',
                'token',
                'user',
            ]);
    }

    public function test_invalid_login_is_rejected(): void
    {
        User::create([
            'name' => 'Test User',
            'phone_number' =>
                '+255700000003',
            'password' =>
                'password123',
        ]);

        $response = $this->postJson(
            '/api/v1/auth/login',
            [
                'phone_number' =>
                    '+255700000003',
                'password' =>
                    'wrong-password',
            ]
        );

        $response
            ->assertUnprocessable();
    }

    public function test_authenticated_user_can_access_me(): void
    {
        $user = User::create([
            'name' => 'Authenticated User',
            'phone_number' =>
                '+255700000004',
            'password' =>
                'password123',
        ]);

        Sanctum::actingAs($user);

        $response = $this->getJson(
            '/api/v1/auth/me'
        );

        $response
            ->assertOk()
            ->assertJsonPath(
                'user.id',
                $user->id
            )
            ->assertJsonPath(
                'user.phone_number',
                '+255700000004'
            );
    }

    public function test_unauthenticated_user_cannot_access_me(): void
    {
        $response = $this->getJson(
            '/api/v1/auth/me'
        );

        $response->assertUnauthorized();
    }

    public function test_authenticated_user_can_logout(): void
    {
        $user = User::create([
            'name' => 'Logout User',
            'phone_number' =>
                '+255700000005',
            'password' =>
                'password123',
        ]);

        Sanctum::actingAs($user);

        $response = $this->postJson(
            '/api/v1/auth/logout'
        );

        $response
            ->assertOk()
            ->assertJson([
                'message' =>
                    'Logged out successfully.',
            ]);
    }
}