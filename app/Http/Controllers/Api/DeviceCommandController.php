<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Device;
use App\Models\DeviceCommand;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class DeviceCommandController extends Controller
{
    /**
     * Owner creates a command for their device.
     */
    public function store(
        Request $request,
        Device $device
    ): JsonResponse {
        if ($device->user_id !== $request->user()->id) {
            return response()->json([
                'message' => 'Unauthorized.',
            ], 403);
        }

        $validated = $request->validate([
            'command' => [
                'required',
                'string',
                Rule::in([
                    'request_location',
                    'enable_lost_mode',
                    'disable_lost_mode',
                    'start_tracking',
                    'stop_tracking',
                ]),
            ],
            'payload' => [
                'nullable',
                'array',
            ],
        ]);

        $command = DeviceCommand::create([
            'device_id' => $device->id,
            'command' => $validated['command'],
            'payload' => $validated['payload'] ?? [],
            'status' => 'pending',
        ]);

        return response()->json([
            'message' => 'Command created.',
            'command' => $command,
        ], 201);
    }

    /**
     * Device retrieves pending commands.
     */
    public function index(
        Request $request,
        Device $device
    ): JsonResponse {
        if (!$this->isDeviceRequestAuthorized(
            $request,
            $device
        )) {
            return response()->json([
                'message' => 'Unauthorized.',
            ], 403);
        }

        $commands = $device
            ->commands()
            ->where('status', 'pending')
            ->orderBy('id')
            ->limit(20)
            ->get();

        return response()->json([
            'commands' => $commands,
        ]);
    }

    /**
     * Device acknowledges a command.
     */
    public function acknowledge(
        Request $request,
        Device $device,
        DeviceCommand $command
    ): JsonResponse {
        if (!$this->isDeviceRequestAuthorized(
            $request,
            $device
        )) {
            return response()->json([
                'message' => 'Unauthorized.',
            ], 403);
        }

        if ($command->device_id !== $device->id) {
            return response()->json([
                'message' => 'Command does not belong to this device.',
            ], 403);
        }

        $validated = $request->validate([
            'status' => [
                'required',
                'string',
                Rule::in([
                    'executed',
                    'failed',
                ]),
            ],
            'error_message' => [
                'nullable',
                'string',
            ],
        ]);

        $command->update([
            'status' => $validated['status'],
            'executed_at' =>
                $validated['status'] === 'executed'
                    ? now()
                    : null,
            'error_message' =>
                $validated['error_message'] ?? null,
        ]);

        return response()->json([
            'message' => 'Command acknowledged.',
            'command' => $command->fresh(),
        ]);
    }

    private function isDeviceRequestAuthorized(
        Request $request,
        Device $device
    ): bool {
        if ($device->user_id !== $request->user()->id) {
            return false;
        }

        $secret =
            $request->header('X-Webs-Device-Secret');

        if (!$secret) {
            return false;
        }

        return password_verify(
            $secret,
            $device->device_secret_hash
        );
    }
}