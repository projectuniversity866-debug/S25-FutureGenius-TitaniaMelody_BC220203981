<?php

namespace App\Modules\Governance\Services;

use App\Modules\Governance\Models\Consent;
use App\Modules\Governance\Support\LegalDocuments;
use App\Modules\Identity\Models\Role;
use App\Modules\Identity\Models\User;
use App\Modules\Platform\Services\ActivityLogger;
use App\Modules\Platform\Support\PersonalData;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Data rights (roadmap 2.6): consent to the current terms, "Download my
 * data", and "Delete my account". What each module holds is declared by the
 * module itself (ModuleServiceProvider::personalData / personalDataErasers).
 */
class PrivacyService
{
    public function __construct(private PersonalData $registry, private ActivityLogger $activity) {}

    public function hasAccepted(User $user): bool
    {
        return Consent::where('user_id', $user->id)->where('version', LegalDocuments::VERSION)->exists();
    }

    public function accept(User $user): void
    {
        Consent::firstOrCreate(['user_id' => $user->id, 'version' => LegalDocuments::VERSION], ['accepted_at' => now()]);
    }

    /** Everything AQDesk holds about the member, grouped by area. */
    public function export(User $user): array
    {
        return [
            'exported_at' => now()->toIso8601String(),
            'about'       => 'Personal data AQDesk holds about you. Content other people wrote is not included.',
            'data'        => $this->registry->collect($user),
        ];
    }

    /**
     * Erase the member's personal data and close the account. Public
     * contributions stay, credited to "Deleted user", so discussions others
     * took part in keep making sense.
     *
     * @throws ValidationException for the last administrator
     */
    public function deleteAccount(User $user): void
    {
        $admins = User::where('is_active', true)->whereHas('role', fn ($q) => $q->where('slug', Role::ADMIN))->count();
        if ($user->isAdmin() && $admins <= 1) {
            throw ValidationException::withMessages(['confirm' => 'You are the only administrator. Make someone else an administrator first.']);
        }

        DB::transaction(fn () => $this->registry->eraseAll($user));

        $this->activity->record('account.deleted', null, 'A member deleted their account (#'.$user->id.')');
    }
}
