<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Device;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LostModeController extends Controller
{
    // ============================================================
    // ENABLE LOST MODE
    // ============================================================

    public function enable(
        Request $request,
        Device $device
    ): JsonResponse {

        if (
            $device->user_id !==
            $request->user()->id
        ) {
            return response()->json([
                'message' =>
                    'Unauthorized.',
            ], 403);
        }

        if (!$device->is_active) {
            return response()->json([
                'message' =>
                    'Cannot enable Lost Mode on an inactive device.',
            ], 403);
        }

        $device->update([
            'is_lost' => true,
        ]);

        return response()->json([
            'message' =>
                'Lost Mode enabled.',

            'device' =>
                $device->fresh(),
        ]);
    }


    // ============================================================
    // DISABLE LOST MODE
    // ============================================================

    public function disable(
        Request $request,
        Device $device
    ): JsonResponse {

        if (
            $device->user_id !==
            $request->user()->id
        ) {
            return response()->json([
                'message' =>
                    'Unauthorized.',
            ], 403);
        }

        $device->update([
            'is_lost' => false,
        ]);

        return response()->json([
            'message' =>
                'Lost Mode disabled.',

            'device' =>
                $device->fresh(),
        ]);
    }
}