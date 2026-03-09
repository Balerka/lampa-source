<?php

namespace App\Services;

use App\Models\Backup;
use App\Models\User;
use Illuminate\Http\UploadedFile;

class BackupService
{
    public function latest(User $user): array
    {
        $backup = Backup::query()
            ->where('user_id', $user->id)
            ->latest('id')
            ->first();

        return $backup?->data ?? [];
    }

    public function store(User $user, UploadedFile $file): void
    {
        $data = json_decode((string) file_get_contents($file->getRealPath()), true, 512, JSON_THROW_ON_ERROR);

        Backup::create([
            'user_id' => $user->id,
            'data' => $data,
        ]);
    }
}
