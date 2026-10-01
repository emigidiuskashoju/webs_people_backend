<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Connection;
use App\Models\ConnectionRequest;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ConnectionController extends Controller
{
    public function sendRequest(
        Request $request
    ): JsonResponse {
        $request->validate([
            'user_id' => [
                'required',
                'integer',
                'exists:users,id',
            ],
        ]);

        $senderId = $request->user()->id;
        $receiverId = (int) $request->user_id;

        if ($senderId === $receiverId) {
            return response()->json([
                'message' => 'You cannot connect with yourself.',
            ], 422);
        }

        $receiverExists = User::where(
            'id',
            $receiverId
        )->exists();

        if (!$receiverExists) {
            return response()->json([
                'message' => 'User not found.',
            ], 404);
        }

        $userOne = min(
            $senderId,
            $receiverId
        );

        $userTwo = max(
            $senderId,
            $receiverId
        );

        $alreadyConnected = Connection::where(
            'user_one_id',
            $userOne
        )
            ->where(
                'user_two_id',
                $userTwo
            )
            ->exists();

        if ($alreadyConnected) {
            return response()->json([
                'message' => 'Users are already connected.',
            ], 422);
        }

        $existingRequest = ConnectionRequest::where(
            function ($query) use (
                $senderId,
                $receiverId
            ) {
                $query
                    ->where('sender_id', $senderId)
                    ->where('receiver_id', $receiverId);
            }
        )
            ->orWhere(
                function ($query) use (
                    $senderId,
                    $receiverId
                ) {
                    $query
                        ->where('sender_id', $receiverId)
                        ->where('receiver_id', $senderId);
                }
            )
            ->where('status', 'pending')
            ->first();

        if ($existingRequest) {
            return response()->json([
                'message' =>
                    'A pending connection request already exists.',
            ], 422);
        }

        $connectionRequest =
            ConnectionRequest::create([
                'sender_id' => $senderId,
                'receiver_id' => $receiverId,
                'status' => 'pending',
            ]);

        return response()->json([
            'message' =>
                'Connection request sent.',
            'request' => [
                'id' => $connectionRequest->id,
                'sender_id' =>
                    $connectionRequest->sender_id,
                'receiver_id' =>
                    $connectionRequest->receiver_id,
                'status' =>
                    $connectionRequest->status,
                'created_at' =>
                    $connectionRequest->created_at,
            ],
        ], 201);
    }

    public function pendingRequests(
        Request $request
    ): JsonResponse {
        $requests =
            ConnectionRequest::with('sender')
                ->where(
                    'receiver_id',
                    $request->user()->id
                )
                ->where(
                    'status',
                    'pending'
                )
                ->latest()
                ->get();

        return response()->json([
            'requests' => $requests->map(
                function (
                    ConnectionRequest $item
                ) {
                    return [
                        'id' => $item->id,
                        'sender_id' =>
                            $item->sender_id,
                        'sender_name' =>
                            $item->sender?->name,
                        'status' =>
                            $item->status,
                        'created_at' =>
                            $item->created_at,
                    ];
                }
            )->values(),
        ]);
    }

    public function acceptRequest(
        Request $request,
        int $requestId
    ): JsonResponse {
        $connectionRequest =
            ConnectionRequest::where(
                'id',
                $requestId
            )
                ->where(
                    'receiver_id',
                    $request->user()->id
                )
                ->where(
                    'status',
                    'pending'
                )
                ->first();

        if (!$connectionRequest) {
            return response()->json([
                'message' =>
                    'Connection request not found.',
            ], 404);
        }

        $connectionRequest->update([
            'status' => 'accepted',
        ]);

        $userOne = min(
            $connectionRequest->sender_id,
            $connectionRequest->receiver_id
        );

        $userTwo = max(
            $connectionRequest->sender_id,
            $connectionRequest->receiver_id
        );

        $connection = Connection::firstOrCreate(
            [
                'user_one_id' => $userOne,
                'user_two_id' => $userTwo,
            ]
        );

        return response()->json([
            'message' =>
                'Connection request accepted.',
            'connection' => [
                'id' => $connection->id,
                'user_one_id' =>
                    $connection->user_one_id,
                'user_two_id' =>
                    $connection->user_two_id,
            ],
        ]);
    }

    public function rejectRequest(
        Request $request,
        int $requestId
    ): JsonResponse {
        $connectionRequest =
            ConnectionRequest::where(
                'id',
                $requestId
            )
                ->where(
                    'receiver_id',
                    $request->user()->id
                )
                ->where(
                    'status',
                    'pending'
                )
                ->first();

        if (!$connectionRequest) {
            return response()->json([
                'message' =>
                    'Connection request not found.',
            ], 404);
        }

        $connectionRequest->update([
            'status' => 'rejected',
        ]);

        return response()->json([
            'message' =>
                'Connection request rejected.',
        ]);
    }

    public function connections(
        Request $request
    ): JsonResponse {
        $userId = $request->user()->id;

        $connections = Connection::where(
            'user_one_id',
            $userId
        )
            ->orWhere(
                'user_two_id',
                $userId
            )
            ->get();

        $userIds = $connections
            ->map(
                function (Connection $connection) use (
                    $userId
                ) {
                    return $connection->user_one_id === $userId
                        ? $connection->user_two_id
                        : $connection->user_one_id;
                }
            )
            ->values();

        $users = User::whereIn(
            'id',
            $userIds
        )->get();

        return response()->json([
            'connections' => $users->map(
                function (User $user) {
                    return [
                        'id' => $user->id,
                        'name' => $user->name,
                    ];
                }
            )->values(),
        ]);
    }
}