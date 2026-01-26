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
            $table->boolean('calling_enabled')->default(false)->after('whatsapp_phone_number_id');
            $table->integer('max_call_minutes_per_month')->nullable()->after('calling_enabled');
            $table->boolean('call_recording_enabled')->default(false)->after('max_call_minutes_per_month');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('teams', function (Blueprint $table) {
            $table->dropColumn(['calling_enabled', 'max_call_minutes_per_month', 'call_recording_enabled']);
        });
    }
};
