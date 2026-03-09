<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\UserService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class UserController extends Controller
{
    public function __construct(protected UserService $users)
    {
    }

    public function show(Request $request): JsonResponse
    {
        return response()->json([
            'user' => $this->users->payload($request->user()),
        ]);
    }
}
