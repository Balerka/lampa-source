<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Profile extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'name',
        'icon',
        'main',
        'child',
        'age',
        'bookmarks_version',
        'timelines_version',
    ];

    protected function casts(): array
    {
        return [
            'main' => 'boolean',
            'child' => 'boolean',
            'age' => 'integer',
            'bookmarks_version' => 'integer',
            'timelines_version' => 'integer',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function bookmarks(): HasMany
    {
        return $this->hasMany(Bookmark::class);
    }

    public function timelines(): HasMany
    {
        return $this->hasMany(Timeline::class);
    }

    public function storages(): HasMany
    {
        return $this->hasMany(ProfileStorage::class);
    }
}
