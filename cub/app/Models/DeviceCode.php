<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use NjoguAmos\Otp\Models\Otp;

class DeviceCode extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'otp_id',
        'identifier',
        'expires_at',
        'consumed_at',
    ];

    protected function casts(): array
    {
        return [
            'otp_id' => 'integer',
            'expires_at' => 'datetime',
            'consumed_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function otp(): BelongsTo
    {
        return $this->belongsTo(Otp::class);
    }
}
