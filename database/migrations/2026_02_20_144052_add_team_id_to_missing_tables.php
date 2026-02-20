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
        // 1. Carts
        Schema::table('carts', function (Blueprint $table) {
            if (!Schema::hasColumn('carts', 'team_id')) {
                $table->foreignId('team_id')->nullable()->after('id')->index();
            }
        });

        // 2. Sync Sessions
        Schema::table('sync_sessions', function (Blueprint $table) {
            if (!Schema::hasColumn('sync_sessions', 'team_id')) {
                $table->foreignId('team_id')->nullable()->after('id')->index();
            }
        });

        // 3. Automations related
        Schema::table('automation_steps', function (Blueprint $table) {
            if (!Schema::hasColumn('automation_steps', 'team_id')) {
                $table->foreignId('team_id')->nullable()->after('id')->index();
            }
        });

        Schema::table('automation_runs', function (Blueprint $table) {
            if (!Schema::hasColumn('automation_runs', 'team_id')) {
                $table->foreignId('team_id')->nullable()->after('id')->index();
            }
        });

        Schema::table('automation_step_ledger', function (Blueprint $table) {
            if (!Schema::hasColumn('automation_step_ledger', 'team_id')) {
                $table->foreignId('team_id')->nullable()->after('id')->index();
            }
        });

        // 4. Webhook Payloads
        Schema::table('webhook_payloads', function (Blueprint $table) {
            if (!Schema::hasColumn('webhook_payloads', 'team_id')) {
                $table->foreignId('team_id')->nullable()->after('id')->index();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('webhook_payloads', function (Blueprint $table) {
            $table->dropColumn('team_id');
        });
        Schema::table('automation_step_ledger', function (Blueprint $table) {
            $table->dropColumn('team_id');
        });
        Schema::table('automation_runs', function (Blueprint $table) {
            $table->dropColumn('team_id');
        });
        Schema::table('automation_steps', function (Blueprint $table) {
            $table->dropColumn('team_id');
        });
        Schema::table('sync_sessions', function (Blueprint $table) {
            $table->dropColumn('team_id');
        });
        Schema::table('carts', function (Blueprint $table) {
            $table->dropColumn('team_id');
        });
    }
};
