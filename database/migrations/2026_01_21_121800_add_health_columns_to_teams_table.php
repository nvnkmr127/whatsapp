<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('teams', function (Blueprint $table) {
            $table->string('whatsapp_messaging_limit')->nullable()->after('whatsapp_access_token');
            $table->string('whatsapp_quality_rating')->nullable()->after('whatsapp_messaging_limit');
            $table->string('whatsapp_phone_display')->nullable()->after('whatsapp_quality_rating');
            $table->string('whatsapp_verified_name')->nullable()->after('whatsapp_phone_display');
        });
    }

    public function down(): void
    {
        Schema::table('teams', function (Blueprint $table) {
            $table->dropColumn([
                'whatsapp_messaging_limit',
                'whatsapp_quality_rating',
                'whatsapp_phone_display',
                'whatsapp_verified_name',
            ]);
        });
    }
};
