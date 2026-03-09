<?php

use App\Http\Controllers\Api\DeviceController;
use App\Http\Controllers\Api\MiscController;
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

    Route::inertia('/add', 'add', [
        'actionUrl' => fn () => url('/add/code'),
        'email' => fn () => request()->user()?->email,
        'ttl' => fn () => config('lampa.device_code_ttl'),
    ])->name('lampa.add');

    Route::inertia('dashboard', 'dashboard')->name('dashboard');
});

require __DIR__.'/settings.php';
