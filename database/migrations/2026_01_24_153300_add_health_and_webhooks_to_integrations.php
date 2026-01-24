<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('integrations', function (Blueprint $table) {
            if (!Schema::hasColumn('integrations', 'webhook_secret')) {
                $table->string('webhook_secret')->nullable()->after('credentials');
            }
            if (!Schema::hasColumn('integrations', 'last_webhook_received_at')) {
                $table->timestamp('last_webhook_received_at')->nullable()->after('last_synced_at');
            }
            if (!Schema::hasColumn('integrations', 'health_score')) {
                $table->integer('health_score')->default(100)->after('status');
            }
        });
    }

    public function down(): void
    {
        Schema::table('integrations', function (Blueprint $table) {
            $table->dropColumn(['webhook_secret', 'last_webhook_received_at', 'health_score']);
        });
    }
};
