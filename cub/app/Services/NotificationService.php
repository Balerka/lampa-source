<?php

namespace App\Services;

use App\Models\Notification;
use App\Models\Profile;
use App\Models\User;
use App\Support\LampaResponse;
use Illuminate\Support\Arr;

class NotificationService
{
    public function all(User $user, ?Profile $profile = null): array
    {
        return Notification::query()
            ->where('user_id', $user->id)
            ->when($profile, fn ($query) => $query->where('profile_id', $profile->id))
            ->orderByDesc('id')
            ->get()
            ->map(fn (Notification $notification) => [
                'id' => $notification->id,
                'voice' => $notification->voice,
                'data' => $notification->data,
                'episode' => $notification->episode,
                'season' => $notification->season,
                'profile' => $notification->profile_id,
                'time' => LampaResponse::iso($notification->created_at),
            ])
            ->all();
    }

    public function add(User $user, ?Profile $profile, array $payload): void
    {
        Notification::create([
            'user_id' => $user->id,
            'profile_id' => $profile?->id,
            'voice' => Arr::get($payload, 'voice'),
            'data' => $this->normalizeJsonString(Arr::get($payload, 'data', '{}')),
            'episode' => Arr::has($payload, 'episode') ? (int) Arr::get($payload, 'episode') : null,
            'season' => Arr::has($payload, 'season') ? (int) Arr::get($payload, 'season') : null,
        ]);
    }

    protected function normalizeJsonString(mixed $value): string
    {
        if (is_string($value)) {
            return $value;
        }

        return json_encode($value ?? new \stdClass(), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
    }
}
