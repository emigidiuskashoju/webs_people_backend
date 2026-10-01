<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PendingMessage;
use App\Models\PendingReadReceipt;
use App\Services\FcmPushService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MessageController extends Controller
{
    /**
     * Accept a message for temporary delivery.
     */
    public function send(
        Request $request,
        FcmPushService $fcm,
    ): JsonResponse {
        $validated = $request->validate([
            'recipient_id' => [
                'required',
                'integer',
                'exists:users,id',
            ],

            'client_message_id' => [
                'required',
                'uuid',
            ],

            'message' => [
                'required',
                'string',
                'max:5000',
            ],
        ]);

        $sender = $request->user();

        if (
            (int) $validated['recipient_id']
            === (int) $sender->id
        ) {
            return response()->json([
                'message' =>
                    'You cannot send a message to yourself.',
            ], 422);
        }

        PendingMessage::where(
            'expires_at',
            '<',
            now()->subDays(30)
        )->delete();

        $existing = PendingMessage::where(
            'sender_id',
            $sender->id
        )
            ->where(
                'client_message_id',
                $validated['client_message_id']
            )
            ->first();

        if ($existing) {
            return response()->json([
                'message' =>
                    'Message already accepted.',
                'server_message_id' =>
                    $existing->id,
                'client_message_id' =>
                    $existing->client_message_id,
            ]);
        }

        $pending = PendingMessage::create([
            'sender_id' => $sender->id,
            'recipient_id' =>
                $validated['recipient_id'],
            'client_message_id' =>
                $validated['client_message_id'],
            'message' => $validated['message'],
            'created_at' => now(),
            'expires_at' => now()->addDays(7),
            'acknowledged_at' => null,
        ]);

        // ------------------------------------------------------------
        // Push notification to the recipient's devices
        //
        // Non-fatal: if FCM fails, the message is still stored
        // and the client still gets a 201.
        // ------------------------------------------------------------

        try {
            $fcm->sendToUser(
                (int) $validated['recipient_id'],
                [
                    'type' => 'new_message',
                    'conversation_id' => $this->conversationId(
                        (int) $sender->id,
                        (int) $validated['recipient_id'],
                    ),
                    'sender_id' => (string) $sender->id,
                    'sender_name' => (string) $sender->name,
                    'body' => (string) $validated['message'],
                    'server_message_id' => (string) $pending->id,
                ],
                $sender->name,
                $validated['message'],
            );
        } catch (\Throwable $e) {
            report($e);
        }

        return response()->json([
            'message' =>
                'Message accepted for delivery.',
            'server_message_id' =>
                $pending->id,
            'client_message_id' =>
                $pending->client_message_id,
        ], 201);
    }

    /**
     * Return messages waiting for the authenticated user.
     */
    public function pending(Request $request): JsonResponse
    {
        $user = $request->user();

        PendingMessage::where(
            'expires_at',
            '<',
            now()->subDays(30)
        )->delete();

        $messages = PendingMessage::query()
            ->where('recipient_id', $user->id)
            ->whereNull('acknowledged_at')
            ->orderBy('id')
            ->get();

        return response()->json([
            'messages' => $messages,
        ]);
    }

    /**
     * Mark a message as stored on the recipient's device.
     */
    public function acknowledge(
        Request $request,
        PendingMessage $message
    ): JsonResponse {
        $user = $request->user();

        if (
            (int) $message->recipient_id
            !== (int) $user->id
        ) {
            return response()->json([
                'message' =>
                    'You are not allowed to acknowledge this message.',
            ], 403);
        }

        if ($message->acknowledged_at === null) {
            $message->update([
                'acknowledged_at' => now(),
            ]);
        }

        return response()->json([
            'message' => 'Message acknowledged.',
        ]);
    }

    /**
     * Record that the recipient has read a message.
     */
    public function markRead(
        Request $request,
        int $messageId
    ): JsonResponse {
        $user = $request->user();

        $message = PendingMessage::find($messageId);

        if (!$message) {
            return response()->json([
                'message' =>
                    'Message no longer available; read receipt skipped.',
            ]);
        }

        if (
            (int) $message->recipient_id
            !== (int) $user->id
        ) {
            return response()->json([
                'message' =>
                    'You are not allowed to mark this message as read.',
            ], 403);
        }

        PendingReadReceipt::firstOrCreate(
            [
                'message_id' => $message->id,
                'reader_id' => $user->id,
            ],
            [
                'sender_id' => $message->sender_id,
                'created_at' => now(),
            ]
        );

        return response()->json([
            'message' => 'Message marked as read.',
        ]);
    }

    /**
     * Return read receipts waiting for the authenticated sender.
     */
    public function pendingReadReceipts(
        Request $request
    ): JsonResponse {
        $user = $request->user();

        $receipts = PendingReadReceipt::query()
            ->where('sender_id', $user->id)
            ->orderBy('id')
            ->get();

        return response()->json([
            'receipts' => $receipts,
        ]);
    }

    /**
     * Delete a read receipt after the sender's device has
     * recorded it locally.
     */
    public function acknowledgeReadReceipt(
        Request $request,
        PendingReadReceipt $receipt
    ): JsonResponse {
        $user = $request->user();

        if (
            (int) $receipt->sender_id
            !== (int) $user->id
        ) {
            return response()->json([
                'message' =>
                    'You are not allowed to acknowledge this read receipt.',
            ], 403);
        }

        $receipt->delete();

        return response()->json([
            'message' => 'Read receipt acknowledged.',
        ]);
    }

    /**
     * Build the same conversation id the Flutter app uses:
     * "<smallerId>_<largerId>".
     */
    private function conversationId(int $a, int $b): string
    {
        $ids = [$a, $b];
        sort($ids);
        return $ids[0] . '_' . $ids[1];
    }
}