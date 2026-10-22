<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AppRelease;
use Illuminate\Http\JsonResponse;

class AppVersionController extends Controller
{
    /**
     * Returns the latest published release.
     *
     * Public — no auth required. The client only needs a
     * version number and a download URL to decide whether
     * to prompt the user.
     */
    public function show(): JsonResponse
    {
        $latest = AppRelease::query()
            ->orderByDesc('version_code')
            ->first();

        if (!$latest) {
            return response()->json([
                'message' => 'No releases published yet.',
            ], 404);
        }

        return response()->json([
            'version_code' => $latest->version_code,
            'version_name' => $latest->version_name,
            'download_url' => $latest->download_url,
            'release_notes' => $latest->release_notes,
            'critical' => $latest->critical,
        ]);
    }
}