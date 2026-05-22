<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('conversation_locks', function (Blueprint $table) {
            $table->foreignId('conversation_id')
                ->nullable()
                ->after('team_id')
                ->constrained('conversations')
                ->cascadeOnDelete();

            $table->index('conversation_id');
        });
    }

    public function down(): void
    {
        Schema::table('conversation_locks', function (Blueprint $table) {
            $table->dropForeign(['conversation_id']);
            $table->dropIndex(['conversation_id']);
            $table->dropColumn('conversation_id');
        });
    }
};
