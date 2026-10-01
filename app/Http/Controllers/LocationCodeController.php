<?php

namespace App\Http\Controllers;

use App\Models\DailyLocationCode;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class LocationCodeController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $user = $request->user();

        $today = Carbon::today();

        $existing = DailyLocationCode::query()
            ->where('user_id', $user->id)
            ->whereDate('code_date', $today)
            ->first();

        if ($existing && $existing->expires_at->isFuture()) {
            return response()->json([
                'message' => 'Today\'s code has already been generated.',
                'expires_at' => $existing->expires_at->toIso8601String(),
            ], 409);
        }

        $code = str_pad(
            (string) random_int(0, 999999),
            6,
            '0',
            STR_PAD_LEFT
        );

        $expiresAt = Carbon::tomorrow();

        DailyLocationCode::updateOrCreate(
            [
                'user_id' => $user->id,
                'code_date' => $today,
            ],
            [
                'code_hash' => hash('sha256', $code),
                'expires_at' => $expiresAt,
            ]
        );

        return response()->json([
            'message' => 'Daily location code created.',
            'code' => $code,
            'expires_at' => $expiresAt->toIso8601String(),
        ]);
    }
}