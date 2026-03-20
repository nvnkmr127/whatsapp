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
            $table->text('ip_whitelist')->nullable()->after('auth_config');
            $table->timestamp('last_secret_rotated_at')->nullable()->after('ip_whitelist');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('webhook_sources', function (Blueprint $table) {
            $table->dropColumn(['ip_whitelist', 'last_secret_rotated_at']);
        });
    }
};
