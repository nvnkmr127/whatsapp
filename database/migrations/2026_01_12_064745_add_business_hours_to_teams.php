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
            if (!Schema::hasColumn('teams', 'business_hours')) {
                $table->json('business_hours')->nullable();
            }
            if (!Schema::hasColumn('teams', 'away_message')) {
                $table->text('away_message')->nullable();
            }
            if (!Schema::hasColumn('teams', 'away_message_enabled')) {
                $table->boolean('away_message_enabled')->default(false);
            }
            if (!Schema::hasColumn('teams', 'timezone')) {
                $table->string('timezone')->default('UTC');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('teams', function (Blueprint $table) {
            //
        });
    }
};
