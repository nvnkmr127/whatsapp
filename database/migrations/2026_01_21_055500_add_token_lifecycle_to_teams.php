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
            $table->timestamp('whatsapp_token_expires_at')->nullable()->after('whatsapp_access_token');
            $table->timestamp('whatsapp_token_last_validated')->nullable()->after('whatsapp_token_expires_at');
            $table->string('whatsapp_phone_status')->default('unknown')->after('whatsapp_phone_number_id');
            $table->timestamp('whatsapp_phone_status_checked_at')->nullable()->after('whatsapp_phone_status');
            $table->string('whatsapp_phone_verification_status')->default('unverified')->after('whatsapp_phone_status_checked_at');
            $table->timestamp('whatsapp_phone_verified_at')->nullable()->after('whatsapp_phone_verification_status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('teams', function (Blueprint $table) {
            $table->dropColumn([
                'whatsapp_token_expires_at',
                'whatsapp_token_last_validated',
                'whatsapp_phone_status',
                'whatsapp_phone_status_checked_at',
                'whatsapp_phone_verification_status',
                'whatsapp_phone_verified_at',
            ]);
        });
    }
};
