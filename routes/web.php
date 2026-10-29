<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;
use App\Models\Announcement;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

// Default welcome page (usually exists in Laravel)
Route::get('/', function () {
    return view('welcome');
});

/*
|--------------------------------------------------------------------------
| Admin Announcements (Owner Panel)
|--------------------------------------------------------------------------
|
| These routes allow you to open a web page in your browser and publish
| announcements that will show up in the Flutter app.
|
*/

// The Admin Page (Form)
Route::get('/admin/announcements', function () {
    return view('admin.announcements');
});

// The Submit Action (Saves to database)
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