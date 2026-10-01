<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Device;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class DeviceController extends Controller
{
    // ============================================================
    // LIST MY DEVICES
    // ============================================================

    public function index(Request $request): JsonResponse
    {
        $devices = $request->user()
            ->devices()
            ->latest()
            ->get();

        return response()->json([
            'devices' => $devices,
        ]);
    }

    // ============================================================
    // REGISTER DEVICE
    // ============================================================

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'device_uuid' => [
                'required',
                'uuid',
            ],

            'device_secret' => [
                'required',
                'string',
                'min:16',
                'max:255',
            ],

            'name' => [
                'required',
                'string',
                'max:100',
            ],

            'platform' => [
                'required',
                'string',
                'max:50',
            ],

            'model' => [
                'nullable',
                'string',
                'max:100',
            ],

            'manufacturer' => [
                'nullable',
                'string',
                'max:100',
            ],

            'os_version' => [
                'nullable',
                'string',
                'max:100',
            ],

            'app_version' => [
                'nullable',
                'string',
                'max:100',
            ],
        ]);

        $user = $request->user();

        /*
         * A device UUID identifies a physical device.
         *
         * Check whether this phone has already been registered.
         */
        $device = Device::where(
            'device_uuid',
            $validated['device_uuid']
        )->first();

        // ========================================================
        // DEVICE ALREADY EXISTS
        // ========================================================

        if ($device) {
            /*
             * If the device belongs to another account, do not
             * allow it to be attached to this account.
             */
            if ((int) $device->user_id !== (int) $user->id) {
                return response()->json([
                    'message' =>
                        'This physical device is already registered to another Webs account.',
                ], 409);
            }

            /*
             * Verify the device secret.
             *
             * This prevents someone who knows only the UUID from
             * claiming the device.
             */
            if (
                $device->device_secret_hash !== null &&
                !Hash::check(
                    $validated['device_secret'],
                    $device->device_secret_hash
                )
            ) {
                return response()->json([
                    'message' =>
                        'The device identity could not be verified.',
                ], 403);
            }

            /*
             * Same user + same device.
             *
             * Update device information instead of creating a
             * duplicate row.
             */
            $device->update([
                'name' => trim($validated['name']),
                'platform' => $validated['platform'],
                'model' => $validated['model'] ?? null,
                'manufacturer' =>
                    $validated['manufacturer'] ?? null,
                'os_version' =>
                    $validated['os_version'] ?? null,
                'app_version' =>
                    $validated['app_version'] ?? null,
                'is_active' => true,
            ]);

            return response()->json([
                'message' => 'Device already registered.',
                'device' => $device->fresh(),
            ]);
        }

        // ========================================================
        // NEW DEVICE
        // ========================================================

        $device = $user->devices()->create([
            'device_uuid' =>
                $validated['device_uuid'],

            'device_secret_hash' =>
                Hash::make(
                    $validated['device_secret']
                ),

            'name' =>
                trim($validated['name']),

            'platform' =>
                $validated['platform'],

            'model' =>
                $validated['model'] ?? null,

            'manufacturer' =>
                $validated['manufacturer'] ?? null,

            'os_version' =>
                $validated['os_version'] ?? null,

            'app_version' =>
                $validated['app_version'] ?? null,

            'battery_level' => null,
            'is_charging' => null,
            'network_type' => null,
            'location_enabled' => null,
            'last_seen_at' => now(),
            'is_active' => true,
            'is_lost' => false,
        ]);

        return response()->json([
            'message' => 'Device registered successfully.',
            'device' => $device,
        ], 201);
    }

    // ============================================================
    // SHOW DEVICE
    // ============================================================

    public function show(
        Request $request,
        Device $device
    ): JsonResponse {
        $this->authorizeDevice(
            $request,
            $device
        );

        return response()->json([
            'device' => $device,
        ]);
    }

    // ============================================================
    // HEARTBEAT
    // ============================================================

   public function heartbeat(
    Request $request,
    Device $device
): JsonResponse {
    $this->authorizeDevice(
        $request,
        $device
    );

    $this->verifyDeviceSecret(
        $request,
        $device
    );

    $device->update([
        'last_seen_at' => now(),
        'is_active' => true,
    ]);

    return response()->json([
        'message' => 'Heartbeat received.',
        'device' => $device->fresh(),
    ]);
}

    // ============================================================
    // DEVICE AUTHORIZATION
    // ============================================================

    private function authorizeDevice(
        Request $request,
        Device $device
    ): void {
        abort_unless(
            (int) $device->user_id ===
                (int) $request->user()->id,
            404
        );
    }

    // ============================================================
    // DEVICE SECRET VERIFICATION
    // ============================================================

    private function verifyDeviceSecret(
        Request $request,
        Device $device
    ): void {
        $deviceSecret =
            $request->header('X-Device-Secret');

        if (
            !$deviceSecret ||
            !$device->device_secret_hash ||
            !Hash::check(
                $deviceSecret,
                $device->device_secret_hash
            )
        ) {
            abort(
                403,
                'Invalid device secret.'
            );
        }
    }
}