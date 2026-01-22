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
        Schema::table('automation_runs', function (Blueprint $table) {
            $table->json('execution_history')->nullable()->after('state_data');
            $table->text('error_message')->nullable()->after('execution_history');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('automation_runs', function (Blueprint $table) {
            $table->dropColumn(['execution_history', 'error_message']);
        });
    }
};
