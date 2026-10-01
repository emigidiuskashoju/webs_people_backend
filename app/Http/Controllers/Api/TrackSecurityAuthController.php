<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class TrackSecurityAuthController
    extends Controller
{
    /**
     * Check whether the currently authenticated
     * Webs People account has configured
     * Track & Security.
     */
    public function status(
        Request $request
    ): JsonResponse {
        $user = $request->user();

        return response()->json([
            'configured' =>
                !empty(
                    $user->track_security_password
                ),
        ]);
    }

    /**
     * Create the Track & Security password.
     *
     * This is only available to an already
     * authenticated Webs People account.
     */
    public function setPassword(
        Request $request
    ): JsonResponse {
        $user = $request->user();

        if (
            !empty(
                $user->track_security_password
            )
        ) {
            return response()->json([
                'message' =>
                    'Track & Security password is already configured.',
            ], 409);
        }

        $validated = $request->validate([
            'password' => [
                'required',
                'string',
                'min:8',
                'max:72',
                'confirmed',
            ],
        ]);

        $user->track_security_password =
            Hash::make(
                $validated['password']
            );

        $user->save();

        return response()->json([
            'message' =>
                'Track & Security password created successfully.',
        ]);
    }

    /**
     * Track & Security login.
     *
     * This creates a separate Sanctum token
     * for the security area, while still
     * authenticating the same User record.
     */
    public function login(
        Request $request
    ): JsonResponse {
        $validated = $request->validate([
            'email' => [
                'required',
                'email',
            ],

            'password' => [
                'required',
                'string',
            ],
        ]);

        $user = \App\Models\User::query()
            ->where(
                'email',
                $validated['email']
            )
            ->first();

        if (
            !$user ||
            empty(
                $user->track_security_password
            ) ||
            !Hash::check(
                $validated['password'],
                $user->track_security_password
            )
        ) {
            throw ValidationException::withMessages([
                'email' => [
                    'The email or security password is incorrect.',
                ],
            ]);
        }

        $token = $user->createToken(
            'track-security'
        )->plainTextToken;

        return response()->json([
            'message' =>
                'Track & Security login successful.',

            'token' => $token,

            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'phone_number' =>
                    $user->phone_number,
            ],
        ]);
    }

    /**
     * Revoke the current Track & Security token.
     */
    public function logout(
        Request $request
    ): JsonResponse {
        $token = $request->user()
            ->currentAccessToken();

        if ($token) {
            $token->delete();
        }

        return response()->json([
            'message' =>
                'Track & Security session ended.',
        ]);
    }
}