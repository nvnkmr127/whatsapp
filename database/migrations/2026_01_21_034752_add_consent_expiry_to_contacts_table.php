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
        Schema::table('contacts', function (Blueprint $table) {
            // Add consent expiry timestamp for GDPR compliance
            $table->timestamp('opt_in_expires_at')->nullable()->after('opt_in_at');
        });

        // Set expiry date for existing opted-in contacts (24 months from opt_in_at)
        DB::statement('
            UPDATE contacts 
            SET opt_in_expires_at = DATE_ADD(opt_in_at, INTERVAL 24 MONTH)
            WHERE opt_in_status = "opted_in" 
            AND opt_in_at IS NOT NULL
        ');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('contacts', function (Blueprint $table) {
            $table->dropColumn('opt_in_expires_at');
        });
    }
};
