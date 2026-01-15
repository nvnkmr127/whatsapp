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
        Schema::table('webhook_payloads', function (Blueprint $table) {
            $table->foreignId('webhook_source_id')->nullable()->after('id')->constrained()->nullOnDelete();
            $table->json('mapped_data')->nullable()->after('payload');
            $table->string('event_type')->nullable()->after('signature');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('webhook_payloads', function (Blueprint $table) {
            $table->dropForeign(['webhook_source_id']);
            $table->dropColumn(['webhook_source_id', 'mapped_data', 'event_type']);
        });
    }
};
