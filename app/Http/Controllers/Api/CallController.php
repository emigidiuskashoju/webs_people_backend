<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PendingCallEvent;
use App\Services\FcmPushService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class CallController extends Controller
{
    private const EVENT_EXPIRATION_SECONDS = 120;

    public function start(
        Request $request,
        FcmPushService $fcm,
    ): JsonResponse {
        $validated = $request->validate([
            'recipient_id' => [
                'required',
                'integer',
                'exists:users,id',
            ],

            'type' => [
                'required',
                'in:audio,video',
            ],
        ]);

        $caller = $request->user();

        if (
            (int) $validated['recipient_id']
            === (int) $caller->id
        ) {
            return response()->json([
                'message' => 'You cannot call yourself.',
            ], 422);
        }

        $this->cleanupExpired();

        $callId = (string) Str::uuid();

        PendingCallEvent::create([
            'call_id' => $callId,

            'sender_id' => $caller->id,

            'recipient_id' =>
                $validated['recipient_id'],

            'event' => 'incoming_call',

            'payload' => [
                'type' => $validated['type'],

                'caller_id' => $caller->id,

                'caller_name' => $caller->name,

                'caller_phone_number' =>
                    $caller->phone_number,
            ],

            'created_at' => now(),

            'expires_at' => now()->addSeconds(
                self::EVENT_EXPIRATION_SECONDS
            ),
        ]);

        // ------------------------------------------------------------
        // Push notification to the recipient's devices so the call
        // can ring even if the app is closed.
        //
        // Non-fatal: if FCM fails, the event is still stored and
        // the app's polling listener will still pick it up when
        // the app is next opened.
        // ------------------------------------------------------------
        try {
            $fcm->sendCallPush(
                (int) $validated['recipient_id'],
                (int) $caller->id,
                (string) $caller->name,
                (string) $caller->phone_number,
                $callId,
                (string) $validated['type'],
            );
        } catch (\Throwable $e) {
            report($e);
        }

        return response()->json([
            'call_id' => $callId,

            'type' => $validated['type'],
        ], 201);
    }

    public function pending(
        Request $request
    ): JsonResponse {
        $user = $request->user();

        $this->cleanupExpired();

        $events = PendingCallEvent::query()
            ->where('recipient_id', $user->id)
            ->orderBy('id')
            ->get();

        return response()->json([
            'events' => $events,
        ]);
    }

    public function event(
        Request $request,
        string $callId
    ): JsonResponse {
        $validated = $request->validate([
            'recipient_id' => [
                'required',
                'integer',
                'exists:users,id',
            ],

            'event' => [
                'required',
                'in:accept,decline,end,offer,answer,ice',
            ],

            'payload' => [
                'nullable',
                'array',
            ],
        ]);

        $sender = $request->user();

        $this->cleanupExpired();

        $existing = PendingCallEvent::query()
            ->where('call_id', $callId)
            ->where(function ($query) use (
                $sender,
                $validated
            ) {
                $query
                    ->where(function ($query) use (
                        $sender,
                        $validated
                    ) {
                        $query
                            ->where(
                                'sender_id',
                                $sender->id
                            )
                            ->where(
                                'recipient_id',
                                $validated['recipient_id']
                            );
                    })
                    ->orWhere(function ($query) use (
                        $sender,
                        $validated
                    ) {
                        $query
                            ->where(
                                'sender_id',
                                $validated['recipient_id']
                            )
                            ->where(
                                'recipient_id',
                                $sender->id
                            );
                    });
            })
            ->exists();

        if (!$existing) {
            return response()->json([
                'message' => 'Call was not found.',
            ], 404);
        }

        PendingCallEvent::create([
            'call_id' => $callId,

            'sender_id' => $sender->id,

            'recipient_id' =>
                $validated['recipient_id'],

            'event' => $validated['event'],

            'payload' =>
                $validated['payload'] ?? [],

            'created_at' => now(),

            'expires_at' => now()->addSeconds(
                self::EVENT_EXPIRATION_SECONDS
            ),
        ]);

        return response()->json([
            'message' => 'Call event accepted.',
        ]);
    }

    public function events(
        Request $request,
        string $callId
    ): JsonResponse {
        $user = $request->user();

        $this->cleanupExpired();

        $events = PendingCallEvent::query()
            ->where('call_id', $callId)
            ->where('recipient_id', $user->id)
            ->orderBy('id')
            ->get();

        return response()->json([
            'events' => $events,
        ]);
    }

    public function acknowledge(
        Request $request,
        PendingCallEvent $event
    ): JsonResponse {
        $user = $request->user();

        if (
            (int) $event->recipient_id
            !== (int) $user->id
        ) {
            return response()->json([
                'message' =>
                    'You cannot acknowledge this event.',
            ], 403);
        }

        $event->delete();

        return response()->json([
            'message' => 'Call event acknowledged.',
        ]);
    }

    private function cleanupExpired(): void
    {
        PendingCallEvent::query()
            ->where('expires_at', '<', now())
            ->delete();
    }
}