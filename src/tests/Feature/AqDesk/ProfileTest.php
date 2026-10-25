<?php

namespace Tests\Feature\AqDesk;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;

/**
 * Suite D — User profile (Module 4). Cases D1–D6.
 */
class ProfileTest extends AqDeskTestCase
{
    /** D1 — headline, bio and skills save and appear publicly. */
    public function test_d1_profile_details_save_and_display(): void
    {
        $member = $this->member();

        $this->actingAs($member)->put(route('profile.update'), [
            'name'     => $member->name,
            'email'    => $member->email,
            'headline' => 'Final year Computer Science student',
            'bio'      => 'Working on an accessible community platform.',
            'skills'   => 'PHP, Laravel, Accessibility',
        ])->assertSessionHasNoErrors();

        $this->get(route('profile.show', $member))
            ->assertOk()
            ->assertSee('Final year Computer Science student')
            ->assertSee('Accessibility');
    }

    /** D2 — an uploaded avatar replaces the initials placeholder. */
    public function test_d2_avatar_upload_is_stored(): void
    {
        Storage::fake('public');

        $member = $this->member();

        $this->actingAs($member)->put(route('profile.update'), [
            'name'   => $member->name,
            'email'  => $member->email,
            'avatar' => UploadedFile::fake()->image('avatar.jpg', 300, 300),
        ])->assertSessionHasNoErrors();

        $this->assertNotNull($member->fresh()->avatarUrl(), 'No avatar URL after upload.');
    }

    /** D3 — a non-image upload is rejected. */
    public function test_d3_non_image_avatar_is_rejected(): void
    {
        Storage::fake('public');

        $member = $this->member();

        $this->actingAs($member)->put(route('profile.update'), [
            'name'   => $member->name,
            'email'  => $member->email,
            'avatar' => UploadedFile::fake()->create('notes.txt', 20, 'text/plain'),
        ])->assertSessionHasErrors('avatar');
    }

    /** D4 — the wrong current password is refused. */
    public function test_d4_wrong_current_password_is_refused(): void
    {
        $member = $this->member();

        $this->actingAs($member)->put(route('profile.password'), [
            'current_password'      => 'definitely-not-it',
            'password'              => 'brandnewpass',
            'password_confirmation' => 'brandnewpass',
        ])->assertSessionHasErrors('current_password');

        $this->assertTrue(Hash::check('password', $member->fresh()->password));
    }

    /** D5 — a correct change works and the old password stops working. */
    public function test_d5_password_change_takes_effect(): void
    {
        $member = $this->member();

        $this->actingAs($member)->put(route('profile.password'), [
            'current_password'      => 'password',
            'password'              => 'brandnewpass',
            'password_confirmation' => 'brandnewpass',
        ])->assertSessionHasNoErrors();

        $this->post(route('logout'));

        $this->post(route('login.store'), [
            'email'    => $member->email,
            'password' => 'password',
        ])->assertSessionHasErrors();

        $this->post(route('login.store'), [
            'email'    => $member->email,
            'password' => 'brandnewpass',
        ])->assertSessionHasNoErrors();

        $this->assertAuthenticated();
    }

    /** D6 — a public profile is readable without an edit affordance. */
    public function test_d6_public_profile_has_no_edit_button_for_visitors(): void
    {
        $this->get(route('profile.show', $this->member()))
            ->assertOk()
            ->assertDontSee(route('profile.edit'));
    }
}
