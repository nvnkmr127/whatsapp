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
        Schema::table('onboarding_statuses', function (Blueprint $table) {
            $table->integer('reminders_sent_count')->default(0);
            $table->boolean('was_recovered')->default(false);
            $table->timestamp('recovered_at')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('onboarding_statuses', function (Blueprint $table) {
            $table->dropColumn(['reminders_sent_count', 'was_recovered', 'recovered_at']);
        });
    }
};
