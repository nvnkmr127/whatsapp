<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('automation_runs', function (Blueprint $table) {
            $table->timestamp('resume_at')->nullable()->after('last_processed_at');
        });
    }

    public function down(): void
    {
        Schema::table('automation_runs', function (Blueprint $table) {
            $table->dropColumn('resume_at');
        });
    }
};
