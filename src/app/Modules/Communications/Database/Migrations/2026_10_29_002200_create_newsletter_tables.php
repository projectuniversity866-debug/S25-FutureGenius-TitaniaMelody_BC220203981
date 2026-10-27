<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * FR7 "Newsletter subscription" (roadmap 3.15): subscribers with double
 * opt-in. One unguessable token per subscriber serves the confirmation and
 * the one-click unsubscribe links.
 *
 * FR7 "Email campaign management" (3.16): campaigns, and one row per
 * recipient so queued sending reaches each address exactly once.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('newsletter_subscribers', function (Blueprint $table) {
            $table->id();
            $table->string('email')->unique();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();   // when an AQDesk member subscribed
            $table->string('status', 16)->default('pending');   // pending | subscribed | unsubscribed
            $table->string('token', 64)->unique();
            $table->timestamp('confirmed_at')->nullable();
            $table->timestamp('unsubscribed_at')->nullable();
            $table->timestamps();

            $table->index('status');
        });

        Schema::create('newsletter_campaigns', function (Blueprint $table) {
            $table->id();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('subject', 150);
            $table->text('body');                                   // Markdown
            $table->string('status', 16)->default('draft');         // draft | scheduled | sending | sent
            $table->string('timezone', 64)->default('Asia/Karachi');
            $table->timestamp('scheduled_at')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->unsignedInteger('recipients_count')->default(0);
            $table->timestamps();

            $table->index(['status', 'scheduled_at']);
        });

        Schema::create('newsletter_campaign_recipients', function (Blueprint $table) {
            $table->id();
            $table->foreignId('newsletter_campaign_id')->constrained()->cascadeOnDelete();
            $table->foreignId('newsletter_subscriber_id')->nullable()->constrained()->nullOnDelete();
            $table->string('email');
            $table->string('status', 16)->default('pending');   // pending | sending | sent | failed | skipped
            $table->string('error', 500)->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamps();

            $table->unique(['newsletter_campaign_id', 'newsletter_subscriber_id'], 'campaign_recipient_unique');
            $table->index(['newsletter_campaign_id', 'status'], 'campaign_recipient_status_index');   // MySQL names stop at 64 characters
        });

        // Existing databases: the new "Send newsletters" permission for admins.
        if (Schema::hasTable('permissions') && ! DB::table('permissions')->where('slug', 'newsletter.send')->exists()) {
            $id = DB::table('permissions')->insertGetId([
                'slug' => 'newsletter.send', 'name' => 'Write and send newsletter campaigns', 'group' => 'Communications',
                'created_at' => now(), 'updated_at' => now(),
            ]);
            foreach (DB::table('roles')->where('slug', 'admin')->pluck('id') as $role) {
                DB::table('permission_role')->insert(['permission_id' => $id, 'role_id' => $role]);
            }
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('newsletter_campaign_recipients');
        Schema::dropIfExists('newsletter_campaigns');
        Schema::dropIfExists('newsletter_subscribers');
        DB::table('permissions')->where('slug', 'newsletter.send')->delete();
    }
};
