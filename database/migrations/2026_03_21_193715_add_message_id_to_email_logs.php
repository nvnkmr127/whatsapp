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
        Schema::table('email_logs', function (Blueprint $table) {
            if (!Schema::hasColumn('email_logs', 'message_id')) {
                $table->string('message_id')->nullable()->after('provider_name');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('email_logs', function (Blueprint $table) {
            if (Schema::hasColumn('email_logs', 'message_id')) {
                $table->dropColumn('message_id');
            }
        });
    }
};
