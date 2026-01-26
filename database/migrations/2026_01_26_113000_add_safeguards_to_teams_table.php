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
            $table->json('calling_safeguards')->nullable()->after('call_routing_config');
            $table->timestamp('calling_suspended_until')->nullable()->after('calling_safeguards');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('teams', function (Blueprint $table) {
            $table->dropColumn(['calling_safeguards', 'calling_suspended_until']);
        });
    }
};
