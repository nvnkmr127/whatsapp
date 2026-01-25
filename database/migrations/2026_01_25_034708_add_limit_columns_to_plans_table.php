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
        Schema::table('plans', function (Blueprint $table) {
            $table->integer('automation_run_limit')->default(100)->after('agent_limit');
            $table->integer('contact_limit')->default(1000)->after('automation_run_limit');
            $table->integer('ai_conversation_limit')->default(50)->after('contact_limit');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('plans', function (Blueprint $table) {
            $table->dropColumn(['automation_run_limit', 'contact_limit', 'ai_conversation_limit']);
        });
    }
};
