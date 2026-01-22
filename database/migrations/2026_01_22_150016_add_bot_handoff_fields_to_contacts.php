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
        Schema::table('contacts', function (Blueprint $table) {
            $table->boolean('is_bot_paused')->default(false)->after('total_tags_count');
            $table->timestamp('bot_paused_at')->nullable()->after('is_bot_paused');
            $table->string('bot_paused_reason')->nullable()->after('bot_paused_at');
            $table->timestamp('bot_paused_until')->nullable()->after('bot_paused_reason');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('contacts', function (Blueprint $table) {
            $table->dropColumn(['is_bot_paused', 'bot_paused_at', 'bot_paused_reason', 'bot_paused_until']);
        });
    }
};
