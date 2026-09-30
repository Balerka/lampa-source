<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Balerka\LaravelProxy\ProxyOptions;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Http;

class TmdbController extends Controller
{
    public function __invoke(Request $request, string $type, string $path): Response
    {
        $proxies = config('proxy.list', []);

        abort_if($proxies === [], 503, __('TMDB proxy is not configured'));
        abort_unless(preg_match('~\A[a-zA-Z0-9_./-]+\z~', $path) && ! str_contains($path, '..'), 400);

        foreach ($proxies as $proxy) {
            abort_unless(in_array(parse_url($proxy, PHP_URL_SCHEME), ['http', 'https'], true), 503, __('TMDB proxy is not configured'));
        }

        $base = $type === 'api' ? 'https://api.themoviedb.org/3/' : 'https://image.tmdb.org/';

        try {
            $upstream = Http::withMiddleware(ProxyOptions::middleware())->withOptions([
                'allow_redirects' => false,
            ])->connectTimeout(config('proxy.connect_timeout', 5))->timeout(30)->get($base.ltrim($path, '/'), $request->query());
        } catch (ConnectionException) {
            abort(502, __('TMDB connection failed'));
        }

        abort_if($upstream->redirect(), 502, __('TMDB connection failed'));

        $headers = [];

        foreach (['Content-Type', 'Cache-Control', 'ETag', 'Last-Modified', 'Retry-After'] as $header) {
            if ($value = $upstream->header($header)) {
                $headers[$header] = $value;
            }
        }

        return response($upstream->body(), $upstream->status(), $headers);
    }
}
