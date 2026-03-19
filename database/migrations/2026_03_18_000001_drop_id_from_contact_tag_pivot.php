<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasTable('contact_tag_pivot')) {
            return;
        }

        if (!Schema::hasColumn('contact_tag_pivot', 'id')) {
            return;
        }

        if (DB::getDriverName() !== 'sqlite') {
            // In MySQL/MariaDB, we must remove AUTO_INCREMENT before dropping Primary Key
            DB::statement('ALTER TABLE contact_tag_pivot MODIFY id INT NOT NULL');
            DB::statement('ALTER TABLE contact_tag_pivot DROP PRIMARY KEY');

            Schema::table('contact_tag_pivot', function (Blueprint $table) {
                $table->dropColumn('id');
            });
        }
    }

    public function down(): void
    {
        if (!Schema::hasTable('contact_tag_pivot')) {
            return;
        }

        if (Schema::hasColumn('contact_tag_pivot', 'id')) {
            return;
        }

        Schema::table('contact_tag_pivot', function (Blueprint $table) {
            $table->id();
        });
    }
};

