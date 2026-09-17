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

        $phoneNumbers =
            collect(
                $validated['phone_numbers']
            )
            ->map(
                fn ($phone) =>
                    $this->normalizePhoneNumber(
                        $phone
                    )
            )
            ->filter()
            ->unique()
            ->values();

        if ($phoneNumbers->isEmpty()) {
            return response()->json([
                'users' => [],
            ]);
        }

        $users =
            User::query()
                ->whereIn(
                    'phone_number',
                    $phoneNumbers->all()
                )
                ->select([
                    'id',
                    'name',
                    'phone_number',
                ])
                ->get();

        return response()->json([
            'users' => $users,
        ]);
    }

    private function normalizePhoneNumber(
        string $phoneNumber
    ): ?string {
        $phoneNumber =
            preg_replace(
                '/[\s\-\(\)\.]/',
                '',
                trim($phoneNumber)
            );

        if (!$phoneNumber) {
            return null;
        }

        if (
            str_starts_with(
                $phoneNumber,
                '00'
            )
        ) {
            $phoneNumber =
                '+' .
                substr(
                    $phoneNumber,
                    2
                );
        }

        if (
            !str_starts_with(
                $phoneNumber,
                '+'
            )
        ) {
            return null;
        }

        $digits =
            substr(
                $phoneNumber,
                1
            );

        if (
            !preg_match(
                '/^\d{8,15}$/',
                $digits
            )
        ) {
            return null;
        }

        return '+' . $digits;
    }
}