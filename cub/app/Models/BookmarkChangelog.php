<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BookmarkChangelog extends Model
{
    use HasFactory;

    protected $table = 'bookmark_changelog';

    protected $fillable = [
        'user_id',
        'profile_id',
        'version',
        'action',
        'entity_id',
        'data',
    ];

    protected function casts(): array
    {
        return [
            'version' => 'integer',
            'entity_id' => 'integer',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function profile(): BelongsTo
    {
        return $this->belongsTo(Profile::class);
    }
}
