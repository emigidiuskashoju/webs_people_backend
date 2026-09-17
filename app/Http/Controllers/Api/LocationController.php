<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ConnectionRequest;
use App\Models\PeopleLocation;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LocationController extends Controller
{
    public function share(
        Request $request
    ): JsonResponse {
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
        ]);

        $location =
            PeopleLocation::updateOrCreate(
                [
                    'user_id' =>
                        $request->user()->id,
                ],
                [
                    'latitude' =>
                        $validated['latitude'],

                    'longitude' =>
                        $validated['longitude'],

                    'accuracy' =>
                        $validated['accuracy']
                            ?? null,

                    'expires_at' =>
                        now()->addMinutes(5),
                ]
            );

        return response()->json([
            'message' =>
                'Location sharing updated.',

            'location' =>
                $location,
        ]);
    }

    public function stop(
        Request $request
    ): JsonResponse {
        PeopleLocation::where(
            'user_id',
            $request->user()->id
        )->delete();

        return response()->json([
            'message' =>
                'Location sharing stopped.',
        ]);
    }

    public function people(
        Request $request
    ): JsonResponse {
        $userId =
            $request->user()->id;

        $connectedUserIds =
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
                ->get()
                ->map(
                    function (
                        ConnectionRequest $connection
                    ) use ($userId) {
                        return $connection
                            ->sender_id ===
                            $userId
                            ? $connection
                                ->receiver_id
                            : $connection
                                ->sender_id;
                    }
                );

        $locations =
            PeopleLocation::query()
                ->whereIn(
                    'user_id',
                    $connectedUserIds
                )
                ->where(
                    'expires_at',
                    '>',
                    now()
                )
                ->with([
                    'user:id,name,phone_number',
                ])
                ->get();

        return response()->json([
            'locations' =>
                $locations,
        ]);
    }
}