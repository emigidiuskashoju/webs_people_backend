<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class RecoveryController extends Controller
{
    public function index(
        Request $request
    ): JsonResponse {
        $devices = $request
            ->user()
            ->devices()
            ->with('latestLocation')
            ->latest()
            ->get();

        return response()->json([
            'devices' => $devices,
        ]);
    }
}