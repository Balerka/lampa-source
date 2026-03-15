<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\StorageSyncService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class StorageController extends Controller
{
    public function __construct(protected StorageSyncService $storage)
    {
    }

    public function show(Request $request, string $name, string $classType): JsonResponse
    {
        return response()->json($this->storage->dump(
            $request->attributes->get('activeProfile'),
            $name,
            $classType,
        ));
    }
}
