<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Support\LampaResponse;
use Illuminate\Http\JsonResponse;

class MiscController extends Controller
{
    public function notice(): JsonResponse
    {
        return response()->json(LampaResponse::success([
            'notice' => [],
        ]));
    }

    public function personList(): JsonResponse
    {
        return response()->json([
            'results' => [],
        ]);
    }

    public function health(): JsonResponse
    {
        return response()->json([
            'ok' => true,
        ]);
    }
}
