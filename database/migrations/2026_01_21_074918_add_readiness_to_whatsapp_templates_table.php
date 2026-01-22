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
        Schema::table('whatsapp_templates', function (Blueprint $table) {
            $table->integer('readiness_score')->default(0)->after('status');
            $table->json('validation_results')->nullable()->after('readiness_score');
            $table->boolean('is_paused')->default(false)->after('status');
        });
    }

    public function down(): void
    {
        Schema::table('whatsapp_templates', function (Blueprint $table) {
            $table->dropColumn(['readiness_score', 'validation_results', 'is_paused']);
        });
    }
};
