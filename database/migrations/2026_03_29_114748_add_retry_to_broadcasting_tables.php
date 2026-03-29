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
        Schema::table('campaigns', function (Blueprint $table) {
            $table->json('retry_config')->nullable()->after('completed_at');
        });

        Schema::table('messages', function (Blueprint $table) {
            $table->integer('retry_count')->default(0)->after('status');
            $table->timestamp('next_retry_at')->nullable()->after('retry_count');
            $table->text('last_error')->nullable()->after('next_retry_at');
        });

        Schema::table('campaign_details', function (Blueprint $table) {
            $table->integer('retry_count')->default(0)->after('status');
            $table->timestamp('last_retry_at')->nullable()->after('retry_count'); // next_retry_at rename for drip context
            $table->text('last_error')->nullable()->after('last_retry_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('campaigns', function (Blueprint $table) {
            $table->dropColumn('retry_config');
        });

        Schema::table('messages', function (Blueprint $table) {
            $table->dropColumn(['retry_count', 'next_retry_at', 'last_error']);
        });

        Schema::table('campaign_details', function (Blueprint $table) {
            $table->dropColumn(['retry_count', 'last_retry_at', 'last_error']);
        });
    }
};
