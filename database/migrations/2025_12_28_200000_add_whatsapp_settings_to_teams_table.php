<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('teams', function (Blueprint $table) {
            $table->string('whatsapp_phone_number_id')->nullable();
            $table->string('whatsapp_business_account_id')->nullable();
            $table->text('whatsapp_access_token')->nullable(); // Encrypted usually, but text for now
            $table->boolean('whatsapp_connected')->default(false);
        });
    }

    public function down(): void
    {
        Schema::table('teams', function (Blueprint $table) {
            $table->dropColumn([
                'whatsapp_phone_number_id',
                'whatsapp_business_account_id',
                'whatsapp_access_token',
                'whatsapp_connected',
            ]);
        });
    }
};
