<?php

namespace App\Services;

use App\Models\Bookmark;
use App\Models\BookmarkChangelog;
use App\Models\Profile;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

class BookmarkService
{
    public function dump(Profile $profile): array
    {
        return [
            'version' => (int) $profile->bookmarks_version,
            'bookmarks' => Bookmark::query()
                ->where('profile_id', $profile->id)
                ->orderBy('id')
                ->get()
                ->map(fn (Bookmark $bookmark) => $this->serializeBookmark($bookmark))
                ->all(),
        ];
    }

    public function changelog(Profile $profile, int $since): array
    {
        return [
            'version' => (int) $profile->bookmarks_version,
            'changelog' => BookmarkChangelog::query()
                ->where('profile_id', $profile->id)
                ->where('version', '>', $since)
                ->orderBy('version')
                ->get()
                ->map(fn (BookmarkChangelog $entry) => [
                    'action' => $entry->action,
                    'entity_id' => $entry->entity_id,
                    'updated_at' => $this->formatDateTime($entry->updated_at),
                    'data' => $entry->data,
                ])
                ->all(),
        ];
    }

    public function add(Profile $profile, array $payload): void
    {
        DB::transaction(function () use ($profile, $payload): void {
            $lockedProfile = $this->lockProfile($profile);
            $existing = $this->findBookmark($lockedProfile, $payload);

            $bookmark = $existing ?? new Bookmark([
                'user_id' => $lockedProfile->user_id,
                'profile_id' => $lockedProfile->id,
            ]);

            $bookmark->fill([
                'card_id' => (int) Arr::get($payload, 'card_id', 0),
                'type' => (string) Arr::get($payload, 'type', 'book'),
                'data' => $this->normalizeJsonString(Arr::get($payload, 'data', '{}')),
                'client_id' => filled(Arr::get($payload, 'id')) ? (int) Arr::get($payload, 'id') : null,
            ]);
            $bookmark->save();

            $version = $this->incrementBookmarksVersion($lockedProfile);

            BookmarkChangelog::create([
                'user_id' => $lockedProfile->user_id,
                'profile_id' => $lockedProfile->id,
                'version' => $version,
                'action' => $existing ? 'update' : 'add',
                'entity_id' => $bookmark->id,
                'data' => $bookmark->data,
            ]);
        });
    }

    public function remove(Profile $profile, array $payload): void
    {
        DB::transaction(function () use ($profile, $payload): void {
            $lockedProfile = $this->lockProfile($profile);
            $bookmarks = $this->matchingBookmarks($lockedProfile, $payload);

            foreach ($bookmarks as $bookmark) {
                $version = $this->incrementBookmarksVersion($lockedProfile);

                BookmarkChangelog::create([
                    'user_id' => $lockedProfile->user_id,
                    'profile_id' => $lockedProfile->id,
                    'version' => $version,
                    'action' => 'remove',
                    'entity_id' => $bookmark->id,
                    'data' => $bookmark->data,
                ]);

                $bookmark->delete();
            }
        });
    }

    public function clear(Profile $profile, string $group): void
    {
        DB::transaction(function () use ($profile, $group): void {
            $lockedProfile = $this->lockProfile($profile);

            $bookmarks = Bookmark::query()
                ->where('profile_id', $lockedProfile->id)
                ->where('type', $group)
                ->orderBy('id')
                ->get();

            if ($bookmarks->isEmpty()) {
                return;
            }

            Bookmark::query()
                ->whereIn('id', $bookmarks->pluck('id'))
                ->delete();

            $version = $this->incrementBookmarksVersion($lockedProfile);

            BookmarkChangelog::create([
                'user_id' => $lockedProfile->user_id,
                'profile_id' => $lockedProfile->id,
                'version' => $version,
                'action' => 'clear',
                'entity_id' => null,
                'data' => json_encode(['type' => $group], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR),
            ]);
        });
    }

    public function sync(Profile $profile, UploadedFile $file): void
    {
        $payload = json_decode((string) file_get_contents($file->getRealPath()), true, 512, JSON_THROW_ON_ERROR);
        $bookmarks = $this->normalizeSyncedBookmarks($payload);

        DB::transaction(function () use ($profile, $bookmarks): void {
            $lockedProfile = $this->lockProfile($profile);
            $existingIds = Bookmark::query()
                ->where('profile_id', $lockedProfile->id)
                ->pluck('id');

            if ($existingIds->isNotEmpty()) {
                Bookmark::query()->whereIn('id', $existingIds)->delete();

                $version = $this->incrementBookmarksVersion($lockedProfile);

                BookmarkChangelog::create([
                    'user_id' => $lockedProfile->user_id,
                    'profile_id' => $lockedProfile->id,
                    'version' => $version,
                    'action' => 'clear',
                    'entity_id' => null,
                    'data' => null,
                ]);
            }

            foreach ($bookmarks as $item) {
                $bookmark = Bookmark::create([
                    'user_id' => $lockedProfile->user_id,
                    'profile_id' => $lockedProfile->id,
                    'card_id' => (int) Arr::get($item, 'card_id', 0),
                    'type' => (string) Arr::get($item, 'type', 'book'),
                    'data' => $this->normalizeJsonString(Arr::get($item, 'data', '{}')),
                    'client_id' => filled(Arr::get($item, 'id')) ? (int) Arr::get($item, 'id') : null,
                ]);

                $version = $this->incrementBookmarksVersion($lockedProfile);

                BookmarkChangelog::create([
                    'user_id' => $lockedProfile->user_id,
                    'profile_id' => $lockedProfile->id,
                    'version' => $version,
                    'action' => 'add',
                    'entity_id' => $bookmark->id,
                    'data' => $bookmark->data,
                ]);
            }
        });
    }

    protected function normalizeSyncedBookmarks(mixed $payload): array
    {
        if (! is_array($payload)) {
            return [];
        }

        if (isset($payload['bookmarks']) && is_array($payload['bookmarks'])) {
            return array_values(array_filter($payload['bookmarks'], fn ($item) => is_array($item)));
        }

        if (array_is_list($payload)) {
            return array_values(array_filter($payload, fn ($item) => is_array($item)));
        }

        $catalog = [];
        foreach (($payload['card'] ?? []) as $card) {
            if (is_array($card) && filled($card['id'] ?? null)) {
                $catalog[(int) $card['id']] = $card;
            }
        }

        $bookmarks = [];

        foreach ($payload as $type => $items) {
            if ($type === 'card' || ! is_array($items)) {
                continue;
            }

            foreach ($items as $item) {
                $normalized = $this->normalizeLegacyBookmarkItem((string) $type, $item, $catalog);

                if ($normalized) {
                    $bookmarks[] = $normalized;
                }
            }
        }

        return $bookmarks;
    }

    protected function normalizeLegacyBookmarkItem(string $type, mixed $item, array $catalog): ?array
    {
        if (is_array($item)) {
            $cardId = (int) ($item['id'] ?? 0);

            if ($cardId <= 0) {
                return null;
            }

            return [
                'card_id' => $cardId,
                'type' => $type,
                'data' => $item,
            ];
        }

        $cardId = (int) $item;

        if ($cardId <= 0) {
            return null;
        }

        return [
            'card_id' => $cardId,
            'type' => $type,
            'data' => $catalog[$cardId] ?? ['id' => $cardId],
        ];
    }

    protected function serializeBookmark(Bookmark $bookmark): array
    {
        return [
            'id' => $bookmark->id,
            'card_id' => (int) $bookmark->card_id,
            'type' => $bookmark->type,
            'data' => $bookmark->data,
            'profile' => (int) $bookmark->profile_id,
            'time' => $this->formatDateTime($bookmark->updated_at),
        ];
    }

    protected function formatDateTime(mixed $value): string
    {
        return $value->format('Y-m-d H:i:s');
    }

    protected function lockProfile(Profile $profile): Profile
    {
        return Profile::query()->whereKey($profile->id)->lockForUpdate()->firstOrFail();
    }

    protected function incrementBookmarksVersion(Profile $profile): int
    {
        $profile->bookmarks_version++;
        $profile->save();

        return (int) $profile->bookmarks_version;
    }

    protected function normalizeJsonString(mixed $value): string
    {
        if (is_string($value) && $value !== '') {
            json_decode($value, true);

            if (json_last_error() === JSON_ERROR_NONE) {
                return $value;
            }
        }

        return json_encode($value ?? new \stdClass(), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
    }

    protected function findBookmark(Profile $profile, array $payload): ?Bookmark
    {
        $query = Bookmark::query()->where('profile_id', $profile->id);

        if (filled(Arr::get($payload, 'id'))) {
            return $query->where('client_id', (int) Arr::get($payload, 'id'))->first();
        }

        return $query
            ->where('card_id', (int) Arr::get($payload, 'card_id', 0))
            ->where('type', (string) Arr::get($payload, 'type', 'book'))
            ->first();
    }

    protected function matchingBookmarks(Profile $profile, array $payload): Collection
    {
        $query = Bookmark::query()->where('profile_id', $profile->id);

        if (filled(Arr::get($payload, 'id'))) {
            return $query->where('client_id', (int) Arr::get($payload, 'id'))->get();
        }

        return $query
            ->when(filled(Arr::get($payload, 'card_id')), fn ($builder) => $builder->where('card_id', (int) Arr::get($payload, 'card_id')))
            ->when(filled(Arr::get($payload, 'type')), fn ($builder) => $builder->where('type', (string) Arr::get($payload, 'type')))
            ->get();
    }
}
