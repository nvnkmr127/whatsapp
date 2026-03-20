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
        Schema::table('personal_access_tokens', function (Blueprint $table) {
            $table->text('ip_whitelist')->nullable()->after('abilities');
        });

        Schema::table('teams', function (Blueprint $table) {
            $table->boolean('is_sandbox_mode')->default(false)->after('whatsapp_connected');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('personal_access_tokens', function (Blueprint $table) {
            $table->dropColumn('ip_whitelist');
        });

        Schema::table('teams', function (Blueprint $table) {
            $table->dropColumn('is_sandbox_mode');
        });
    }
};
