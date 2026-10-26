<?php

namespace Tests\Feature\AqDesk;

use App\Modules\Identity\Models\Role;
use App\Modules\Identity\Support\PermissionCatalog;

/**
 * Suite R — permissions on roles (FR1 fine-grained RBAC). Cases R1–R7.
 */
class PermissionTest extends AqDeskTestCase
{
    /** Grant exactly these permissions to a role, as the admin screen would. */
    private function setPermissions(string $roleSlug, array $slugs): void
    {
        $this->actingAs($this->admin())
            ->put(route('admin.roles.update', $this->role($roleSlug)), ['permissions' => $slugs])
            ->assertSessionHasNoErrors();
        $this->signOut();
    }

    private function defaultsFor(string $roleSlug): array
    {
        return array_keys(array_filter(PermissionCatalog::all(), fn ($p) => in_array($roleSlug, $p['roles'], true)));
    }

    /** R1 — the default grants match the catalogue for every role. */
    public function test_r1_default_grants_match_the_catalogue(): void
    {
        foreach ([Role::ADMIN, Role::EXPERT, Role::MEMBER] as $slug) {
            $this->assertEqualsCanonicalizing($this->defaultsFor($slug),
                $this->role($slug)->permissions()->pluck('slug')->all(), "Defaults for $slug");
        }
    }

    /** R2 — taking "publish" from Experts blocks posting and hides the button. */
    public function test_r2_revoking_publish_blocks_experts(): void
    {
        $this->setPermissions('expert', array_diff($this->defaultsFor('expert'), ['opportunities.publish']));

        $this->actingAs($this->expert())->get(route('jobs.create'))->assertForbidden();
        $this->actingAs($this->expert())->get(route('dashboard'))->assertDontSee(route('jobs.create'));
    }

    /** R3 — granting "moderate" to Experts opens moderation without code changes. */
    public function test_r3_granting_moderation_to_experts(): void
    {
        $this->actingAs($this->expert())->get(route('admin.questions.index'))->assertForbidden();

        $this->setPermissions('expert', [...$this->defaultsFor('expert'), 'content.moderate']);

        $this->actingAs($this->expert())->get(route('admin.questions.index'))->assertOk();
        $this->actingAs($this->expert())->get(route('admin.users.index'))->assertForbidden();
    }

    /** R4 — a member without "vote" cannot vote. */
    public function test_r4_revoking_vote(): void
    {
        $question = $this->askQuestionAs($this->expert());
        $this->setPermissions('member', array_diff($this->defaultsFor('member'), ['questions.vote']));

        $this->actingAs($this->member())->post(route('votes.question', $question), ['value' => 1])->assertForbidden();
        $this->assertSame(0, $question->votes()->count());
    }

    /** R5 — administrators can never lose user management. */
    public function test_r5_admin_cannot_lock_themselves_out(): void
    {
        $this->actingAs($this->admin())
            ->put(route('admin.roles.update', $this->role('admin')), ['permissions' => ['activity.view']])
            ->assertSessionHasErrors('permissions');

        $this->assertTrue($this->admin()->fresh()->hasPermission('users.manage'));
    }

    /** R6 — only users.manage may open or change the matrix; changes are logged. */
    public function test_r6_matrix_is_admin_only_and_logged(): void
    {
        $this->actingAs($this->member())->get(route('admin.roles.index'))->assertForbidden();
        $this->actingAs($this->admin())->get(route('admin.roles.index'))->assertOk()->assertSee('Publish jobs and internships');

        $this->setPermissions('member', [...$this->defaultsFor('member'), 'activity.view']);

        $this->assertDatabaseHas('activity_logs', ['action' => 'role.permissions_changed', 'subject_type' => 'role']);
    }

    /** R7 — an unknown permission slug is rejected. */
    public function test_r7_unknown_permission_is_rejected(): void
    {
        $this->actingAs($this->admin())
            ->put(route('admin.roles.update', $this->role('member')), ['permissions' => ['everything.forever']])
            ->assertSessionHasErrors('permissions.0');
    }
}
