<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Models\Profile;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ProfileImageController extends Controller
{
    public function store(Request $request, Profile $profile): RedirectResponse
    {
        abort_unless($profile->user_id === $request->user()?->id, 404);

        $validated = $request->validate([
            'image' => ['required', 'image', 'max:5120', 'mimetypes:image/webp'],
        ]);

        Storage::disk('public')->putFileAs(
            'profiles/'.$profile->user_id,
            $validated['image'],
            $profile->icon.'.webp',
        );

        return back();
    }
}
