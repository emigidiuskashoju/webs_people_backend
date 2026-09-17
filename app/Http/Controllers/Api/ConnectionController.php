<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ConnectionRequest;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ConnectionController extends Controller
{
    public function send(
        Request $request
    ): JsonResponse {
        $validated = $request->validate([
            'receiver_id' => [
                'required',
                'integer',
                'exists:users,id',
            ],
        ]);

        $sender =
            $request->user();

        $receiverId =
            (int) $validated['receiver_id'];

        if (
            $sender->id ===
            $receiverId
        ) {
            return response()->json([
                'message' =>
                    'You cannot connect with yourself.',
            ], 422);
        }

        $existing =
            ConnectionRequest::where(
                function ($query) use (
                    $sender,
                    $receiverId
                ) {
                    $query
                        ->where(
                            'sender_id',
                            $sender->id
                        )
                        ->where(
                            'receiver_id',
                            $receiverId
                        );
                }
            )
            ->orWhere(
                function ($query) use (
                    $sender,
                    $receiverId
                ) {
                    $query
                        ->where(
                            'sender_id',
                            $receiverId
                        )
                        ->where(
                            'receiver_id',
                            $sender->id
                        );
                }
            )
            ->first();

        if ($existing) {
            return response()->json([
                'message' =>
                    'A connection request already exists.',
                'request' => $existing,
            ], 409);
        }

        $connection =
            ConnectionRequest::create([
                'sender_id' =>
                    $sender->id,

                'receiver_id' =>
                    $receiverId,

                'status' =>
                    'pending',
            ]);

        return response()->json([
            'message' =>
                'Connection request sent.',
            'request' =>
                $connection,
        ], 201);
    }

    public function pending(
        Request $request
    ): JsonResponse {
        $requests =
            ConnectionRequest::query()
                ->where(
                    'receiver_id',
                    $request->user()->id
                )
                ->where(
                    'status',
                    'pending'
                )
                ->with([
                    'sender:id,name,phone_number',
                ])
                ->latest()
                ->get();

        return response()->json([
            'requests' =>
                $requests,
        ]);
    }

    public function accept(
        Request $request,
        ConnectionRequest $connectionRequest
    ): JsonResponse {
        if (
            $connectionRequest
                ->receiver_id !==
            $request->user()->id
        ) {
            return response()->json([
                'message' =>
                    'You cannot accept this request.',
            ], 403);
        }

        if (
            $connectionRequest->status !==
            'pending'
        ) {
            return response()->json([
                'message' =>
                    'This request is no longer pending.',
            ], 409);
        }

        $connectionRequest->update([
            'status' =>
                'accepted',
        ]);

        return response()->json([
            'message' =>
                'Connection request accepted.',
            'request' =>
                $connectionRequest,
        ]);
    }

    public function reject(
        Request $request,
        ConnectionRequest $connectionRequest
    ): JsonResponse {
        if (
            $connectionRequest
                ->receiver_id !==
            $request->user()->id
        ) {
            return response()->json([
                'message' =>
                    'You cannot reject this request.',
            ], 403);
        }

        if (
            $connectionRequest->status !==
            'pending'
        ) {
            return response()->json([
                'message' =>
                    'This request is no longer pending.',
            ], 409);
        }

        $connectionRequest->update([
            'status' =>
                'rejected',
        ]);

        return response()->json([
            'message' =>
                'Connection request rejected.',
            'request' =>
                $connectionRequest,
        ]);
    }

    public function index(
        Request $request
    ): JsonResponse {
        $userId =
            $request->user()->id;

        $connections =
            ConnectionRequest::query()
                ->where(
                    'status',
                    'accepted'
                )
                ->where(
                    function ($query) use (
                        $userId
                    ) {
                        $query
                            ->where(
                                'sender_id',
                                $userId
                            )
                            ->orWhere(
                                'receiver_id',
                                $userId
                            );
                    }
                )
                ->with([
                    'sender:id,name,phone_number',
                    'receiver:id,name,phone_number',
                ])
                ->latest()
                ->get();

        return response()->json([
            'connections' =>
                $connections,
        ]);
    }
}