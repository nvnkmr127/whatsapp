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
            if (!Schema::hasColumn('email_logs', 'opened_at')) {
                $table->timestamp('opened_at')->nullable()->after('failed_at');
            }
            if (!Schema::hasColumn('email_logs', 'clicked_at')) {
                $table->timestamp('clicked_at')->nullable()->after('opened_at');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('email_logs', function (Blueprint $table) {
            $table->dropColumn(['opened_at', 'clicked_at']);
        });
    }
};
