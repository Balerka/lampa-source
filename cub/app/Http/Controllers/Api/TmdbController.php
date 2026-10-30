<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\TmdbProxyResolver;
use Balerka\LaravelProxy\ProxyOptions;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class TmdbController extends Controller
{
    public function __invoke(Request $request, string $type, string $path, TmdbProxyResolver $resolver): Response
    {
        $proxies = config('proxy.list', []);

        abort_if($proxies === [], 503, __('TMDB proxy is not configured'));
        abort_unless(preg_match('~\A[a-zA-Z0-9_./-]+\z~', $path) && ! str_contains($path, '..'), 400);

        foreach ($proxies as $proxy) {
            abort_unless(in_array(parse_url($proxy, PHP_URL_SCHEME), ['http', 'https'], true), 503, __('TMDB proxy is not configured'));
        }

        $base = $type === 'api' ? 'https://api.themoviedb.org/3/' : 'https://image.tmdb.org/';
        $host = parse_url($base, PHP_URL_HOST);

        try {
            $address = $resolver->resolve($host);

            $upstream = Http::withMiddleware(ProxyOptions::middleware())->withOptions([
                'allow_redirects' => false,
                // CONNECT to the resolved IP while keeping the original TLS SNI and certificate checks.
                'curl' => [CURLOPT_CONNECT_TO => ["{$host}:443:{$address}:443"]],
            ])->connectTimeout(config('proxy.connect_timeout', 5))->timeout(30)->get($base.ltrim($path, '/'), $request->query());
        } catch (ConnectionException $exception) {
            $previous = $exception->getPrevious();
            $context = $previous instanceof \GuzzleHttp\Exception\TransferException && method_exists($previous, 'getHandlerContext')
                ? $previous->getHandlerContext()
                : [];

            Log::warning('TMDB proxy connection failed', [
                'type' => $type,
                'proxy_count' => count($proxies),
                ...array_intersect_key($context, array_flip([
                    'errno', 'http_code', 'http_connectcode', 'ssl_verifyresult',
                    'total_time', 'connect_time', 'pretransfer_time',
                ])),
            ]);

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
