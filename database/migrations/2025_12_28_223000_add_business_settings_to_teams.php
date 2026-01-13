<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('teams', function (Blueprint $table) {
            $table->json('business_hours')->nullable();
            $table->string('timezone')->default('UTC');
            $table->text('away_message')->nullable();
            $table->boolean('away_message_enabled')->default(false);
        });
    }

    public function down(): void
    {
        Schema::table('teams', function (Blueprint $table) {
            $table->dropColumn(['business_hours', 'timezone', 'away_message', 'away_message_enabled']);
        });
    }
};
