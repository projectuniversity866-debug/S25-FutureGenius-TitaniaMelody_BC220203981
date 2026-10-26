<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Tables created while the MySQL server defaulted to MyISAM have no
 * transactions or row locks (booking, waitlist) and no foreign keys. Convert
 * them to InnoDB; config/database.php now forces InnoDB for new tables
 * (roadmap 5.4). The foreign keys MyISAM dropped come back only with a fresh
 * build (`php artisan migrate:fresh --seed`). SQLite: nothing to do.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! in_array(DB::getDriverName(), ['mysql', 'mariadb'], true)) {
            return;
        }

        $tables = DB::select("SELECT TABLE_NAME AS name FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND ENGINE = 'MyISAM'");
        foreach ($tables as $table) {
            DB::statement("ALTER TABLE `{$table->name}` ENGINE = InnoDB");
        }
    }

    public function down(): void
    {
        // Converting back to MyISAM would only bring the problem back.
    }
};
