<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ProfileController extends Controller
{
    /**
     * Return the authenticated user's profile.
     */
    public function show(
        Request $request
    ): JsonResponse {
        $user = $request->user();

        return response()->json([
            'user' => $this->profileData($user),
        ]);
    }

    /**
     * Update the authenticated user's name.
     *
     * Email and phone are intentionally
     * not accepted here.
     */
    public function update(
        Request $request
    ): JsonResponse {
        $validated = $request->validate([
            'name' => [
                'required',
                'string',
                'min:2',
                'max:100',
            ],
        ]);

        $user = $request->user();

        $user->name = trim(
            $validated['name']
        );

        $user->save();

        return response()->json([
            'message' =>
                'Profile updated successfully.',

            'user' =>
                $this->profileData($user),
        ]);
    }

    /**
     * Upload a new profile photo.
     */
    public function uploadPhoto(
        Request $request
    ): JsonResponse {
        $request->validate([
            'photo' => [
                'required',
                'file',
                'image',
                'mimes:jpg,jpeg,png,webp',
                'max:5120',
            ],
        ]);

        $user = $request->user();

        /*
         * Delete the previous photo if one exists.
         */
        if ($user->profile_photo_path) {
            Storage::disk('public')->delete(
                $user->profile_photo_path
            );
        }

        /*
         * Store the new photo.
         */
        $path = $request
            ->file('photo')
            ->store(
                'profile-photos',
                'public'
            );

        $user->profile_photo_path = $path;

        $user->save();

        return response()->json([
            'message' =>
                'Profile photo updated successfully.',

            'user' =>
                $this->profileData($user),
        ]);
    }

    /**
     * Remove the authenticated user's
     * profile photo.
     */
    public function removePhoto(
        Request $request
    ): JsonResponse {
        $user = $request->user();

        if ($user->profile_photo_path) {
            Storage::disk('public')->delete(
                $user->profile_photo_path
            );
        }

        $user->profile_photo_path = null;

        $user->save();

        return response()->json([
            'message' =>
                'Profile photo removed successfully.',

            'user' =>
                $this->profileData($user),
        ]);
    }

    /**
     * Format profile data returned to
     * the Flutter application.
     */
    private function profileData($user): array
{
    $photoUrl = null;

    if ($user->profile_photo_path) {
        $photoUrl = url(
            '/storage/' .
            ltrim(
                $user->profile_photo_path,
                '/'
            )
        );
    }

    return [
        'id' => $user->id,
        'name' => $user->name,
        'email' => $user->email,
        'phone' => $user->phone,
        'profile_photo_url' => $photoUrl,
    ];
}
}