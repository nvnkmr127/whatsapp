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
        Schema::table('teams', function (Blueprint $table) {
            $table->string('whatsapp_app_id')->nullable()->after('whatsapp_business_account_id');
            $table->string('whatsapp_verify_token')->nullable()->after('whatsapp_app_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('teams', function (Blueprint $table) {
            $table->dropColumn(['whatsapp_app_id', 'whatsapp_verify_token']);
        });
    }
};
