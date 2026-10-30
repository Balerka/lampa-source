<?php

namespace App\Services;

use Balerka\LaravelProxy\ProxyOptions;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

class TmdbProxyResolver
{
    public function resolve(string $host): string
    {
        $key = 'tmdb:dns:'.hash('sha256', $host.serialize(config('proxy.list', [])));

        if ($address = Cache::get($key)) {
            return $address;
        }

        // Resolve through the same proxies, bypassing their DNS for TMDB.
        $response = Http::withMiddleware(ProxyOptions::middleware())
            ->accept('application/dns-json')
            ->withHeaders(['Cache-Control' => 'no-cache'])
            ->withOptions(['allow_redirects' => false])
            ->connectTimeout(config('proxy.connect_timeout', 5))
            ->timeout(15)
            ->get('https://cloudflare-dns.com/dns-query', ['name' => $host, 'type' => 'A']);

        if ($response->successful() && $response->json('Status') === 0) {
            $answers = $response->json('Answer') ?? [];

            foreach ($answers as $answer) {
                $address = $answer['data'] ?? '';

                if (($answer['type'] ?? null) === 1 && filter_var($address, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4 | FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                    $ttl = max(1, min(300, ...array_column($answers, 'TTL')));
                    Cache::put($key, $address, $ttl);

                    return $address;
                }
            }
        }

        throw new ConnectionException(__('TMDB DNS resolution failed'));
    }
}
