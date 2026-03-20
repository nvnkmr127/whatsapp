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
        Schema::table('campaign_details', function (Blueprint $table) {
            $table->unsignedInteger('current_step')->default(1)->after('variant');
            $table->timestamp('next_step_at')->nullable()->after('current_step');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('campaign_details', function (Blueprint $table) {
            $table->dropColumn(['current_step', 'next_step_at']);
        });
    }
};
