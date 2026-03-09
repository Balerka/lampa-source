<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TimelineChangelog extends Model
{
    use HasFactory;

    protected $table = 'timeline_changelog';

    protected $fillable = [
        'user_id',
        'profile_id',
        'version',
        'hash',
        'percent',
        'time',
        'duration',
    ];

    protected function casts(): array
    {
        return [
            'version' => 'integer',
            'percent' => 'integer',
            'time' => 'integer',
            'duration' => 'integer',
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
