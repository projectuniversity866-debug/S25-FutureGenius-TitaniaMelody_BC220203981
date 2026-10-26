<?php

namespace Tests\Feature\AqDesk;

use App\Support\AccessibilityPreferences;

/**
 * Suite T — accessibility toolbar and statement (2.7). Cases T1–T5.
 */
class AccessibilityToolbarTest extends AqDeskTestCase
{
    /** T1 — every page offers the toolbar; the statement page is public. */
    public function test_t1_toolbar_and_statement(): void
    {
        $this->get(route('questions.index'))->assertOk()
            ->assertSee('aria-label="Accessibility options"', false)
            ->assertSee('name="contrast"', false);
        $this->get(route('accessibility'))->assertOk()
            ->assertSee('WCAG')->assertSee('Known limitations')->assertSee('Display options');
        $this->get(route('home'))->assertSee(route('accessibility'), false);
    }

    /** T2 — saving stores a readable cookie (works without JavaScript). */
    public function test_t2_save_preferences(): void
    {
        $response = $this->from(route('questions.index'))->post(route('accessibility.update'), [
            'text' => 'larger', 'contrast' => '1', 'motion' => '1',
        ])->assertRedirect(route('questions.index'));

        $response->assertPlainCookie(AccessibilityPreferences::COOKIE, 'text:larger,contrast,motion');
    }

    /** T3 — the page applies the preferences before it is drawn (classes on <html>). */
    public function test_t3_classes_on_html(): void
    {
        $this->withUnencryptedCookie(AccessibilityPreferences::COOKIE, 'text:large,readable,spacing')
            ->get(route('questions.index'))
            ->assertSee('class="a11y-text-large a11y-readable a11y-spacing"', false)
            ->assertSee('id="a11y_readable" name="readable" value="1"', false);

        // Also on the sign-in pages (separate layout).
        $this->withUnencryptedCookie(AccessibilityPreferences::COOKIE, 'contrast')->get(route('login'))
            ->assertSee('class="a11y-contrast"', false);
    }

    /** T4 — unknown values are ignored; reset clears everything. */
    public function test_t4_bad_values_and_reset(): void
    {
        $this->assertSame('text:large,motion', AccessibilityPreferences::parse('text:huge,text:large,<script>,motion,motion')->toCookieValue());

        $this->post(route('accessibility.update'), ['text' => 'enormous', 'sparkles' => '1'])
            ->assertPlainCookie(AccessibilityPreferences::COOKIE, '');
        $this->post(route('accessibility.update'), ['reset' => '1', 'contrast' => '1'])
            ->assertPlainCookie(AccessibilityPreferences::COOKIE, '');
    }

    /** T5 — signed-in members get the same toolbar. */
    public function test_t5_members_too(): void
    {
        $this->actingAs($this->member())->get(route('dashboard'))->assertSee('Display options');
    }
}
