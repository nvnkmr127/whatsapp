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
        Schema::table('message_bots', function (Blueprint $table) {
            $table->foreignId('original_bot_id')->nullable()->after('team_id')->constrained('message_bots')->nullOnDelete();
            $table->timestamp('last_published_at')->nullable()->after('is_bot_active');
            $table->foreignId('last_modified_by')->nullable()->after('last_published_at')->constrained('users')->nullOnDelete();
            $table->unsignedInteger('daily_sending_count')->default(0)->after('sending_count');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('message_bots', function (Blueprint $table) {
            $table->dropForeign(['original_bot_id']);
            $table->dropForeign(['last_modified_by']);
            $table->dropColumn(['original_bot_id', 'last_published_at', 'last_modified_by', 'daily_sending_count']);
        });
    }
};
