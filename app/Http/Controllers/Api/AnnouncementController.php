<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Announcement;

class AnnouncementController extends Controller
{
    public function latest()
    {
        // Get the most recent active announcement
        $announcement = Announcement::where('is_active', true)
                            ->latest()
                            ->first();

        if (!$announcement) {
            return response()->json(['status' => 'no_announcements']);
        }

        return response()->json([
            'status' => 'success',
            'data' => [
                'id' => $announcement->id,
                'title' => $announcement->title,
                'body' => $announcement->body,
                'image_url' => $announcement->image_url,
            ]
        ]);
    }
}