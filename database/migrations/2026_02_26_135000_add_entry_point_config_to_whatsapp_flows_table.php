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
        Schema::table('whatsapp_flows', function (Blueprint $table) {
            $table->json('entry_point_config')->nullable()->after('design_data');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('whatsapp_flows', function (Blueprint $table) {
            $table->dropColumn('entry_point_config');
        });
    }
};
