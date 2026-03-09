<?php

namespace App\Http\Middleware;

use App\Services\UserService;
use App\Support\LampaResponse;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AuthenticateLampaToken
{
    public function __construct(protected UserService $users)
    {
    }

    public function handle(Request $request, Closure $next): Response
    {
        $user = $this->users->resolveByToken($request->header('token'));

        if (! $user) {
            return response()->json(LampaResponse::error('token_invalid', 'A valid token header is required.'), 401);
        }

        $request->setUserResolver(fn () => $user);

        return $next($request);
    }
}
