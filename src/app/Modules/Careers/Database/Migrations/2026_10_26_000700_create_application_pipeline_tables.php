<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Application pipeline (roadmap 1.14): every status change is kept with who
 * made it and an optional message to the applicant, and reviewers keep
 * private notes. Existing applications get their history reconstructed:
 * "submitted" at the application date, then the current status if it moved.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('application_status_changes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('job_application_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete(); // who changed it
            $table->string('from_status', 20)->nullable();                           // null = submitted
            $table->string('to_status', 20);
            $table->text('message')->nullable();                                      // shown to the applicant
            $table->timestamp('created_at')->useCurrent();

            $table->index(['job_application_id', 'created_at']);
        });

        Schema::create('application_notes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('job_application_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete(); // reviewer
            $table->text('body');
            $table->timestamps();
        });

        foreach (DB::table('job_applications')->get() as $application) {
            $rows = [['job_application_id' => $application->id, 'user_id' => $application->user_id,
                'from_status' => null, 'to_status' => 'pending', 'created_at' => $application->created_at]];
            if ($application->status !== 'pending') {
                $rows[] = ['job_application_id' => $application->id, 'user_id' => null,
                    'from_status' => 'pending', 'to_status' => $application->status, 'created_at' => $application->updated_at];
            }
            DB::table('application_status_changes')->insert($rows);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('application_notes');
        Schema::dropIfExists('application_status_changes');
    }
};
