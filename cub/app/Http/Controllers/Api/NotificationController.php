<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\NotificationService;
use App\Support\LampaResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function __construct(protected NotificationService $notifications)
    {
    }

    public function index(Request $request): JsonResponse
    {
        return response()->json([
            'notifications' => $this->notifications->all(
                $request->user(),
                $request->attributes->get('activeProfile'),
            ),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $payload = $request->validate([
            'voice' => ['nullable', 'string', 'max:191'],
            'data' => ['required'],
            'episode' => ['nullable', 'integer'],
            'season' => ['nullable', 'integer'],
        ]);

        $this->notifications->add(
            $request->user(),
            $request->attributes->get('activeProfile'),
            $payload,
        );

        return response()->json(LampaResponse::success([
            'limited' => false,
        ]));
    }
}
