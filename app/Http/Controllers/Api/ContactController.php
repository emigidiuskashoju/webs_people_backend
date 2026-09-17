<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ContactController extends Controller
{
    public function match(
        Request $request
    ): JsonResponse {
        $validated = $request->validate([
            'phone_numbers' => [
                'required',
                'array',
                'max:1000',
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
            ->map(function ($phoneNumber) {
                return preg_replace(
                    '/[\s\-\(\)]/',
                    '',
                    trim($phoneNumber)
                );
            })
            ->filter()
            ->unique()
            ->values();

        $users = User::query()
            ->whereIn(
                'phone_number',
                $phoneNumbers
            )
            ->select([
                'id',
                'name',
                'phone_number',
            ])
            ->get();

        return response()->json([
            'matches' => $users,
        ]);
    }
}