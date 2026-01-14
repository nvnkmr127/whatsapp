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
            $table->boolean('read_receipts_enabled')->default(true);
            $table->boolean('welcome_message_enabled')->default(false);
            $table->text('welcome_message')->nullable();
            $table->boolean('ai_auto_reply_enabled')->default(false);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('teams', function (Blueprint $table) {
            $table->dropColumn([
                'read_receipts_enabled',
                'welcome_message_enabled',
                'welcome_message',
                'ai_auto_reply_enabled'
            ]);
        });
    }
};
