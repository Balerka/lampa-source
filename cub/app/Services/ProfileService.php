<?php

namespace App\Services;

use App\Models\Profile;
use App\Models\User;

class ProfileService
{
    public function ensureMainProfile(User $user): Profile
    {
        $profile = $user->profiles()->where('main', true)->first();

        if ($profile) {
            return $profile;
        }

        return $user->profiles()->create([
            'name' => trim((string) $user->name) !== '' ? trim((string) $user->name) : 'Profile',
            'icon' => 'l_1',
            'main' => true,
            'child' => false,
            'age' => 18,
            'bookmarks_version' => 0,
            'timelines_version' => 0,
        ]);
    }

    public function resolveActiveProfile(User $user, null|int|string $profileId): Profile
    {
        if (filled($profileId)) {
            $profile = $user->profiles()->whereKey((int) $profileId)->first();

            if ($profile) {
                return $profile;
            }
        }

        return $this->ensureMainProfile($user);
    }

    public function all(User $user): array
    {
        return $user->profiles()
            ->orderByDesc('main')
            ->orderBy('id')
            ->get()
            ->map(fn (Profile $profile) => $this->serialize($profile))
            ->all();
    }

    public function create(User $user, string $name): Profile
    {
        $count = (int) $user->profiles()->count();
        $iconIndex = max(1, min($count + 1, 9));

        return $user->profiles()->create([
            'name' => trim($name),
            'icon' => 'l_'.$iconIndex,
            'main' => $count === 0,
            'child' => false,
            'age' => 18,
            'bookmarks_version' => 0,
            'timelines_version' => 0,
        ]);
    }

    public function serialize(Profile $profile): array
    {
        return [
            'id' => $profile->id,
            'name' => $profile->name,
            'icon' => $profile->icon,
            'main' => (bool) $profile->main,
            'child' => (bool) $profile->child,
            'age' => (int) $profile->age,
        ];
    }
}
