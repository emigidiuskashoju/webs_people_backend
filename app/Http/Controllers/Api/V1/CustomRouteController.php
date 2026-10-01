<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\CustomRoute;
use App\Models\LocationRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CustomRouteController extends Controller
{
    public function store(
        Request $request
    ): JsonResponse {
        $validated = $request->validate([
            'location_request_id' => [
                'required',
                'integer',
                'exists:location_requests,id',
            ],

            'points' => [
                'required',
                'array',
                'min:2',
                'max:200',
            ],

            'points.*.latitude' => [
                'required',
                'numeric',
                'between:-90,90',
            ],

            'points.*.longitude' => [
                'required',
                'numeric',
                'between:-180,180',
            ],

            'points.*.order' => [
                'required',
                'integer',
                'min:1',
            ],
        ]);

        $user = $request->user();

        $locationRequest =
            LocationRequest::query()
                ->where(
                    'id',
                    $validated['location_request_id']
                )
                ->where(
                    'status',
                    'accepted'
                )
                ->where(function ($query) use ($user) {
                    $query
                        ->where(
                            'requester_id',
                            $user->id
                        )
                        ->orWhere(
                            'owner_id',
                            $user->id
                        );
                })
                ->first();

        if (!$locationRequest) {
            return response()->json([
                'message' =>
                    'You do not have an accepted location connection with this user.',
            ], 403);
        }

        $points = collect(
            $validated['points']
        )
            ->sortBy('order')
            ->values()
            ->map(function ($point, $index) {
                return [
                    'latitude' =>
                        (float) $point['latitude'],

                    'longitude' =>
                        (float) $point['longitude'],

                    'order' =>
                        $index + 1,
                ];
            })
            ->values()
            ->all();

        if (count($points) < 2) {
            return response()->json([
                'message' =>
                    'At least two route points are required.',
            ], 422);
        }

        $totalDistance =
            $this->calculateDistance(
                $points
            );

        $ownerId =
            (int) $locationRequest->requester_id;

        $recipientId =
            (int) $locationRequest->owner_id;

        /*
         * The person creating the route is
         * the route owner.
         *
         * The other connected person is
         * the route recipient.
         */
        if ($user->id === $recipientId) {
            $ownerId =
                (int) $locationRequest->owner_id;

            $recipientId =
                (int) $locationRequest->requester_id;
        }

        /*
         * Replace the existing route for this
         * location connection.
         *
         * This keeps server storage minimal.
         */
        CustomRoute::updateOrCreate(
            [
                'location_request_id' =>
                    $locationRequest->id,
            ],
            [
                'owner_id' =>
                    $ownerId,

                'recipient_id' =>
                    $recipientId,

                'points' =>
                    $points,

                'total_distance_meters' =>
                    $totalDistance,
            ]
        );

        $route =
            CustomRoute::query()
                ->where(
                    'location_request_id',
                    $locationRequest->id
                )
                ->firstOrFail();

        return response()->json([
            'message' =>
                'Custom route generated successfully.',

            'route' =>
                $route,
        ]);
    }

    public function show(
        Request $request,
        int $locationRequestId
    ): JsonResponse {
        $user = $request->user();

        $locationRequest =
            LocationRequest::query()
                ->where(
                    'id',
                    $locationRequestId
                )
                ->where(
                    'status',
                    'accepted'
                )
                ->where(function ($query) use ($user) {
                    $query
                        ->where(
                            'requester_id',
                            $user->id
                        )
                        ->orWhere(
                            'owner_id',
                            $user->id
                        );
                })
                ->first();

        if (!$locationRequest) {
            return response()->json([
                'message' =>
                    'You do not have access to this route.',
            ], 403);
        }

        $route =
            CustomRoute::query()
                ->where(
                    'location_request_id',
                    $locationRequest->id
                )
                ->first();

        if (!$route) {
            return response()->json([
                'route' => null,
            ]);
        }

        return response()->json([
            'route' => $route,
        ]);
    }

    public function destroy(
        Request $request,
        int $locationRequestId
    ): JsonResponse {
        $user = $request->user();

        $locationRequest =
            LocationRequest::query()
                ->where(
                    'id',
                    $locationRequestId
                )
                ->where(
                    'status',
                    'accepted'
                )
                ->where(function ($query) use ($user) {
                    $query
                        ->where(
                            'requester_id',
                            $user->id
                        )
                        ->orWhere(
                            'owner_id',
                            $user->id
                        );
                })
                ->first();

        if (!$locationRequest) {
            return response()->json([
                'message' =>
                    'You do not have access to this route.',
            ], 403);
        }

        CustomRoute::query()
            ->where(
                'location_request_id',
                $locationRequest->id
            )
            ->delete();

        return response()->json([
            'message' =>
                'Custom route removed.',
        ]);
    }

    private function calculateDistance(
        array $points
    ): float {
        $total = 0.0;

        for (
            $i = 0;
            $i < count($points) - 1;
            $i++
        ) {
            $total += $this->haversine(
                $points[$i]['latitude'],
                $points[$i]['longitude'],
                $points[$i + 1]['latitude'],
                $points[$i + 1]['longitude']
            );
        }

        return round(
            $total,
            2
        );
    }

  private function haversine(
    float $lat1,
    float $lon1,
    float $lat2,
    float $lon2
): float {
    $earthRadius = 6371000;

    $lat1 = deg2rad($lat1);
    $lat2 = deg2rad($lat2);

    $deltaLat =
        $lat2 - $lat1;

    $deltaLon =
        deg2rad($lon2 - $lon1);

    $a =
        sin($deltaLat / 2) ** 2
        +
        cos($lat1)
            * cos($lat2)
            * sin($deltaLon / 2) ** 2;

    $c =
        2 * atan2(
            sqrt($a),
            sqrt(1 - $a)
        );

    return $earthRadius * $c;
}
}