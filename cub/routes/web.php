<?php

use App\Models\Bookmark;
use App\Http\Controllers\Api\DeviceController;
use App\Http\Controllers\Api\MiscController;
use App\Http\Controllers\Settings\ProfileImageController;
use App\Services\ProfileService;
use App\Models\Timeline;
use Illuminate\Support\Facades\Route;
use Laravel\Fortify\Features;

Route::get('/health', [MiscController::class, 'health']);

Route::inertia('/', 'welcome', [
    'canRegister' => Features::enabled(Features::registration()),
    'openapiUrl' => url('/openapi.json'),
    'healthUrl' => url('/health'),
])->name('home');

Route::redirect('/swagger', '/openapi.json');

Route::middleware(['auth'])->group(function () {
    Route::get('/add/code', [DeviceController::class, 'sessionCreate'])->name('lampa.add.code');
    Route::post('/dashboard/profiles/{profile}/image', [ProfileImageController::class, 'store'])
        ->name('dashboard.profile-image.store');

    Route::inertia('/add', 'add', [
        'actionUrl' => fn () => url('/add/code'),
        'email' => fn () => request()->user()?->email,
        'ttl' => fn () => config('lampa.device_code_ttl'),
    ])->name('lampa.add');

    Route::inertia('dashboard', 'dashboard', [
        'stats' => fn () => [
            'profiles' => request()->user()?->profiles()->count() ?? 0,
            'bookmarks' => request()->user()?->bookmarks()->count() ?? 0,
            'timelines' => request()->user()?->timelines()->count() ?? 0,
            'tokens' => request()->user()?->tokens()->count() ?? 0,
        ],
        'profiles' => fn () => request()->user()?->profiles()
            ->withCount(['bookmarks', 'timelines'])
            ->orderByDesc('main')
            ->orderBy('name')
            ->get()
            ->map(fn ($profile) => [
                'id' => $profile->id,
                'userId' => $profile->user_id,
                'name' => $profile->name,
                'icon' => $profile->icon,
                'image' => app(ProfileService::class)->imagePath($profile),
                'main' => $profile->main,
                'child' => $profile->child,
                'age' => $profile->age,
                'bookmarksCount' => $profile->bookmarks_count,
                'timelinesCount' => $profile->timelines_count,
                'bookmarksVersion' => $profile->bookmarks_version,
                'timelinesVersion' => $profile->timelines_version,
                'updatedAt' => $profile->updated_at?->toIso8601String(),
            ])
            ->values() ?? [],
        'bookmarkTypes' => fn () => Bookmark::query()
            ->whereBelongsTo(request()->user())
            ->selectRaw('type, COUNT(*) as total')
            ->groupBy('type')
            ->orderByDesc('total')
            ->limit(6)
            ->get()
            ->map(fn ($bookmark) => [
                'type' => $bookmark->type,
                'total' => (int) $bookmark->total,
            ])
            ->values(),
        'recentTimelines' => fn () => Timeline::query()
            ->whereBelongsTo(request()->user())
            ->with('profile:id,name')
            ->latest()
            ->limit(6)
            ->get()
            ->map(fn ($timeline) => [
                'id' => $timeline->id,
                'hash' => $timeline->hash,
                'percent' => $timeline->percent,
                'time' => $timeline->time,
                'duration' => $timeline->duration,
                'version' => $timeline->version,
                'profileName' => $timeline->profile?->name,
                'updatedAt' => $timeline->updated_at?->toIso8601String(),
            ])
            ->values(),
    ])->name('dashboard');
});

require __DIR__.'/settings.php';
