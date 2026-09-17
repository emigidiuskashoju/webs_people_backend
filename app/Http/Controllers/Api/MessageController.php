<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PendingMessage;
use App\Models\PendingReadReceipt;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MessageController extends Controller
{
    /**
     * Accept a message for temporary delivery.
     */
    public function send(Request $request): JsonResponse
    {
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

        /*
         * Prevent sending a message to yourself.
         */
        if (
            (int) $validated['recipient_id']
            === (int) $sender->id
        ) {
            return response()->json([
                'message' =>
                    'You cannot send a message to yourself.',
            ], 422);
        }

        /*
         * Remove expired messages belonging to this sender.
         */
        PendingMessage::where(
            'expires_at',
            '<',
            now()
        )->delete();

        /*
         * Check whether this message was already accepted.
         *
         * This protects against duplicate requests when the
         * sender retries because of a temporary network problem.
         */
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

        /*
         * Create the temporary delivery copy.
         *
         * Seven days is the maximum lifetime of an undelivered
         * message in this first implementation.
         */
        $pending = PendingMessage::create([
            'sender_id' =>
                $sender->id,

            'recipient_id' =>
                $validated['recipient_id'],

            'client_message_id' =>
                $validated['client_message_id'],

            'message' =>
                $validated['message'],

            'created_at' =>
                now(),

            'expires_at' =>
                now()->addDays(7),
        ]);

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

        /*
         * Clean up expired messages first.
         */
        PendingMessage::where(
            'expires_at',
            '<',
            now()
        )->delete();

        $messages = PendingMessage::query()
            ->where(
                'recipient_id',
                $user->id
            )
            ->orderBy('id')
            ->get();

        return response()->json([
            'messages' => $messages,
        ]);
    }

    /**
     * Delete a message after the recipient has successfully
     * stored it locally.
     */
    public function acknowledge(
        Request $request,
        PendingMessage $message
    ): JsonResponse {
        $user = $request->user();

        /*
         * Only the recipient can acknowledge the message.
         */
        if (
            (int) $message->recipient_id
            !== (int) $user->id
        ) {
            return response()->json([
                'message' =>
                    'You are not allowed to acknowledge this message.',
            ], 403);
        }

        $message->delete();

        return response()->json([
            'message' =>
                'Message acknowledged.',
        ]);
    }

    public function markRead(
    Request $request,
    PendingMessage $message
): JsonResponse {
    $user = $request->user();

    /*
     * Only the recipient can mark a message
     * as read.
     */
    if (
        (int) $message->recipient_id
        !== (int) $user->id
    ) {
        return response()->json([
            'message' =>
                'You are not allowed to mark this message as read.',
        ], 403);
    }

    /*
     * The pending message may already have been
     * acknowledged/deleted from the server.
     *
     * In that case there is nothing more to do.
     */
    PendingReadReceipt::firstOrCreate(
        [
            'message_id' =>
                $message->id,

            'reader_id' =>
                $user->id,
        ],
        [
            'sender_id' =>
                $message->sender_id,

            'created_at' =>
                now(),
        ]
    );

    return response()->json([
        'message' =>
            'Message marked as read.',
    ]);
}

public function pendingReadReceipts(
    Request $request
): JsonResponse {
    $user = $request->user();

    $receipts =
        PendingReadReceipt::query()
            ->where(
                'sender_id',
                $user->id
            )
            ->orderBy('id')
            ->get();

    return response()->json([
        'receipts' =>
            $receipts,
    ]);
}

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
        'message' =>
            'Read receipt acknowledged.',
    ]);
}
}