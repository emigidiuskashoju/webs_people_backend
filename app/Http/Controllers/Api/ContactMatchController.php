<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
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

            'phone_numbers.*' => [
                'required',
                'string',
                'max:20',
            ],
        ]);

        $phoneNumbers = collect(
            $validated['phone_numbers']
        )
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