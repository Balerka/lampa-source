<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\Auth\DeviceCodeService;
use App\Services\ProfileService;
use App\Services\UserService;
use App\Support\LampaResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DeviceController extends Controller
{
    public function __construct(
        protected DeviceCodeService $deviceCodes,
        protected ProfileService $profiles,
        protected UserService $users,
    ) {
    }

    public function create(Request $request): JsonResponse
    {
        $payload = $this->deviceCodes->createForUser($request->user());

        return response()->json(LampaResponse::success($payload));
    }

    public function sessionCreate(Request $request): JsonResponse
    {
        $payload = $this->deviceCodes->createForUser($request->user());

        return response()->json(LampaResponse::success(array_merge($payload, [
            'email' => $request->user()->email,
        ])));
    }

    public function manual(Request $request): JsonResponse
    {
        $request->validate([
            'email' => ['required', 'email'],
            'secret' => ['nullable', 'string'],
        ]);

        $configuredSecret = config('lampa.manual_device_code_secret');
        $providedSecret = (string) ($request->header('X-Manual-Secret') ?? $request->string('secret')->toString());

        if (filled($configuredSecret) && ! hash_equals((string) $configuredSecret, $providedSecret)) {
            return response()->json(LampaResponse::error('manual_secret_invalid', __('lampa.manual_secret_invalid')), 403);
        }

        $email = $request->string('email')->toString();
        $user = $this->users->findByEmail($email);

        if (! $user) {
            return response()->json(LampaResponse::error('user_not_found', __('lampa.user_not_found')), 404);
        }

        $payload = $this->deviceCodes->createForUser($user);

        return response()->json(LampaResponse::success(array_merge($payload, ['email' => $user->email])));
    }

    public function add(Request $request): JsonResponse
    {
        $request->validate([
            'code' => ['required', 'digits:6'],
        ]);

        $auth = $this->deviceCodes->redeem($request->string('code')->toString());

        if (! $auth) {
            return response()->json(LampaResponse::error('device_code_invalid', __('lampa.device_code_invalid')), 200);
        }

        return response()->json([
            'token' => $auth['token'],
            'email' => $auth['user']->email,
            'profile' => $this->profiles->serialize($auth['profile']),
        ]);
    }
}
