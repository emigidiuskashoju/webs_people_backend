<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;
use App\Models\Announcement;

// Default welcome page
Route::get('/', function () {
    return view('welcome');
});

// Admin Page (Form)
Route::get('/admin/announcements', function () {
    return view('admin.announcements');
});

// Submit Action
Route::post('/admin/announcements', function (Request $request) {
    $request->validate([
        'title' => 'required|string|max:255',
        'body' => 'required|string',
        'image_url' => 'nullable|url',
    ]);

    Announcement::create([
        'title' => $request->title,
        'body' => $request->body,
        'image_url' => $request->image_url,
        'is_active' => true,
    ]);

    return back()->with('success', 'Announcement published! It will appear on users phones immediately.');
});
