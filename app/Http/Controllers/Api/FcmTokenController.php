<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\DeviceToken;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class FcmTokenController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'token' => [
                'required',
                'string',
                'max:255',
            ],
            'platform' => [
                'required',
                'string',
                'in:android,ios',
            ],
        ]);

        $user = $request->user();

        // Register (or refresh) this token for the current user.
        //
        // We do NOT delete the same token for other users, because
        // on a shared device (e.g. one phone with two accounts),
        // both accounts should be able to receive pushes.
        DeviceToken::updateOrCreate(
            [
                'user_id' => $user->id,
                'fcm_token' => $validated['token'],
            ],
            [
                'platform' => $validated['platform'],
                'last_seen_at' => now(),
            ]
        );

        return response()->json([
            'message' => 'FCM token registered.',
        ]);
    }

    public function destroy(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'token' => [
                'required',
                'string',
                'max:255',
            ],
        ]);

        $user = $request->user();

        DeviceToken::where('fcm_token', $validated['token'])
            ->where('user_id', $user->id)
            ->delete();

        return response()->json([
            'message' => 'FCM token removed.',
        ]);
    }
}