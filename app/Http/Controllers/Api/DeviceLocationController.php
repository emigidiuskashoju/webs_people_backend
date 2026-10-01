<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Device;
use App\Models\DeviceLocation;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DeviceLocationController extends Controller
{
    // ============================================================
    // STORE LOCATION
    // ============================================================

    public function store(
        Request $request,
        Device $device
    ): JsonResponse {

        /*
         * Ownership and device-secret verification are handled
         * by the device.secret middleware.
         */

        $validated = $request->validate([
            'latitude' => [
                'required',
                'numeric',
                'between:-90,90',
            ],

            'longitude' => [
                'required',
                'numeric',
                'between:-180,180',
            ],

            'accuracy' => [
                'nullable',
                'numeric',
                'min:0',
            ],

            'altitude' => [
                'nullable',
                'numeric',
            ],

            'speed' => [
                'nullable',
                'numeric',
                'min:0',
            ],

            'heading' => [
                'nullable',
                'numeric',
                'between:0,360',
            ],

            'recorded_at' => [
                'required',
                'date',
            ],
        ]);


        // --------------------------------------------------------
        // CREATE LOCATION
        // --------------------------------------------------------

        $location = $device
            ->locations()
            ->create([
                'latitude' =>
                    $validated['latitude'],

                'longitude' =>
                    $validated['longitude'],

                'accuracy' =>
                    $validated['accuracy'] ?? null,

                'altitude' =>
                    $validated['altitude'] ?? null,

                'speed' =>
                    $validated['speed'] ?? null,

                'heading' =>
                    $validated['heading'] ?? null,

                'recorded_at' =>
                    $validated['recorded_at'],
            ]);


        // --------------------------------------------------------
        // UPDATE DEVICE LAST SEEN
        // --------------------------------------------------------

        $device->update([
            'last_seen_at' => now(),
        ]);


        return response()->json([
            'message' =>
                'Location recorded successfully.',

            'location' =>
                $location,
        ], 201);
    }


    // ============================================================
    // LOCATION HISTORY
    // ============================================================

    public function index(
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

        $locations = $device
            ->locations()
            ->latest('recorded_at')
            ->limit(200)
            ->get();

        $latestLocation =
            $locations->first();

        return response()->json([
            'device' =>
                $device,

            'latest_location' =>
                $latestLocation,

            'locations' =>
                $locations,
        ]);
    }
}