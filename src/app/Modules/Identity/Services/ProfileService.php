<?php

namespace App\Modules\Identity\Services;

use App\Modules\Identity\Models\Profile;
use App\Modules\Identity\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class ProfileService
{
    private const AVATAR_DISK = 'public';

    /** Profile fields a user may edit, besides name, email and avatar. */
    private const PROFILE_FIELDS = [
        'headline', 'bio', 'skills', 'organization', 'location', 'website', 'linkedin', 'github',
    ];

    /** @param  array<string, mixed>  $data  validated UpdateProfileRequest data */
    public function update(User $user, array $data, ?UploadedFile $avatar): void
    {
        DB::transaction(function () use ($user, $data, $avatar) {
            $user->fill(Arr::only($data, ['name', 'email']));

            // A new address has to be confirmed again.
            if ($user->isDirty('email')) {
                $user->email_verified_at = null;
            }

            $emailChanged = $user->isDirty('email');
            $user->save();

            if ($emailChanged) {
                $user->sendEmailVerificationNotification();
            }

            $profile = $user->profile ?: new Profile(['user_id' => $user->id]);

            $profile->fill(array_merge(
                array_fill_keys(self::PROFILE_FIELDS, null),
                Arr::only($data, self::PROFILE_FIELDS),
            ));

            if ($avatar) {
                // Remove the previous file so uploads do not pile up.
                if ($profile->avatar_path) {
                    Storage::disk(self::AVATAR_DISK)->delete($profile->avatar_path);
                }

                $profile->avatar_path = $avatar->store('avatars', self::AVATAR_DISK);
            }

            $profile->user_id = $user->id;
            $profile->save();
        });
    }

    public function changePassword(User $user, string $newPassword): void
    {
        $user->update(['password' => $newPassword]);   // hashed by the model cast
    }
}
