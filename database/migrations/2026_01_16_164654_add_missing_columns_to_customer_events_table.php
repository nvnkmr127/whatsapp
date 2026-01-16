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
        Schema::table('customer_events', function (Blueprint $table) {
            $table->foreignId('team_id')->after('id')->constrained()->cascadeOnDelete();
            $table->foreignId('contact_id')->after('team_id')->constrained()->cascadeOnDelete();
            $table->string('event_type')->after('contact_id');
            $table->json('event_data')->nullable()->after('event_type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('customer_events', function (Blueprint $table) {
            $table->dropForeign(['team_id']);
            $table->dropForeign(['contact_id']);
            $table->dropColumn(['team_id', 'contact_id', 'event_type', 'event_data']);
        });
    }
};
