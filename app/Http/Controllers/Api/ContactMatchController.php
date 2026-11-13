<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\LocationRequest;
use App\Models\PendingMessage;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ContactMatchController extends Controller
{
    public function match(
        Request $request
    ): JsonResponse {
        $validated = $request->validate([
            'phone_numbers' => [
                'required',
                'array',
                'max:5000',
            ],

            // Accept any string; over-long / malformed entries are
            // dropped below instead of failing the whole request (an
            // old app could send a bad number and get a 422, which
            // stopped ALL contacts from loading).
            'phone_numbers.*' => [
                'nullable',
                'string',
            ],
        ]);

        $phoneNumbers = collect(
            $validated['phone_numbers']
        )
            ->filter(fn ($phone) =>
                is_string($phone) && strlen($phone) <= 20)
            ->map(
                fn($phone) =>
                    $this->normalizePhoneNumber($phone)
            )
            ->filter()
            ->unique()
            ->values();

        if ($phoneNumbers->isEmpty()) {
            return response()->json([
                'users' => [],
            ]);
        }

        $users = User::query()
            ->whereIn(
                'phone_number',
                $phoneNumbers->all()
            )
            ->select([
                'id',
                'name',
                'phone_number',
                'profile_photo_path',
            ])
            ->get()
            ->map(function (User $user) {
                return [
                    'id' => $user->id,
                    'name' => $user->name,
                    'phone_number' => $user->phone_number,
                    'profile_photo_url' =>
                        $this->profilePhotoUrl($user),
                ];
            })
            ->values();

        return response()->json([
            'users' => $users,
        ]);
    }

    /**
     * Look up Webs users by ID, for people who are NOT in the
     * caller's phone contacts but have chatted with them.
     *
     * When someone messages you and you don't have their number
     * saved, the app only knows their user ID. It calls this to
     * get their name, number and photo so the chat can appear in
     * the chat list.
     *
     * Privacy: only returns users who have exchanged a message or
     * a location request with the caller. Any other ID is silently
     * left out, so this can't be used to browse users.
     */
    public function lookup(
        Request $request
    ): JsonResponse {
        $validated = $request->validate([
            'user_ids' => [
                'required',
                'array',
                'max:200',
            ],
            'user_ids.*' => [
                'required',
                'integer',
            ],
        ]);

        $me = (int) $request->user()->id;

        $ids = collect($validated['user_ids'])
            ->map(fn ($id) => (int) $id)
            ->reject(fn ($id) => $id === $me)
            ->unique()
            ->values();

        if ($ids->isEmpty()) {
            return response()->json(['users' => []]);
        }

        // People I have messaged, or who messaged me.
        $messaged = PendingMessage::query()
            ->where(function ($q) use ($me, $ids) {
                $q->where('recipient_id', $me)
                    ->whereIn('sender_id', $ids);
            })
            ->orWhere(function ($q) use ($me, $ids) {
                $q->where('sender_id', $me)
                    ->whereIn('recipient_id', $ids);
            })
            ->get(['sender_id', 'recipient_id'])
            ->flatMap(fn ($m) => [
                (int) $m->sender_id,
                (int) $m->recipient_id,
            ]);

        // People I have shared location with.
        $located = LocationRequest::query()
            ->where(function ($q) use ($me, $ids) {
                $q->where('owner_id', $me)
                    ->whereIn('requester_id', $ids);
            })
            ->orWhere(function ($q) use ($me, $ids) {
                $q->where('requester_id', $me)
                    ->whereIn('owner_id', $ids);
            })
            ->get(['requester_id', 'owner_id'])
            ->flatMap(fn ($r) => [
                (int) $r->requester_id,
                (int) $r->owner_id,
            ]);

        $allowed = $messaged
            ->merge($located)
            ->unique()
            ->intersect($ids)
            ->values();

        if ($allowed->isEmpty()) {
            return response()->json(['users' => []]);
        }

        $users = User::query()
            ->whereIn('id', $allowed->all())
            ->select([
                'id',
                'name',
                'phone_number',
                'profile_photo_path',
            ])
            ->get()
            ->map(fn (User $user) => [
                'id' => $user->id,
                'name' => $user->name,
                'phone_number' => $user->phone_number,
                'profile_photo_url' =>
                    $this->profilePhotoUrl($user),
            ])
            ->values();

        return response()->json([
            'users' => $users,
        ]);
    }

    private function normalizePhoneNumber(
        string $phoneNumber
    ): ?string {
        $phoneNumber = preg_replace(
            '/[\s\-\(\)\.]/',
            '',
            trim($phoneNumber)
        );

        if (!$phoneNumber) {
            return null;
        }

        // Convert 00 prefix to +
        if (str_starts_with($phoneNumber, '00')) {
            $phoneNumber = '+' . substr($phoneNumber, 2);
        }

        // Local format: 0XXXXXXXXX -> +255XXXXXXXXX
        if (str_starts_with($phoneNumber, '0')) {
            $phoneNumber = '+255' . substr($phoneNumber, 1);
        }

        // Saved without the 0: 712345678 -> +255712345678
        if (preg_match('/^[67]\d{8}$/', $phoneNumber)) {
            $phoneNumber = '+255' . $phoneNumber;
        }

        // Code plus the local 0: +2550712345678 -> +255712345678
        if (preg_match('/^\+?2550(\d{9})$/', $phoneNumber, $m)) {
            $phoneNumber = '+255' . $m[1];
        }

        // Country code without +: 255XXXXXXXXX -> +255XXXXXXXXX
        if (!str_starts_with($phoneNumber, '+')) {
            if (preg_match('/^\d{10,15}$/', $phoneNumber)) {
                $phoneNumber = '+' . $phoneNumber;
            } else {
                return null;
            }
        }

        $digits = substr($phoneNumber, 1);

        if (!preg_match('/^\d{8,15}$/', $digits)) {
            return null;
        }

        return '+' . $digits;
    }
    private function profilePhotoUrl(
        User $user
    ): ?string {
        if (
            empty(
            $user->profile_photo_path
        )
        ) {
            return null;
        }

        return url(
            '/storage/' .
            ltrim(
                $user->profile_photo_path,
                '/'
            )
        );
    }
}