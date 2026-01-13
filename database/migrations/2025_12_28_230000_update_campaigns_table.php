<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('campaigns', function (Blueprint $table) {
            $table->json('segment_config')->nullable()->after('template_variables');
            $table->string('language')->default('en_US')->after('template_name');
        });
    }

    public function down(): void
    {
        Schema::table('campaigns', function (Blueprint $table) {
            $table->dropColumn(['segment_config', 'language']);
        });
    }
};
