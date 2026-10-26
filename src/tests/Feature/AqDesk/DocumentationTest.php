<?php

namespace Tests\Feature\AqDesk;

use App\Modules\Platform\Models\FormField;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;

/**
 * Suite DC — deliverables (5.3, 5.4): the user manual matches the app, and
 * the ERDs and schema SQL match the migrations. Cases DC1–DC5.
 */
class DocumentationTest extends AqDeskTestCase
{
    private string $out;

    protected function setUp(): void
    {
        parent::setUp();
        $this->out = storage_path('framework/testing/docs');
        File::deleteDirectory($this->out);
    }

    protected function tearDown(): void
    {
        File::deleteDirectory($this->out);
        parent::tearDown();
    }

    /** @return array<int, string> */
    private function migratedTables(): array
    {
        return array_values(array_diff(Schema::getTableListing(schemaQualified: false), ['migrations']));
    }

    private function generate(): void
    {
        $this->artisan('docs:schema', ['--diagrams' => $this->out.'/erd', '--sql' => $this->out.'/schema.sql'])->assertSuccessful();
    }

    /** DC1 — every table gets a CREATE statement and every module table a diagram entity. */
    public function test_dc1_every_table_documented(): void
    {
        $this->generate();
        $sql = File::get($this->out.'/schema.sql');
        $diagrams = collect(File::glob($this->out.'/erd/*.mmd'))->map(fn ($f) => File::get($f))->implode("\n");

        $this->assertFileExists($this->out.'/erd/00-overview.mmd');
        $this->assertFileExists($this->out.'/erd/README.md');
        foreach ($this->migratedTables() as $table) {
            $this->assertStringContainsString('CREATE TABLE "'.$table.'"', $sql, $table);
            if (! in_array($table, ['cache', 'cache_locks', 'failed_jobs', 'job_batches', 'jobs', 'password_reset_tokens', 'sessions'], true)) {
                $this->assertStringContainsString("    {$table} {\n", $diagrams, $table);
            }
        }
    }

    /** DC2 — foreign keys become relationships with the right cardinality. */
    public function test_dc2_relationships(): void
    {
        $this->generate();
        $overview = File::get($this->out.'/erd/00-overview.mmd');
        $consulting = File::get($this->out.'/erd/04-consulting.mmd');

        $this->assertStringContainsString('consultant_profiles ||--o{ consultations : "consultant_profile_id"', $overview);
        $this->assertStringContainsString('users ||--o| profiles : "user_id"', $overview);
        $this->assertStringContainsString('        integer consultant_profile_id FK', $consulting);
        $this->assertStringNotContainsString('sessions', $overview);
    }

    /** DC3 — the committed schema SQL is MySQL/InnoDB and has every migrated table (regenerate after new migrations). */
    public function test_dc3_committed_schema_matches_migrations(): void
    {
        $sql = File::get(dirname(base_path()).'/schema/aqdesk_schema.sql');

        $this->assertStringContainsString('MySQL', strtok($sql, "\n").strtok("\n"));
        $this->assertStringNotContainsString('MyISAM', $sql);
        foreach ($this->migratedTables() as $table) {
            $this->assertStringContainsString('CREATE TABLE `'.$table.'`', $sql, "$table missing: run php artisan docs:schema on MySQL");
        }
        $this->assertSame('InnoDB', config('database.connections.mysql.engine'));
        $this->assertSame('InnoDB', config('database.connections.mariadb.engine'));
    }

    /** DC4 — every address the user manual names is a real page. */
    public function test_dc4_manual_addresses_exist(): void
    {
        $manual = File::get(dirname(base_path()).'/docs/USER-MANUAL.md');
        preg_match_all('#`(/[a-z0-9/_-]+)`#', $manual, $m);
        $uris = collect(Route::getRoutes()->getRoutes())->filter(fn ($r) => in_array('GET', $r->methods(), true))->map(fn ($r) => '/'.ltrim($r->uri(), '/'));

        $this->assertNotEmpty($m[1]);
        foreach (array_unique($m[1]) as $path) {
            $this->assertTrue($uris->contains($path), "$path in the manual is not a page");
        }
    }

    /** DC5 — the forms guide covers every field type and the creator's buttons. */
    public function test_dc5_manual_covers_forms(): void
    {
        $manual = File::get(dirname(base_path()).'/docs/USER-MANUAL.md');

        $this->assertStringContainsString('Part B — Forms: guide for creators and respondents', $manual);
        foreach (FormField::TYPES as [$label]) {
            $this->assertStringContainsString($label, $manual);
        }
        foreach (['New form', 'Save question', 'Save settings', 'Preview', 'Open for responses', 'Download CSV', 'Submit'] as $button) {
            $this->assertStringContainsString('**'.$button.'**', $manual);
        }
    }
}
