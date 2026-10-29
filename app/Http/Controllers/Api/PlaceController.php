<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Place;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class PlaceController extends Controller
{
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'latitude' => 'required|numeric',
            'longitude' => 'required|numeric',
            'photo' => 'nullable|image|max:5120',
            'logo' => 'nullable|image|max:2048',
        ]);

        $baseSlug = Str::slug($request->name);
        $slug = $baseSlug . rand(1000, 9999);

        while (Place::where('slug', $slug)->exists()) {
            $slug = $baseSlug . rand(10000, 99999);
        }

        $photoUrl = null;
        if ($request->hasFile('photo')) {
            $path = $request->file('photo')->store('places/photos', 'public');
            $photoUrl = asset('storage/' . $path);
        }

        $logoUrl = null;
        if ($request->hasFile('logo')) {
            $path = $request->file('logo')->store('places/logos', 'public');
            $logoUrl = asset('storage/' . $path);
        }

        $place = Place::create([
            'name' => $request->name,
            'slug' => $slug,
            'latitude' => $request->latitude,
            'longitude' => $request->longitude,
            'photo_url' => $photoUrl,
            'logo_url' => $logoUrl,
        ]);

        return response()->json([
            'status' => 'success',
            'data' => $place,
            'share_link' => 'https://webzapp.org/p/' . $slug
        ]);
    }

    public function show($slug)
    {
        $place = Place::where('slug', $slug)->first();

        if (!$place) {
            return response()->json(['status' => 'error', 'message' => 'Place not found'], 404);
        }

        return response()->json([
            'status' => 'success',
            'data' => $place
        ]);
    }
}