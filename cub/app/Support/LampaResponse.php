<?php

namespace App\Support;

use Carbon\CarbonInterface;
use Illuminate\Http\Response;

class LampaResponse
{
    public static function success(array $payload = []): array
    {
        return array_merge(['secuses' => true], $payload);
    }

    public static function error(string $code, ?string $message = null): array
    {
        return [
            'secuses' => false,
            'code' => $code,
            'error' => $message ?? $code,
        ];
    }

    public static function iso(?CarbonInterface $value): ?string
    {
        return $value?->utc()->format('Y-m-d\TH:i:s.v\Z');
    }

    public static function plainJson(array $payload): Response
    {
        return response(
            json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR),
            200,
            ['Content-Type' => 'text/plain; charset=UTF-8'],
        );
    }
}
