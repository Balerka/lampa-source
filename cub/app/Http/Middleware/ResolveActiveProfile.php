<?php

namespace App\Http\Middleware;

use App\Services\ProfileService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ResolveActiveProfile
{
    public function __construct(protected ProfileService $profiles)
    {
    }

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        $profile = $this->profiles->resolveActiveProfile($user, $request->header('profile'));

        $request->attributes->set('activeProfile', $profile);

        return $next($request);
    }
}
