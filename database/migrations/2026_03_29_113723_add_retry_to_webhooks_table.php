<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('webhook_sources', function (Blueprint $table) {
            $table->json('retry_config')->nullable()->after('action_config');
        });

        Schema::table('webhook_payloads', function (Blueprint $table) {
            $table->integer('retry_count')->default(0)->after('status');
            $table->timestamp('next_retry_at')->nullable()->after('retry_count');
            $table->text('last_error')->nullable()->after('next_retry_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('webhook_sources', function (Blueprint $table) {
            $table->dropColumn('retry_config');
        });

        Schema::table('webhook_payloads', function (Blueprint $table) {
            $table->dropColumn(['retry_count', 'next_retry_at', 'last_error']);
        });
    }
};
