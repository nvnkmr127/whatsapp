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
        Schema::table('campaigns', function (Blueprint $table) {
            if (!Schema::hasColumn('campaigns', 'audience_filters')) {
                $table->json('audience_filters')->nullable();
            }
            if (!Schema::hasColumn('campaigns', 'template_language')) {
                $table->string('template_language')->default('en_US');
            }
            if (!Schema::hasColumn('campaigns', 'template_id')) {
                $table->foreignId('template_id')->nullable()->constrained('whatsapp_templates')->nullOnDelete();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('campaigns', function (Blueprint $table) {
            //
        });
    }
};
