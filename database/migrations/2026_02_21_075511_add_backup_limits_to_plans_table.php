<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('plans', function (Blueprint $table) {
            $table->integer('max_backups_per_team')->default(5)->after('ai_conversation_limit');
            $table->integer('max_storage_mb')->default(512)->after('max_backups_per_team');
            $table->integer('cooldown_hours_between_backups')->default(24)->after('max_storage_mb');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('plans', function (Blueprint $table) {
            $table->dropColumn([
                'max_backups_per_team',
                'max_storage_mb',
                'cooldown_hours_between_backups',
            ]);
        });
    }
};
