<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('automation_runs', function (Blueprint $table) {
            $table->index(['status', 'resume_at'], 'automation_runs_status_resume_at_index');
            $table->index(['status', 'last_processed_at'], 'automation_runs_status_last_processed_at_index');
        });
    }

    public function down(): void
    {
        Schema::table('automation_runs', function (Blueprint $table) {
            $table->dropIndex('automation_runs_status_resume_at_index');
            $table->dropIndex('automation_runs_status_last_processed_at_index');
        });
    }
};

