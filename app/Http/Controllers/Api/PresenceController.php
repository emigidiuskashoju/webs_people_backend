<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\UserPresence;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PresenceController extends Controller
{
    /** A user counts as "online" if seen within this many seconds. */
    private const ONLINE_WINDOW_SECONDS = 60;

    /** A "typing" signal is valid for this many seconds. */
    private const TYPING_WINDOW_SECONDS = 6;

    /**
     * Called by the app every ~25s while running, and immediately
     * whenever the user starts typing in a chat.
     *
     * Body (all optional except presence is implicit):
     *   {
     *     "typing_in_conversation": "1_3" | null
     *   }
     */
    public function heartbeat(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'typing_in_conversation' => [
                'nullable',
                'string',
                'max:64',
            ],
        ]);

        $userId = $request->user()->id;

        $typingConversation =
            $validated['typing_in_conversation'] ?? null;

        $now = now();

        UserPresence::updateOrCreate(
            ['user_id' => $userId],
            [
                'last_seen_at' => $now,
                'typing_in_conversation' => $typingConversation,
                'typing_updated_at' => $typingConversation === null
                    ? null
                    : $now,
            ]
        );

        return response()->json([
            'message' => 'Presence updated.',
            'online' => true,
            'last_seen_at' => $now->toISOString(),
        ]);
    }

    /**
     * Return one user's presence. Used by the chat screen
     * when the user opens a conversation.
     */
    public function show(Request $request, int $userId): JsonResponse
    {
        $user = User::find($userId);

        if (!$user) {
            return response()->json([
                'message' => 'User not found.',
            ], 404);
        }

        $presence = UserPresence::find($userId);

        return response()->json([
            'presence' => $this->payload($presence),
        ]);
    }

    /**
     * Return presence for every user the caller has a
     * conversation with. Used by the chats list.
     *
     * Conversation partners are derived from the users table:
     * we simply return presence for every user except the
     * caller. If your app grows, swap this for a real
     * "connections" query.
     */
    public function index(Request $request): JsonResponse
    {
        $currentUserId = $request->user()->id;

        $otherUserIds = User::query()
            ->where('id', '!=', $currentUserId)
            ->pluck('id')
            ->all();

        $presences = UserPresence::query()
            ->whereIn('user_id', $otherUserIds)
            ->get()
            ->keyBy('user_id');

        $result = [];

        foreach ($otherUserIds as $userId) {
            $result[(string) $userId] = $this->payload(
                $presences->get($userId)
            );
        }

        return response()->json([
            'presences' => $result,
        ]);
    }

    /**
     * Shape the presence row into the JSON contract the
     * Flutter client expects.
     */
    private function payload(?UserPresence $presence): array
    {
        if (!$presence || $presence->last_seen_at === null) {
            return [
                'online' => false,
                'last_seen_at' => null,
                'typing' => false,
            ];
        }

        $now = now();

        $online = $presence->last_seen_at
            ->diffInSeconds($now) < self::ONLINE_WINDOW_SECONDS;

        $typing = false;

        if (
            $presence->typing_in_conversation !== null &&
            $presence->typing_updated_at !== null
        ) {
            $typing = $presence->typing_updated_at
                ->diffInSeconds($now) < self::TYPING_WINDOW_SECONDS;
        }

        return [
            'online' => $online,
            'last_seen_at' =>
                $presence->last_seen_at?->toISOString(),
            'typing' => $typing,
            'typing_in_conversation' => $typing
                ? $presence->typing_in_conversation
                : null,
        ];
    }
}