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
        Schema::table('automations', function (Blueprint $table) {
            if (!Schema::hasColumn('automations', 'flow_data')) {
                $table->json('flow_data')->nullable()->after('trigger_config');
            }
            $table->integer('version')->default(1)->after('name');
            $table->timestamp('last_published_at')->nullable()->after('is_active');
            $table->json('publish_log')->nullable()->after('last_published_at');
        });
    }

    public function down(): void
    {
        Schema::table('automations', function (Blueprint $table) {
            $table->dropColumn(['version', 'last_published_at', 'publish_log']);
            // Not dropping flow_data as it might have been there before or needed.
        });
    }
};
