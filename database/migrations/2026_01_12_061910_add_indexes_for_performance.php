<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {

    private function indexExists($table, $indexName): bool
    {
        $indexes = DB::select("SHOW INDEX FROM {$table} WHERE Key_name = ?", [$indexName]);
        return !empty($indexes);
    }

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('messages', function (Blueprint $table) {
            // Optimize "Show me all messages for this team sorted by time"
            if (!$this->indexExists('messages', 'messages_team_id_created_at_index')) {
                $table->index(['team_id', 'created_at']);
            }
            // Optimize "Show conversation messages" - only if conversation_id column exists
            if (
                Schema::hasColumn('messages', 'conversation_id') &&
                !$this->indexExists('messages', 'messages_conversation_id_created_at_index')
            ) {
                $table->index(['conversation_id', 'created_at']);
            }
        });

        Schema::table('contacts', function (Blueprint $table) {
            // Optimize lookup by phone within team
            if (!$this->indexExists('contacts', 'contacts_team_id_phone_number_index')) {
                $table->index(['team_id', 'phone_number']);
            }
        });

        Schema::table('webhook_payloads', function (Blueprint $table) {
            // Optimize cleanup of old logs
            if (!$this->indexExists('webhook_payloads', 'webhook_payloads_created_at_index')) {
                $table->index(['created_at']);
            }
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
