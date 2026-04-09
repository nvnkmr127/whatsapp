<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('messages', function (Blueprint $table) {
            if (! Schema::hasColumn('messages', 'automation_id')) {
                $table->foreignId('automation_id')
                    ->nullable()
                    ->after('webhook_source_id')
                    ->constrained('automations')
                    ->nullOnDelete();
            }

            if (! Schema::hasColumn('messages', 'automation_run_id')) {
                $table->foreignId('automation_run_id')
                    ->nullable()
                    ->after('automation_id')
                    ->constrained('automation_runs')
                    ->nullOnDelete();
            }

            $table->index(['team_id', 'automation_id', 'created_at'], 'messages_team_automation_created_idx');
            $table->index(['team_id', 'automation_run_id', 'created_at'], 'messages_team_automation_run_created_idx');
        });
    }

    public function down(): void
    {
        Schema::table('messages', function (Blueprint $table) {
            if (Schema::hasColumn('messages', 'automation_run_id')) {
                $table->dropIndex('messages_team_automation_run_created_idx');
                $table->dropConstrainedForeignId('automation_run_id');
            }

            if (Schema::hasColumn('messages', 'automation_id')) {
                $table->dropIndex('messages_team_automation_created_idx');
                $table->dropConstrainedForeignId('automation_id');
            }
        });
    }
};
