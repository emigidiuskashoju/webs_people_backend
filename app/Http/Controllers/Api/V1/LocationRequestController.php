<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\LocationCode;
use App\Models\LocationRequest;
use App\Models\SharedLocation;
use Illuminate\Http\Request;

class LocationRequestController extends Controller
{
    // ============================================================
    // DAILY CODE
    // ============================================================

    public function generateCode(Request $request)
    {
        $user = $request->user();

        if (!$user) {
            return response()->json([
                'message' => 'Unauthenticated.',
            ], 401);
        }

        if (!$user->email) {
            return response()->json([
                'message' => 'Your account does not have an email address.',
            ], 422);
        }

        $secret = config('app.daily_code_secret');

        if (!$secret) {
            return response()->json([
                'message' => 'Daily code service is not configured.',
            ], 500);
        }

        $today = now()->toDateString();
        $email = mb_strtolower(trim($user->email));
        $message = $email . '|' . $today;

        $hash = hash_hmac('sha256', $message, $secret);

        $number = hexdec(substr($hash, 0, 12));

        $code = str_pad(
            (string) ($number % 1000000),
            6,
            '0',
            STR_PAD_LEFT
        );

        $expiresAt = now()->copy()->endOfDay();

        LocationCode::updateOrCreate(
            ['user_id' => $user->id],
            [
                'code' => hash('sha256', $code),
                'expires_at' => $expiresAt,
            ]
        );

        return response()->json([
            'message' => 'Daily location code ready.',
            'code' => $code,
            'expires_at' => $expiresAt->toISOString(),
        ]);
    }

    // ============================================================
    // REQUEST LOCATION
    // ============================================================

    public function requestLocation(Request $request)
    {
        $validated = $request->validate([
            'code' => [
                'required',
                'string',
                'size:6',
                'regex:/^[0-9]{6}$/',
            ],
        ]);

        $requester = $request->user();

        $codeHash = hash(
            'sha256',
            $validated['code']
        );

        $locationCode = LocationCode::with('user')
            ->where('code', $codeHash)
            ->where('expires_at', '>', now())
            ->first();

        if (!$locationCode) {
            return response()->json([
                'message' => 'The location code is invalid or expired.',
            ], 422);
        }

        $owner = $locationCode->user;

        if (!$owner) {
            return response()->json([
                'message' =>
                    'The owner of this location code no longer exists.',
            ], 404);
        }

        if ($owner->id === $requester->id) {
            return response()->json([
                'message' => 'You cannot request your own location.',
            ], 422);
        }

        $existing = LocationRequest::where(
                'requester_id',
                $requester->id
            )
            ->where('owner_id', $owner->id)
            ->where('status', 'pending')
            ->where(function ($query) {
                $query
                    ->whereNull('expires_at')
                    ->orWhere('expires_at', '>', now());
            })
            ->latest('id')
            ->first();

        if ($existing) {
            return response()->json([
                'message' => 'A location request is already pending.',
                'request' => $this->requestPayload(
                    $existing->load(['requester', 'owner', 'sharedLocations'])
                ),
            ]);
        }

        $locationRequest = LocationRequest::create([
            'requester_id' => $requester->id,
            'owner_id' => $owner->id,
            'status' => 'pending',
            'expires_at' => now()->addHour(),
        ]);

        return response()->json([
            'message' => 'Location request sent in Webs People.',
            'request' => $this->requestPayload(
                $locationRequest->load(['requester', 'owner', 'sharedLocations'])
            ),
        ], 201);
    }

    // ============================================================
    // PENDING REQUESTS
    // ============================================================

    public function pendingRequests(Request $request)
    {
        $ownerId = $request->user()->id;

        $this->expireOldRequests(ownerId: $ownerId);

        $requests = LocationRequest::with([
                'requester:id,name',
                'sharedLocations',
            ])
            ->where('owner_id', $ownerId)
            ->where('status', 'pending')
            ->where(function ($query) {
                $query
                    ->whereNull('expires_at')
                    ->orWhere('expires_at', '>', now());
            })
            ->latest('id')
            ->get()
            ->map(fn (LocationRequest $item) =>
                $this->requestPayload($item)
            )
            ->values();

        return response()->json([
            'requests' => $requests,
        ]);
    }

    // ============================================================
    // CONVERSATION REQUESTS
    // ============================================================

    public function conversationRequests(
        Request $request,
        int $userId
    ) {
        $currentUserId = $request->user()->id;

        $this->expireOldRequests(ownerId: $currentUserId);
        $this->expireOldRequesterRequests(
            requesterId: $currentUserId
        );

        $requests = LocationRequest::with([
                'requester:id,name',
                'owner:id,name',
                'sharedLocations',
            ])
            ->where(function ($query) use (
                $currentUserId,
                $userId
            ) {
                $query
                    ->where('requester_id', $currentUserId)
                    ->where('owner_id', $userId);
            })
            ->orWhere(function ($query) use (
                $currentUserId,
                $userId
            ) {
                $query
                    ->where('requester_id', $userId)
                    ->where('owner_id', $currentUserId);
            })
            ->latest('id')
            ->limit(20)
            ->get()
            ->map(fn (LocationRequest $item) =>
                $this->requestPayload($item)
            )
            ->values();

        return response()->json([
            'requests' => $requests,
        ]);
    }

    // ============================================================
    // ACCEPT
    // ============================================================

    public function accept(Request $request, int $requestId)
    {
        $locationRequest = LocationRequest::where('id', $requestId)
            ->where('owner_id', $request->user()->id)
            ->first();

        if (!$locationRequest) {
            return response()->json([
                'message' => 'Location request not found.',
            ], 404);
        }

        if (
            $locationRequest->expires_at &&
            $locationRequest->expires_at->isPast()
        ) {
            $locationRequest->update(['status' => 'expired']);

            return response()->json([
                'message' => 'This location request has expired.',
            ], 422);
        }

        if ($locationRequest->status !== 'pending') {
            return response()->json([
                'message' =>
                    'This location request is no longer pending.',
            ], 422);
        }

        $locationRequest->update([
            'status' => 'accepted',
            // Legacy columns: leave as null. Two-way mode
            // uses the shared_locations table instead.
            'latitude' => null,
            'longitude' => null,
            'accuracy' => null,
            'location_updated_at' => null,
        ]);

        return response()->json([
            'message' => 'Location sharing accepted.',
            'request' => $this->requestPayload(
                $locationRequest
                    ->fresh()
                    ->load(['requester', 'owner', 'sharedLocations'])
            ),
        ]);
    }

    // ============================================================
    // DENY
    // ============================================================

    public function deny(Request $request, int $requestId)
    {
        $locationRequest = LocationRequest::where('id', $requestId)
            ->where('owner_id', $request->user()->id)
            ->first();

        if (!$locationRequest) {
            return response()->json([
                'message' => 'Location request not found.',
            ], 404);
        }

        if ($locationRequest->status !== 'pending') {
            return response()->json([
                'message' =>
                    'This location request is no longer pending.',
            ], 422);
        }

        $locationRequest->update(['status' => 'denied']);

        return response()->json([
            'message' => 'Location request denied.',
            'request' => $this->requestPayload(
                $locationRequest
                    ->fresh()
                    ->load(['requester', 'owner', 'sharedLocations'])
            ),
        ]);
    }

    // ============================================================
    // UPDATE LOCATION (both parties)
    // ============================================================
    //
    // Called by either the requester OR the owner while the
    // request is accepted. Each call writes a row in
    // shared_locations for the authenticated user.
    // ============================================================

    public function updateLocation(
        Request $request,
        int $requestId
    ) {
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

        $userId = $request->user()->id;

        $locationRequest = LocationRequest::where('id', $requestId)
            ->where(function ($query) use ($userId) {
                $query
                    ->where('requester_id', $userId)
                    ->orWhere('owner_id', $userId);
            })
            ->first();

        if (!$locationRequest) {
            return response()->json([
                'message' => 'Location request not found.',
            ], 404);
        }

        if ($locationRequest->status !== 'accepted') {
            return response()->json([
                'message' => 'Location sharing is not active.',
            ], 422);
        }

        if (
            $locationRequest->expires_at &&
            $locationRequest->expires_at->isPast()
        ) {
            $locationRequest->update(['status' => 'expired']);

            return response()->json([
                'message' => 'Location sharing has expired.',
            ], 422);
        }

        SharedLocation::updateOrCreate(
            [
                'location_request_id' => $locationRequest->id,
                'user_id' => $userId,
            ],
            [
                'latitude' => $validated['latitude'],
                'longitude' => $validated['longitude'],
                'accuracy' => $validated['accuracy'] ?? null,
                'location_updated_at' => now(),
            ]
        );

        return response()->json([
            'message' => 'Location updated.',
            'location' => [
                'latitude' => $validated['latitude'],
                'longitude' => $validated['longitude'],
                'accuracy' => $validated['accuracy'] ?? null,
                'updated_at' => now()->toISOString(),
            ],
        ]);
    }

    // ============================================================
    // GET THE OTHER PERSON'S LOCATION
    // ============================================================
    //
    // Called by either the requester or the owner. Returns the
    // coordinates of the *other* party.
    // ============================================================

    public function getLocation(
        Request $request,
        int $requestId
    ) {
        $userId = $request->user()->id;

        $locationRequest = LocationRequest::where('id', $requestId)
            ->where(function ($query) use ($userId) {
                $query
                    ->where('requester_id', $userId)
                    ->orWhere('owner_id', $userId);
            })
            ->with(['sharedLocations'])
            ->first();

        if (!$locationRequest) {
            return response()->json([
                'message' => 'Location request not found.',
            ], 404);
        }

        if (
            $locationRequest->expires_at &&
            $locationRequest->expires_at->isPast()
        ) {
            if ($locationRequest->status === 'accepted') {
                $locationRequest->update(['status' => 'expired']);
            }

            return response()->json([
                'message' => 'Location sharing has expired.',
            ], 422);
        }

        if ($locationRequest->status !== 'accepted') {
            return response()->json([
                'message' => 'Location has not been accepted.',
            ], 422);
        }

        $other = $locationRequest
            ->sharedLocations
            ->firstWhere(
                fn (SharedLocation $row) =>
                    $row->user_id !== $userId
            );

        if (
            !$other ||
            $other->latitude === null ||
            $other->longitude === null
        ) {
            return response()->json([
                'message' =>
                    'The current location is not available yet.',
                'location' => null,
            ]);
        }

        return response()->json([
            'location' => [
                'latitude' => $other->latitude,
                'longitude' => $other->longitude,
                'accuracy' => $other->accuracy,
                'updated_at' =>
                    $other->location_updated_at?->toISOString(),
            ],
        ]);
    }

    // ============================================================
    // SHARE LOCATION (broadcast)
    // ============================================================

    public function shareLocation(Request $request)
    {
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

        $userId = $request->user()->id;

        $accepted = LocationRequest::where(function ($query) use ($userId) {
                $query
                    ->where('requester_id', $userId)
                    ->orWhere('owner_id', $userId);
            })
            ->where('status', 'accepted')
            ->where(function ($query) {
                $query
                    ->whereNull('expires_at')
                    ->orWhere('expires_at', '>', now());
            })
            ->get();

        foreach ($accepted as $locationRequest) {
            SharedLocation::updateOrCreate(
                [
                    'location_request_id' => $locationRequest->id,
                    'user_id' => $userId,
                ],
                [
                    'latitude' => $validated['latitude'],
                    'longitude' => $validated['longitude'],
                    'accuracy' => $validated['accuracy'] ?? null,
                    'location_updated_at' => now(),
                ]
            );
        }

        return response()->json([
            'message' => 'Location shared.',
            'updated_requests' => $accepted->count(),
            'location' => [
                'latitude' => $validated['latitude'],
                'longitude' => $validated['longitude'],
                'accuracy' => $validated['accuracy'] ?? null,
                'updated_at' => now()->toISOString(),
            ],
        ]);
    }

    // ============================================================
    // STOP SHARING
    // ============================================================

    public function stopSharing(Request $request)
    {
        $userId = $request->user()->id;

        $accepted = LocationRequest::where(function ($query) use ($userId) {
                $query
                    ->where('requester_id', $userId)
                    ->orWhere('owner_id', $userId);
            })
            ->where('status', 'accepted')
            ->pluck('id');

        SharedLocation::whereIn(
                'location_request_id',
                $accepted
            )
            ->where('user_id', $userId)
            ->delete();

        return response()->json([
            'message' => 'Location sharing stopped.',
            'updated_requests' => $accepted->count(),
        ]);
    }

    // ============================================================
    // PEOPLE LOCATIONS
    // ============================================================

    public function peopleLocations(Request $request)
    {
        $userId = $request->user()->id;

        $this->expireOldRequesterRequests($userId);
        $this->expireOldRequests($userId);

        $requests = LocationRequest::with([
                'requester:id,name',
                'owner:id,name',
                'sharedLocations',
            ])
            ->where(function ($query) use ($userId) {
                $query
                    ->where('requester_id', $userId)
                    ->orWhere('owner_id', $userId);
            })
            ->where('status', 'accepted')
            ->where(function ($query) {
                $query
                    ->whereNull('expires_at')
                    ->orWhere('expires_at', '>', now());
            })
            ->latest('location_updated_at')
            ->get();

        $locations = $requests
            ->map(function (LocationRequest $item) use ($userId) {
                $other = $item->sharedLocations
                    ->firstWhere(
                        fn (SharedLocation $row) =>
                            $row->user_id !== $userId
                    );

                if (!$other) {
                    return null;
                }

                $otherUser = $other->user_id === $item->requester_id
                    ? $item->requester
                    : $item->owner;

                return [
                    'request_id' => $item->id,
                    'user_id' => $other->user_id,
                    'user_name' => $otherUser?->name,
                    'latitude' => $other->latitude,
                    'longitude' => $other->longitude,
                    'accuracy' => $other->accuracy,
                    'updated_at' =>
                        $other->location_updated_at?->toISOString(),
                ];
            })
            ->filter()
            ->values();

        return response()->json([
            'locations' => $locations,
        ]);
    }

    // ============================================================
    // PRIVATE HELPERS
    // ============================================================

    private function requestPayload(
        LocationRequest $item
    ): array {
        $shared = [];

        foreach ($item->sharedLocations as $row) {
            $shared[] = [
                'user_id' => $row->user_id,
                'latitude' => $row->latitude,
                'longitude' => $row->longitude,
                'accuracy' => $row->accuracy,
                'updated_at' =>
                    $row->location_updated_at?->toISOString(),
            ];
        }

        return [
            'id' => $item->id,
            'requester_id' => $item->requester_id,
            'owner_id' => $item->owner_id,
            'requester_name' => $item->requester?->name,
            'owner_name' => $item->owner?->name,
            'status' => $item->status,
            'created_at' => $item->created_at?->toISOString(),
            'expires_at' => $item->expires_at?->toISOString(),
            'shared_locations' => $shared,
        ];
    }

    private function expireOldRequests(int $ownerId): void
    {
        LocationRequest::where('owner_id', $ownerId)
            ->where('status', 'pending')
            ->where('expires_at', '<=', now())
            ->update(['status' => 'expired']);
    }

    private function expireOldRequesterRequests(
        int $requesterId
    ): void {
        LocationRequest::where('requester_id', $requesterId)
            ->whereIn('status', ['pending', 'accepted'])
            ->where('expires_at', '<=', now())
            ->update(['status' => 'expired']);
    }
}