<?php

namespace App\Services;

use App\Models\User;
use App\Models\UserToken;
use App\Support\LampaResponse;
use Illuminate\Support\Str;

class UserService
{
    public function issueToken(User $user, string $name = 'lampa-device'): string
    {
        $plainToken = Str::random(80);

        UserToken::create([
            'user_id' => $user->id,
            'name' => $name,
            'token_hash' => hash('sha256', $plainToken),
            'last_used_at' => now(),
        ]);

        return $plainToken;
    }

    public function resolveByToken(?string $plainToken): ?User
    {
        $plainToken = is_string($plainToken) ? trim($plainToken) : null;

        if (blank($plainToken)) {
            return null;
        }

        $token = UserToken::query()
            ->where('token_hash', hash('sha256', $plainToken))
            ->when(now(), fn ($query, $now) => $query->where(fn ($inner) => $inner
                ->whereNull('expires_at')
                ->orWhere('expires_at', '>', $now)))
            ->first();

        if (! $token) {
            return null;
        }

        $token->forceFill(['last_used_at' => now()])->save();

        return User::query()
            ->with('profiles')
            ->find($token->user_id);
    }

    public function payload(User $user): array
    {
        return [
            'id' => $user->id,
            'email' => $user->email,
            'premium' => config('lampa.premium_until'),
        ];
    }

    public function findByEmail(string $email): ?User
    {
        return User::query()->where('email', $email)->first();
    }
}
