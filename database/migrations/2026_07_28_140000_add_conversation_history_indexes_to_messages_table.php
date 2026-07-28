<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('messages', function (Blueprint $table) {
            if (! $this->hasIndex('messages', 'messages_conversation_id_created_at_index')) {
                $table->index(['conversation_id', 'created_at'], 'messages_conversation_id_created_at_index');
            }
            if (! $this->hasIndex('messages', 'messages_conversation_id_direction_created_at_index')) {
                $table->index(['conversation_id', 'direction', 'created_at'], 'messages_conversation_id_direction_created_at_index');
            }
        });
    }

    public function down(): void
    {
        Schema::table('messages', function (Blueprint $table) {
            $table->dropIndex('messages_conversation_id_created_at_index');
            $table->dropIndex('messages_conversation_id_direction_created_at_index');
        });
    }

    private function hasIndex(string $table, string $index): bool
    {
        if (DB::getDriverName() === 'sqlite') {
            $indexes = DB::select("PRAGMA index_list(`{$table}`)");
            return collect($indexes)->contains('name', $index);
        }

        return ! empty(DB::select(
            "SHOW INDEX FROM `{$table}` WHERE Key_name = ?",
            [$index]
        ));
    }
};
