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
        Schema::table('whatsapp_templates', function (Blueprint $table) {
            $table->boolean('is_mobile_draft')->default(false)->after('status');
            $table->timestamp('last_status_sync_at')->nullable()->after('last_synced_at');
            $table->string('meta_submission_id')->nullable()->after('last_status_sync_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('whatsapp_templates', function (Blueprint $table) {
            $table->dropColumn(['is_mobile_draft', 'last_status_sync_at', 'meta_submission_id']);
        });
    }
};
