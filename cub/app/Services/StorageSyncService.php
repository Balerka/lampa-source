<?php

namespace App\Services;

use App\Models\Profile;
use App\Models\ProfileStorage;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class StorageSyncService
{
    /**
     * @var array<string, string>
     */
    protected array $fieldTypes = [
        'online_view' => 'array_string',
        'torrents_view' => 'array_string',
        'search_history' => 'array_string',
        'online_last_balanser' => 'object_string',
        'user_clarifys' => 'object_object',
        'torrents_filter_data' => 'object_object',
    ];

    public function dump(Profile $profile, string $name, string $classType): array
    {
        $expectedType = $this->resolveClassType($name);

        if ($expectedType !== $classType) {
            throw ValidationException::withMessages([
                'class_type' => ['Unsupported storage class type.'],
            ]);
        }

        $storage = ProfileStorage::query()
            ->where('profile_id', $profile->id)
            ->where('name', $name)
            ->first();

        return [
            'secuses' => true,
            'data' => $this->prepareForResponse(
                $storage ? $this->decodePayload($storage->payload, $classType) : $this->emptyValue($classType),
                $classType,
            ),
        ];
    }

    public function applySocketMutation(Profile $profile, array $payload): array
    {
        $name = (string) ($payload['name'] ?? '');
        $classType = $this->resolveClassType($name);

        if ($classType === null) {
            throw ValidationException::withMessages([
                'name' => ['Unsupported storage field.'],
            ]);
        }

        $id = array_key_exists('id', $payload) && $payload['id'] !== null ? (string) $payload['id'] : null;
        $remove = ! empty($payload['remove']);
        $clean = ! empty($payload['clean']);
        $value = $payload['value'] ?? null;

        $this->validateMutation($classType, $id, $remove, $clean, $value);

        DB::transaction(function () use ($profile, $name, $classType, $id, $remove, $clean, $value): void {
            $storage = ProfileStorage::query()->firstOrNew([
                'profile_id' => $profile->id,
                'name' => $name,
            ]);

            $storage->user_id = $profile->user_id;
            $storage->class_type = $classType;

            $data = $storage->exists ? $this->decodePayload($storage->payload, $classType) : $this->emptyValue($classType);
            $data = $this->mutate($data, $classType, $id, $value, $remove, $clean);

            $storage->payload = $this->encodePayload($data);
            $storage->save();
        });

        return [
            'id' => $id,
            'name' => $name,
            'value' => $value,
            'remove' => $remove,
            'clean' => $clean,
        ];
    }

    public function resolveClassType(string $name): ?string
    {
        return $this->fieldTypes[$name] ?? null;
    }

    protected function validateMutation(string $classType, ?string $id, bool $remove, bool $clean, mixed $value): void
    {
        if ($clean) {
            return;
        }

        if (str_starts_with($classType, 'object_') && ($id === null || $id === '')) {
            throw ValidationException::withMessages([
                'id' => ['The id field is required for object storage updates.'],
            ]);
        }

        if (! $remove && str_starts_with($classType, 'array_') && ! is_scalar($value)) {
            throw ValidationException::withMessages([
                'value' => ['Array string storage expects a scalar value.'],
            ]);
        }
    }

    protected function mutate(mixed $data, string $classType, ?string $id, mixed $value, bool $remove, bool $clean): array
    {
        if ($clean) {
            return $this->emptyValue($classType);
        }

        if ($classType === 'array_string') {
            $data = is_array($data) ? array_values($data) : [];
            $stringValue = (string) $value;

            if ($remove) {
                return array_values(array_filter($data, fn ($item) => (string) $item !== $stringValue));
            }

            if (! in_array($stringValue, array_map('strval', $data), true)) {
                $data[] = $stringValue;
            }

            return array_values($data);
        }

        $data = is_array($data) ? $data : [];

        if ($remove) {
            unset($data[$id]);

            return $data;
        }

        $data[$id] = $value;

        return $data;
    }

    protected function emptyValue(string $classType): array
    {
        return str_starts_with($classType, 'array_') ? [] : [];
    }

    protected function prepareForResponse(mixed $data, string $classType): mixed
    {
        if (str_starts_with($classType, 'object_') && $data === []) {
            return (object) [];
        }

        return $data;
    }

    protected function decodePayload(?string $payload, string $classType): array
    {
        if (! is_string($payload) || $payload === '') {
            return $this->emptyValue($classType);
        }

        $decoded = json_decode($payload, true);

        return is_array($decoded) ? $decoded : $this->emptyValue($classType);
    }

    protected function encodePayload(array $data): string
    {
        return json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
    }
}
