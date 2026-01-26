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
        Schema::table('team_user', function (Blueprint $table) {
            $table->string('call_status')->default('available')->after('receives_tickets');
            $table->boolean('is_call_enabled')->default(true)->after('call_status');
            $table->timestamp('last_call_ended_at')->nullable()->after('is_call_enabled');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('team_user', function (Blueprint $table) {
            $table->dropColumn(['call_status', 'is_call_enabled', 'last_call_ended_at']);
        });
    }
};
