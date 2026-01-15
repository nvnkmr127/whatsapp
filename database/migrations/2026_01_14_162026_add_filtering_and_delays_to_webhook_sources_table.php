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
        Schema::table('webhook_sources', function (Blueprint $table) {
            $table->json('filtering_rules')->nullable()->after('transformation_rules');
            $table->integer('process_delay')->default(0)->after('action_config'); // Delay in minutes
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('webhook_sources', function (Blueprint $table) {
            //
        });
    }
};
