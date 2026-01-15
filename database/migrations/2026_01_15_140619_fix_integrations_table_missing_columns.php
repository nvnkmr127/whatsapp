<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('integrations', function (Blueprint $table) {
            if (!Schema::hasColumn('integrations', 'last_synced_at')) {
                $table->timestamp('last_synced_at')->nullable()->after('settings');
            }
            if (!Schema::hasColumn('integrations', 'error_message')) {
                $table->text('error_message')->nullable()->after('last_synced_at');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('integrations', function (Blueprint $table) {
            //
        });
    }
};
