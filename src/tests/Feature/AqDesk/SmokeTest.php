<?php

namespace Tests\Feature\AqDesk;

use App\Models\Category;
use App\Models\Role;
use App\Models\User;

/**
 * Environment smoke test — run this FIRST. If it fails, the install is
 * wrong and every other failure downstream is noise.
 */
class SmokeTest extends AqDeskTestCase
{
    /** The three roles exist with the slugs the middleware expects. */
    public function test_the_three_roles_are_seeded(): void
    {
        foreach (['admin', 'expert', 'member'] as $slug) {
            $this->assertNotNull(
                Role::where('slug', $slug)->first(),
                "Role '{$slug}' missing — run php artisan migrate:fresh --seed"
            );
        }
    }

    /** The four topic tracks from the requirements exist. */
    public function test_categories_are_seeded(): void
    {
        $this->assertGreaterThanOrEqual(4, Category::count(), 'Expected at least four categories.');
    }

    /** The demo accounts exist and all use the documented password. */
    public function test_demo_accounts_can_sign_in(): void
    {
        foreach (['admin@aqdesk.test', 'expert@aqdesk.test', 'member@aqdesk.test'] as $email) {
            $user = User::where('email', $email)->first();

            $this->assertNotNull($user, "Demo account {$email} is missing.");

            $this->post(route('login.store'), ['email' => $email, 'password' => 'password'])
                ->assertSessionHasNoErrors();

            $this->assertAuthenticated();
            $this->post(route('logout'));
        }
    }

    /** The role helpers return the capability set the matrix documents. */
    public function test_role_helpers_match_the_permission_matrix(): void
    {
        $this->assertTrue($this->admin()->isAdmin());
        $this->assertTrue($this->admin()->canModerate());
        $this->assertTrue($this->admin()->canPostJobs());

        $this->assertFalse($this->expert()->isAdmin());
        $this->assertFalse($this->expert()->canModerate());
        $this->assertTrue($this->expert()->canPostJobs());

        $this->assertFalse($this->member()->isAdmin());
        $this->assertFalse($this->member()->canModerate());
        $this->assertFalse($this->member()->canPostJobs());
    }

    /** Every public page renders for a guest. */
    public function test_public_pages_render_for_a_guest(): void
    {
        foreach ([route('home'), route('questions.index'), route('jobs.index'), route('login'), route('register')] as $url) {
            $this->get($url)->assertOk();
        }
    }
}
