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
        Schema::table('teams', function (Blueprint $table) {
            $table->string('whatsapp_setup_state')->default('NOT_CONFIGURED')->after('whatsapp_phone_verified_at');
            $table->json('whatsapp_setup_progress')->nullable()->after('whatsapp_setup_state');
            $table->timestamp('whatsapp_setup_started_at')->nullable()->after('whatsapp_setup_progress');
            $table->timestamp('whatsapp_setup_completed_at')->nullable()->after('whatsapp_setup_started_at');
            $table->boolean('whatsapp_setup_in_progress')->default(false)->after('whatsapp_setup_completed_at');
            $table->integer('whatsapp_setup_retry_count')->default(0)->after('whatsapp_setup_in_progress');

            $table->index('whatsapp_setup_state');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('teams', function (Blueprint $table) {
            $table->dropIndex(['whatsapp_setup_state']);

            $table->dropColumn([
                'whatsapp_setup_state',
                'whatsapp_setup_progress',
                'whatsapp_setup_started_at',
                'whatsapp_setup_completed_at',
                'whatsapp_setup_in_progress',
                'whatsapp_setup_retry_count',
            ]);
        });
    }
};
