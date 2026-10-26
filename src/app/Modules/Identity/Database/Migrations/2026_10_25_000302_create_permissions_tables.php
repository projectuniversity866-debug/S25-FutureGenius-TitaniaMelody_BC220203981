<?php

use App\Modules\Identity\Support\PermissionCatalog;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Fine-grained RBAC (FR1): roles hold permissions. The default grants are
 * written here too, so an existing database keeps exactly the access it had.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('permissions', function (Blueprint $table) {
            $table->id();
            $table->string('slug', 64)->unique();   // "opportunities.publish"
            $table->string('name');
            $table->string('group', 32);
            $table->timestamps();
        });

        Schema::create('permission_role', function (Blueprint $table) {
            $table->foreignId('permission_id')->constrained()->cascadeOnDelete();
            $table->foreignId('role_id')->constrained()->cascadeOnDelete();
            $table->primary(['permission_id', 'role_id']);
        });

        $roles = DB::table('roles')->pluck('id', 'slug');

        foreach (PermissionCatalog::all() as $slug => $p) {
            $id = DB::table('permissions')->insertGetId([
                'slug' => $slug, 'name' => $p['name'], 'group' => $p['group'],
                'created_at' => now(), 'updated_at' => now(),
            ]);

            foreach ($p['roles'] as $roleSlug) {
                if (isset($roles[$roleSlug])) {
                    DB::table('permission_role')->insert(['permission_id' => $id, 'role_id' => $roles[$roleSlug]]);
                }
            }
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('permission_role');
        Schema::dropIfExists('permissions');
    }
};
