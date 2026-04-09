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
        Schema::table('workflows', function (Blueprint $table) {
            $table->unsignedInteger('success_count')->default(0)->after('execution_count');
            $table->unsignedInteger('failure_count')->default(0)->after('success_count');
            $table->unsignedInteger('conversions_count')->default(0)->after('failure_count');
            $table->unsignedInteger('time_saved_minutes')->default(0)->after('conversions_count'); // Estimated time saved in minutes per execution
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('workflows', function (Blueprint $table) {
            $table->dropColumn(['success_count', 'failure_count', 'conversions_count', 'time_saved_minutes']);
        });
    }
};
