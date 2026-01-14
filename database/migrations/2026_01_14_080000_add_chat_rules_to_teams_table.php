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
        Schema::table('teams', function (Blueprint $table) {
            $table->json('chat_assignment_config')->nullable();
            $table->json('chat_status_rules')->nullable();
        });

        Schema::table('team_user', function (Blueprint $table) {
            $table->boolean('receives_tickets')->default(true);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('teams', function (Blueprint $table) {
            $table->dropColumn(['chat_assignment_config', 'chat_status_rules']);
        });

        Schema::table('team_user', function (Blueprint $table) {
            $table->dropColumn('receives_tickets');
        });
    }
};
