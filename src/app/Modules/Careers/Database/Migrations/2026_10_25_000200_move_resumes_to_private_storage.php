<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

/**
 * CVs used to be stored on the public disk, where anyone with the link could
 * open them. Move every existing file to the private disk; the paths stay the
 * same, only the disk changes.
 */
return new class extends Migration
{
    public function up(): void
    {
        $this->move('public', 'local');
    }

    public function down(): void
    {
        $this->move('local', 'public');
    }

    private function move(string $from, string $to): void
    {
        DB::table('job_applications')->whereNotNull('resume_path')->pluck('resume_path')
            ->each(function (string $path) use ($from, $to) {
                if (Storage::disk($from)->exists($path) && ! Storage::disk($to)->exists($path)) {
                    Storage::disk($to)->writeStream($path, Storage::disk($from)->readStream($path));
                    Storage::disk($from)->delete($path);
                }
            });
    }
};
