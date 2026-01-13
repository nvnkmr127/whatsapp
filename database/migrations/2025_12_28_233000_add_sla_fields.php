<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('contacts', function (Blueprint $table) {
            $table->timestamp('last_customer_message_at')->nullable();
            $table->boolean('has_pending_reply')->default(false);
            $table->timestamp('sla_breached_at')->nullable(); // To track if we already alerted
        });
    }

    public function down(): void
    {
        Schema::table('contacts', function (Blueprint $table) {
            $table->dropColumn(['last_customer_message_at', 'has_pending_reply', 'sla_breached_at']);
        });
    }
};
