<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\BackupService;
use App\Support\LampaResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BackupController extends Controller
{
    public function __construct(protected BackupService $backups)
    {
    }

    public function show(Request $request): JsonResponse
    {
        return response()->json([
            'data' => $this->backups->latest($request->user()),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'file' => ['required', 'file'],
        ]);

        $this->backups->store($request->user(), $request->file('file'));

        return response()->json(LampaResponse::success([
            'limited' => false,
        ]));
    }
}
