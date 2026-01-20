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
        // First, clean up any duplicate whatsapp_message_id values
        // Keep the oldest message for each duplicate whatsapp_message_id
        DB::statement("
            DELETE m1 FROM messages m1
            INNER JOIN messages m2 
            WHERE m1.whatsapp_message_id = m2.whatsapp_message_id
            AND m1.whatsapp_message_id IS NOT NULL
            AND m1.id > m2.id
        ");

        Schema::table('messages', function (Blueprint $table) {
            // Drop existing index if it exists (wrapped in try-catch to handle if it doesn't exist)
            try {
                $sm = Schema::getConnection()->getDoctrineSchemaManager();
                $indexes = $sm->listTableIndexes('messages');

                // Check if a non-unique index exists
                if (isset($indexes['messages_whatsapp_message_id_index'])) {
                    $table->dropIndex(['whatsapp_message_id']);
                }
            } catch (\Exception $e) {
                // Index doesn't exist, that's fine
            }

            // Add unique constraint
            $table->unique('whatsapp_message_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('messages', function (Blueprint $table) {
            $table->dropUnique(['whatsapp_message_id']);
            $table->index('whatsapp_message_id');
        });
    }
};
