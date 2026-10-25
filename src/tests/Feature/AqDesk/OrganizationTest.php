<?php

namespace Tests\Feature\AqDesk;

use App\Modules\Careers\Models\JobPost;
use App\Modules\Careers\Models\Organization;

/**
 * Suite O — organization profiles (1.12) and professional opportunity types
 * (1.13). Cases O1–O9.
 */
class OrganizationTest extends AqDeskTestCase
{
    private function organizationPayload(array $overrides = []): array
    {
        return array_merge([
            'name'     => 'Lahore Learning Labs',
            'tagline'  => 'EdTech products for schools',
            'about'    => 'We build tools that help teachers give feedback faster.',
            'website'  => 'https://lahore-learning.example.org',
            'location' => 'Lahore, Pakistan',
            'industry' => 'Education technology',
            'size'     => '11-50',
        ], $overrides);
    }

    private function createOrganizationAs($user, array $overrides = []): Organization
    {
        $this->actingAs($user)->post(route('organizations.store'), $this->organizationPayload($overrides))
            ->assertSessionHasNoErrors();

        return Organization::where('user_id', $user->id)->latest('id')->firstOrFail();
    }

    /** O1 — an expert creates a profile; it is public and lists only open opportunities. */
    public function test_o1_profile_lists_open_opportunities(): void
    {
        $expert = $this->expert();
        $org = $this->createOrganizationAs($expert);
        $this->postJobAs($expert, ['title' => 'Open role at the labs', 'organization_id' => $org->id, 'company' => '']);
        $this->postJobAs($expert, ['title' => 'Hidden role at the labs', 'organization_id' => $org->id, 'is_active' => 0]);
        JobPost::where('title', 'Hidden role at the labs')->update(['is_active' => false]);
        $this->signOut();

        $this->get(route('organizations.show', $org))->assertOk()
            ->assertSee('Lahore Learning Labs')->assertSee('EdTech products for schools')
            ->assertSee('lahore-learning.example.org')
            ->assertSee('Open role at the labs')->assertDontSee('Hidden role at the labs')
            ->assertSee('Open opportunities (1)');

        $this->get(route('organizations.index', ['q' => 'learning']))->assertOk()
            ->assertSee('Lahore Learning Labs')->assertSee('1 open opportunity');
    }

    /** O2 — members cannot create profiles; only the owner or a moderator can edit or delete. */
    public function test_o2_who_may_manage_a_profile(): void
    {
        $org = $this->createOrganizationAs($this->expert());

        $this->actingAs($this->member())->get(route('organizations.create'))->assertForbidden();
        $this->actingAs($this->member())->post(route('organizations.store'), $this->organizationPayload())->assertForbidden();
        $this->actingAs($this->otherExpert())->put(route('organizations.update', $org), $this->organizationPayload(['name' => 'Taken over']))
            ->assertForbidden();
        $this->actingAs($this->otherExpert())->delete(route('organizations.destroy', $org))->assertForbidden();

        $this->actingAs($this->admin())->put(route('organizations.update', $org), $this->organizationPayload(['name' => 'Renamed by a moderator']))
            ->assertSessionHasNoErrors();
        $this->assertSame('Renamed by a moderator', $org->fresh()->name);
    }

    /** O3 — publishing under a profile sets the name; renaming the profile renames its posts. */
    public function test_o3_posts_carry_the_organization_name(): void
    {
        $expert = $this->expert();
        $org = $this->createOrganizationAs($expert);
        $job = $this->postJobAs($expert, ['organization_id' => $org->id, 'company' => 'Typed but ignored']);

        $this->assertSame('Lahore Learning Labs', $job->company);
        $this->assertSame($org->id, $job->organization_id);
        $this->get(route('jobs.show', $job))->assertSee(route('organizations.show', $org), false);

        $this->actingAs($expert)->put(route('organizations.update', $org), $this->organizationPayload(['name' => 'Punjab Learning Labs']));
        $this->assertSame('Punjab Learning Labs', $job->fresh()->company);
    }

    /** O4 — you cannot publish under someone else's organization; a name is needed without one. */
    public function test_o4_organization_must_be_your_own(): void
    {
        $org = $this->createOrganizationAs($this->otherExpert());

        $this->actingAs($this->expert())->post(route('jobs.store'), $this->jobPayload(['organization_id' => $org->id]))
            ->assertSessionHasErrors(['organization_id' => 'Choose one of your own organizations.']);
        $this->actingAs($this->expert())->post(route('jobs.store'), $this->jobPayload(['company' => '']))
            ->assertSessionHasErrors('company');
    }

    /** O5 — deleting a profile keeps its opportunities on the board and is logged. */
    public function test_o5_deleting_a_profile_keeps_the_posts(): void
    {
        $expert = $this->expert();
        $org = $this->createOrganizationAs($expert);
        $job = $this->postJobAs($expert, ['organization_id' => $org->id]);

        $this->actingAs($expert)->delete(route('organizations.destroy', $org))->assertRedirect(route('jobs.mine'));

        $this->assertNull($job->fresh()->organization_id);
        $this->assertSame('Lahore Learning Labs', $job->fresh()->company);
        $this->assertDatabaseHas('activity_logs', ['action' => 'organization.deleted', 'subject_type' => 'organization']);
    }

    /** O6 — every seeded opportunity belongs to a profile, and the board offers every type. */
    public function test_o6_seeded_profiles_and_type_tabs(): void
    {
        $this->assertSame(0, JobPost::whereNull('organization_id')->count());

        $page = $this->get(route('jobs.index'))->assertOk();
        foreach (JobPost::TYPES as $type) {
            $page->assertSee($type[1]); // tab label, e.g. "Calls for papers"
        }

        $this->get(route('jobs.index', ['type' => 'hackathon']))->assertOk()
            ->assertSee('Hack Weekend: AI for Public Services')->assertDontSee('Junior Laravel Developer');
        // An unknown type shows everything (newest first: the seeded scholarship and hackathon are on page 1).
        $this->get(route('jobs.index', ['type' => 'nonsense']))->assertOk()
            ->assertSee('Hack Weekend: AI for Public Services')->assertSee('Women in Computing Scholarship 2027');
    }

    /** O7 — a scholarship shows its award, eligibility and dates in the right words. */
    public function test_o7_professional_opportunity_details(): void
    {
        $job = $this->postJobAs($this->expert(), [
            'title'        => 'Data Science Scholarship 2027',
            'type'         => 'scholarship',
            'salary_min'   => 120000,
            'salary_max'   => null,
            'eligibility'  => 'Second-year students with a CGPA above 3.2.',
            'starts_on'    => now()->addMonths(3)->format('Y-m-d'),
            'ends_on'      => now()->addMonths(15)->format('Y-m-d'),
            'external_url' => 'https://scholarships.example.org/data-science',
        ]);

        $this->get(route('jobs.show', $job))->assertOk()
            ->assertSee('Scholarship')
            ->assertSee('<dt>Award</dt>', false)->assertDontSee('<dt>Salary</dt>', false)
            ->assertSee('Who can apply')->assertSee('Second-year students with a CGPA above 3.2.')
            ->assertSee($job->dateRange())
            ->assertSee('rel="noopener noreferrer nofollow"', false);

        $cfp = $this->postJobAs($this->expert(), ['title' => 'Call for papers on learning analytics', 'type' => 'call_for_papers']);
        $this->get(route('jobs.show', $cfp))->assertDontSee('<dt>Salary</dt>', false);
    }

    /** O8 — unknown types, reversed dates and non-web links are rejected. */
    public function test_o8_validation(): void
    {
        $this->actingAs($this->expert())->post(route('jobs.store'), $this->jobPayload([
            'type'         => 'party',
            'starts_on'    => now()->addDays(10)->format('Y-m-d'),
            'ends_on'      => now()->addDays(5)->format('Y-m-d'),
            'external_url' => 'javascript:alert(1)',
        ]))->assertSessionHasErrors(['type', 'ends_on', 'external_url']);

        $this->actingAs($this->expert())->post(route('organizations.store'), $this->organizationPayload([
            'website' => 'ftp://files.example.org', 'size' => 'huge',
        ]))->assertSessionHasErrors(['website', 'size']);
    }

    /** O9 — the post form offers the poster's own organizations; My postings lists them. */
    public function test_o9_forms_offer_your_organizations(): void
    {
        $expert = $this->expert();
        $mine = $this->createOrganizationAs($expert);
        $this->createOrganizationAs($this->otherExpert(), ['name' => 'Someone Else Ltd']);

        $this->actingAs($expert)->get(route('jobs.create'))->assertOk()
            ->assertSee('<option value="'.$mine->id.'"', false)->assertDontSee('Someone Else Ltd')
            ->assertSee('<optgroup label="Funding &amp; programmes">', false);

        $this->actingAs($expert)->get(route('jobs.mine'))->assertOk()
            ->assertSee('My organizations')->assertSee('Lahore Learning Labs');
    }
}
