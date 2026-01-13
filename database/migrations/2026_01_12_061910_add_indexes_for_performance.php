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
        Schema::table('messages', function (Blueprint $table) {
            // Optimize "Show me all messages for this team sorted by time"
            $table->index(['team_id', 'created_at']);
            // Optimize "Show conversation messages"
            $table->index(['conversation_id', 'created_at']);
        });

        Schema::table('contacts', function (Blueprint $table) {
            // Optimize lookup by phone within team
            $table->index(['team_id', 'phone_number']);
        });

        Schema::table('webhook_payloads', function (Blueprint $table) {
            // Optimize cleanup of old logs
            $table->index(['created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('messages', function (Blueprint $table) {
            $table->dropIndex(['team_id', 'created_at']);
            $table->dropIndex(['conversation_id', 'created_at']);
        });

        Schema::table('contacts', function (Blueprint $table) {
            $table->dropIndex(['team_id', 'phone_number']);
        });

        Schema::table('webhook_payloads', function (Blueprint $table) {
            $table->dropIndex(['created_at']);
        });
    }
};
