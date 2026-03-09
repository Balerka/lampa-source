<?php

namespace App\Services;

use App\Models\Profile;
use App\Models\Timeline;
use App\Models\TimelineChangelog;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class TimelineService
{
    public function dump(Profile $profile): array
    {
        return [
            'version' => (int) $profile->timelines_version,
            'timelines' => $this->normalizeTimelines(Timeline::query()
                ->where('profile_id', $profile->id)
                ->orderBy('hash')
                ->get()
                ->mapWithKeys(fn (Timeline $timeline) => [(string) $timeline->hash => $this->serializeTimeline($timeline)])
                ->all()),
        ];
    }

    public function changelog(Profile $profile, int $since): array
    {
        $timelines = [];

        TimelineChangelog::query()
            ->where('profile_id', $profile->id)
            ->where('version', '>', $since)
            ->orderBy('version')
            ->get()
            ->each(function (TimelineChangelog $entry) use (&$timelines): void {
                $timelines[(string) $entry->hash] = $this->serializeTimeline($entry);
            });

        return [
            'version' => (int) $profile->timelines_version,
            'timelines' => $this->normalizeTimelines($timelines),
        ];
    }

    public function validatePayload(array $payload): array
    {
        $payload = $this->normalizePayload($payload);

        return Validator::make($payload, [
            'hash' => ['required', 'string', 'max:191'],
            'percent' => ['required', 'integer', 'min:0', 'max:100'],
            'time' => ['required', 'integer', 'min:0'],
            'duration' => ['required', 'integer', 'min:0'],
        ])->validate();
    }

    public function update(Profile $profile, array $payload): array
    {
        $payload = $this->validatePayload($payload);

        return DB::transaction(function () use ($profile, $payload): array {
            $lockedProfile = Profile::query()->whereKey($profile->id)->lockForUpdate()->firstOrFail();
            $nextVersion = (int) $lockedProfile->timelines_version + 1;

            $timeline = Timeline::query()->updateOrCreate(
                [
                    'profile_id' => $lockedProfile->id,
                    'hash' => $payload['hash'],
                ],
                [
                    'user_id' => $lockedProfile->user_id,
                    'percent' => (int) $payload['percent'],
                    'time' => (int) $payload['time'],
                    'duration' => (int) $payload['duration'],
                    'version' => $nextVersion,
                ],
            );

            $lockedProfile->timelines_version = $nextVersion;
            $lockedProfile->save();

            TimelineChangelog::query()->create([
                'user_id' => $lockedProfile->user_id,
                'profile_id' => $lockedProfile->id,
                'version' => $nextVersion,
                'hash' => $timeline->hash,
                'percent' => $timeline->percent,
                'time' => $timeline->time,
                'duration' => $timeline->duration,
            ]);

            return [
                'version' => $nextVersion,
                'timeline' => $this->serializeTimeline($timeline),
            ];
        });
    }

    protected function serializeTimeline(Timeline|TimelineChangelog $timeline): array
    {
        return [
            'hash' => (string) $timeline->hash,
            'percent' => (int) $timeline->percent,
            'time' => (int) $timeline->time,
            'duration' => (int) $timeline->duration,
            'profile' => (int) $timeline->profile_id,
        ];
    }

    protected function normalizeTimelines(array $timelines): array|object
    {
        return $timelines === [] ? (object) [] : $timelines;
    }

    protected function normalizePayload(array $payload): array
    {
        foreach (['percent', 'time', 'duration'] as $field) {
            if (isset($payload[$field]) && is_numeric($payload[$field])) {
                $payload[$field] = (int) floor((float) $payload[$field]);
            }
        }

        return $payload;
    }
}
