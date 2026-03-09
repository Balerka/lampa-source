<?php

namespace App\Services\Auth;

use App\Models\DeviceCode;
use App\Models\Profile;
use App\Models\User;
use App\Services\ProfileService;
use App\Services\UserService;
use Illuminate\Support\Facades\DB;
use NjoguAmos\Otp\Otp;

class DeviceCodeService
{
    public function __construct(
        protected UserService $users,
        protected ProfileService $profiles,
    ) {
    }

    public function createForUser(User $user): array
    {
        $this->pruneExpiredCodes();
        $this->invalidateActiveCodesForUser($user);

        $identifier = $this->identifierForUser($user);
        $otp = Otp::generate($identifier);

        DeviceCode::create([
            'user_id' => $user->id,
            'otp_id' => $otp->id,
            'identifier' => $identifier,
            'expires_at' => $otp->expires_at,
        ]);

        return [
            'code' => $otp->token,
            'expires' => max(0, now()->diffInSeconds($otp->expires_at, false)),
        ];
    }

    public function redeem(string $code): ?array
    {
        $this->pruneExpiredCodes();

        return DB::transaction(function () use ($code): ?array {
            $deviceCodes = DeviceCode::query()
                ->with('user.profiles')
                ->whereNull('consumed_at')
                ->where('expires_at', '>', now())
                ->orderByDesc('id')
                ->lockForUpdate()
                ->get();

            foreach ($deviceCodes as $deviceCode) {
                if (! Otp::validate($deviceCode->identifier, $code)) {
                    continue;
                }

                $deviceCode->forceFill([
                    'otp_id' => null,
                    'consumed_at' => now(),
                ])->save();

                /** @var User $user */
                $user = $deviceCode->user;
                /** @var Profile $profile */
                $profile = $this->profiles->ensureMainProfile($user);

                return [
                    'token' => $this->users->issueToken($user),
                    'user' => $user,
                    'profile' => $profile,
                ];
            }

            return null;
        });
    }

    protected function invalidateActiveCodesForUser(User $user): void
    {
        DeviceCode::query()
            ->where('user_id', $user->id)
            ->whereNull('consumed_at')
            ->where('expires_at', '>', now())
            ->get()
            ->each(function (DeviceCode $deviceCode): void {
                if (filled($deviceCode->identifier) && $deviceCode->otp_id) {
                    $deviceCode->otp?->invalidate();
                }

                $deviceCode->delete();
            });
    }

    protected function pruneExpiredCodes(): void
    {
        DeviceCode::query()
            ->where('expires_at', '<=', now())
            ->orWhereNotNull('consumed_at')
            ->get()
            ->each(function (DeviceCode $deviceCode): void {
                if ($deviceCode->otp_id) {
                    $deviceCode->otp?->invalidate();
                }

                $deviceCode->delete();
            });
    }

    protected function identifierForUser(User $user): string
    {
        return 'lampa-device:'.$user->id;
    }
}
