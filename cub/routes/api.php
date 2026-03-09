<?php

use App\Http\Controllers\Api\BackupController;
use App\Http\Controllers\Api\BookmarkController;
use App\Http\Controllers\Api\DeviceController;
use App\Http\Controllers\Api\MiscController;
use App\Http\Controllers\Api\NotificationController;
use App\Http\Controllers\Api\ProfileController;
use App\Http\Controllers\Api\TimelineController;
use App\Http\Controllers\Api\UserController;
use Illuminate\Support\Facades\Route;

Route::post('/device/add', [DeviceController::class, 'add']);
Route::post('/device/code/manual', [DeviceController::class, 'manual']);

Route::middleware('lampa.auth')->group(function (): void {
    Route::post('/device/code/create', [DeviceController::class, 'create']);
    Route::get('/users/get', [UserController::class, 'show']);
    Route::get('/profiles/all', [ProfileController::class, 'index']);
    Route::post('/profiles/create', [ProfileController::class, 'store']);
    Route::get('/notice/all', [MiscController::class, 'notice']);
    Route::get('/person/list', [MiscController::class, 'personList']);
    Route::get('/users/backup/import', [BackupController::class, 'show']);
    Route::post('/users/backup/export', [BackupController::class, 'store']);

    Route::middleware('lampa.profile')->group(function (): void {
        Route::get('/bookmarks/dump', [BookmarkController::class, 'dump']);
        Route::get('/bookmarks/changelog', [BookmarkController::class, 'changelog']);
        Route::post('/bookmarks/add', [BookmarkController::class, 'add']);
        Route::post('/bookmarks/remove', [BookmarkController::class, 'remove']);
        Route::post('/bookmarks/clear', [BookmarkController::class, 'clear']);
        Route::post('/bookmarks/sync', [BookmarkController::class, 'sync']);

        Route::get('/timeline/dump', [TimelineController::class, 'dump']);
        Route::get('/timeline/changelog', [TimelineController::class, 'changelog']);
        Route::post('/timeline/update', [TimelineController::class, 'update']);

        Route::get('/notifications/all', [NotificationController::class, 'index']);
        Route::post('/notifications/add', [NotificationController::class, 'store']);
    });
});
