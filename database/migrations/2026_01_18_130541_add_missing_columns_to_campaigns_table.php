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
            if (!Schema::hasColumn('campaigns', 'campaign_name')) {
                $table->string('campaign_name')->nullable()->after('name');
            }
            if (!Schema::hasColumn('campaigns', 'header_params')) {
                $table->json('header_params')->nullable()->after('template_variables');
            }
            if (!Schema::hasColumn('campaigns', 'filename')) {
                $table->string('filename')->nullable()->after('status');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('campaigns', function (Blueprint $table) {
            if (Schema::hasColumn('campaigns', 'campaign_name')) {
                $table->dropColumn('campaign_name');
            }
            if (Schema::hasColumn('campaigns', 'header_params')) {
                $table->dropColumn('header_params');
            }
            if (Schema::hasColumn('campaigns', 'filename')) {
                $table->dropColumn('filename');
            }
        });
    }
};
