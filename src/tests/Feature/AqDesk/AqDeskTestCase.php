<?php

namespace Tests\Feature\AqDesk;

use App\Models\Category;
use App\Models\JobPost;
use App\Models\Question;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Shared base for the AQDesk feature suite.
 *
 * Design note: records are created by POSTing to the application's own
 * routes wherever possible, not by writing models directly. That keeps the
 * suite independent of column names and makes each helper an implicit test
 * of the create path it uses.
 */
abstract class AqDeskTestCase extends TestCase
{
    use RefreshDatabase;

    /** Run DatabaseSeeder before every test, so roles and categories exist. */
    protected $seed = true;

    /**
     * actingAs() keeps the user signed in for every later request in the
     * test. Call this before acting as a guest or signing in as someone else.
     */
    protected function signOut(): void
    {
        $this->app['auth']->forgetGuards();
    }

    // ---------------------------------------------------------------- roles

    protected function role(string $slug): Role
    {
        $role = Role::where('slug', $slug)->first();

        $this->assertNotNull($role, "Role '{$slug}' is missing — check RoleSeeder.");

        return $role;
    }

    /**
     * Fetch a seeded demo account, or create one with that role if the
     * seeder does not provide it.
     */
    protected function userWithRole(string $roleSlug, ?string $email = null): User
    {
        if ($email) {
            $existing = User::where('email', $email)->first();

            if ($existing) {
                return $existing;
            }
        }

        $user = User::create([
            'name'      => ucfirst($roleSlug).' Tester',
            'email'     => $email ?: $roleSlug.'.'.uniqid().'@aqdesk.test',
            'password'  => 'password',   // hashed by the model cast
            'role_id'   => $this->role($roleSlug)->id,
            'is_active' => true,
        ]);

        // Every real user gets a profile row at registration, so mirror that.
        \App\Models\Profile::firstOrCreate(['user_id' => $user->id]);

        return $user;
    }

    protected function admin(): User
    {
        return $this->userWithRole('admin', 'admin@aqdesk.test');
    }

    protected function expert(): User
    {
        return $this->userWithRole('expert', 'expert@aqdesk.test');
    }

    /** A second expert — used to prove ownership boundaries. */
    protected function otherExpert(): User
    {
        return $this->userWithRole('expert', 'hr@aqdesk.test');
    }

    protected function member(): User
    {
        return $this->userWithRole('member', 'member@aqdesk.test');
    }

    protected function otherMember(): User
    {
        return $this->userWithRole('member', 'sara@aqdesk.test');
    }

    /** A member who is safe to deactivate. */
    protected function disposableMember(): User
    {
        return $this->userWithRole('member', 'faisal@aqdesk.test');
    }

    // ------------------------------------------------------------ categories

    protected function category(): Category
    {
        $category = Category::query()->first();

        $this->assertNotNull($category, 'No categories seeded — check CategorySeeder.');

        return $category;
    }

    // ------------------------------------------------------------- questions

    protected function questionPayload(array $overrides = []): array
    {
        return array_merge([
            'title'       => 'How do I structure role based access control here',
            'body'        => 'A body long enough to satisfy the twenty character minimum rule.',
            'category_id' => $this->category()->id,
            'tags'        => 'laravel, testing',
        ], $overrides);
    }

    /** Ask a question as the given user and return the created model. */
    protected function askQuestionAs(User $user, array $overrides = []): Question
    {
        $this->actingAs($user)
            ->post(route('questions.store'), $this->questionPayload($overrides))
            ->assertSessionHasNoErrors();

        $question = Question::where('user_id', $user->id)->latest('id')->first();

        $this->assertNotNull($question, 'The question was not persisted.');

        return $question;
    }

    /** Answer a question as the given user and return the created model. */
    protected function answerQuestionAs(User $user, Question $question, ?string $body = null)
    {
        $this->actingAs($user)
            ->post(route('answers.store', $question), [
                'body' => $body ?: 'An answer body comfortably past the ten character minimum.',
            ])
            ->assertSessionHasNoErrors();

        return $question->answers()->where('user_id', $user->id)->latest('id')->first();
    }

    // ------------------------------------------------------------------ jobs

    protected function jobPayload(array $overrides = []): array
    {
        return array_merge([
            'title'        => 'Junior Laravel Developer',
            'company'      => 'Netsol Technologies',
            'type'         => 'job',
            'work_mode'    => 'onsite',
            'location'     => 'Lahore, Pakistan',
            'description'  => 'A description long enough to pass the twenty character minimum.',
            'requirements' => 'Blade, Eloquent and MySQL.',
            'salary_min'   => 90000,
            'salary_max'   => 130000,
            'deadline'     => now()->addMonth()->format('Y-m-d'),
            'is_active'    => 1,
        ], $overrides);
    }

    /** Publish an opportunity as the given user and return the created model. */
    protected function postJobAs(User $user, array $overrides = []): JobPost
    {
        $this->actingAs($user)
            ->post(route('jobs.store'), $this->jobPayload($overrides))
            ->assertSessionHasNoErrors();

        $job = JobPost::where('user_id', $user->id)->latest('id')->first();

        $this->assertNotNull($job, 'The opportunity was not persisted.');

        return $job;
    }

    /** Apply to an opportunity as the given user. */
    protected function applyAs(User $user, JobPost $job, array $overrides = [])
    {
        return $this->actingAs($user)->post(route('jobs.apply.store', $job), array_merge([
            'cover_letter' => 'A cover letter that comfortably exceeds the thirty character minimum length.',
        ], $overrides));
    }
}
