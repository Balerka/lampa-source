<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\ProfileService;
use App\Support\LampaResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProfileController extends Controller
{
    public function __construct(protected ProfileService $profiles)
    {
    }

    public function index(Request $request): JsonResponse
    {
        return response()->json(LampaResponse::success([
            'profiles' => $this->profiles->all($request->user()),
        ]));
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:64'],
        ]);

        $profile = $this->profiles->create($request->user(), $validated['name']);

        return response()->json(LampaResponse::success([
            'profile' => $this->profiles->serialize($profile),
        ]));
    }
}
