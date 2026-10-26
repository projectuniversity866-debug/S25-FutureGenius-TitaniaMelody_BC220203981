<?php

namespace App\Modules\Identity\Services;

use App\Modules\Identity\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/**
 * "Where you're signed in" — reads Laravel's database session store.
 * With any other session driver the list is simply empty.
 */
class SessionService
{
    public function available(): bool
    {
        return config('session.driver') === 'database';
    }

    /** @return Collection<int, object{device: string, ip: ?string, last_active: Carbon, current: bool}> */
    public function forUser(User $user, string $currentId): Collection
    {
        if (! $this->available()) {
            return collect();
        }

        return DB::table(config('session.table', 'sessions'))
            ->where('user_id', $user->id)
            ->orderByDesc('last_activity')
            ->get()
            ->map(fn ($s) => (object) [
                'device'      => $this->describe($s->user_agent),
                'ip'          => $s->ip_address,
                'last_active' => Carbon::createFromTimestamp($s->last_activity),
                'current'     => $s->id === $currentId,
            ]);
    }

    /**
     * Sign out every other browser. Re-hashing the password also invalidates
     * "remember me" cookies on those devices.
     */
    public function signOutOthers(User $user, string $password, string $currentId): int
    {
        Auth::logoutOtherDevices($password);

        if (! $this->available()) {
            return 0;
        }

        return DB::table(config('session.table', 'sessions'))
            ->where('user_id', $user->id)
            ->where('id', '!=', $currentId)
            ->delete();
    }

    /** "Edge on Windows" from a user-agent string — enough to recognise a device. */
    private function describe(?string $agent): string
    {
        $agent ??= '';

        $browser = match (true) {
            str_contains($agent, 'Edg/')    => 'Edge',
            str_contains($agent, 'OPR/')    => 'Opera',
            str_contains($agent, 'Chrome/') => 'Chrome',
            str_contains($agent, 'Firefox/') => 'Firefox',
            str_contains($agent, 'Safari/') => 'Safari',
            default                         => 'Unknown browser',
        };

        $os = match (true) {
            str_contains($agent, 'Windows')                               => 'Windows',
            str_contains($agent, 'Android')                               => 'Android',
            str_contains($agent, 'iPhone') || str_contains($agent, 'iPad') => 'iOS',
            str_contains($agent, 'Mac OS')                                => 'macOS',
            str_contains($agent, 'Linux')                                 => 'Linux',
            default                                                       => 'unknown system',
        };

        return $browser.' on '.$os;
    }
}
