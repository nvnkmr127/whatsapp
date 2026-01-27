<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Modify enum to include FLAGGED
        // Note: DB::statement is necessary for direct enum modification on some DBs, 
        // but for Laravel consistency we might need raw SQL or doctrine dbal. 
        // Since we are on MySQL/Postgres usually, using change() with enum requires careful handling.
        // Easiest is raw SQL for MySQL.

        // Check driver
        $driver = DB::getDriverName();

        if ($driver === 'mysql') {
            DB::statement("ALTER TABLE whatsapp_templates MODIFY COLUMN status ENUM('APPROVED', 'PENDING', 'REJECTED', 'PAUSED', 'DISABLED', 'FLAGGED') DEFAULT 'PENDING'");
        }

        // Add Indexes
        Schema::table('whatsapp_templates', function (Blueprint $table) {
            $table->index('whatsapp_template_id'); // For fast webhook lookups
            $table->index(['team_id', 'status']); // For fast UI filtering
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('whatsapp_templates', function (Blueprint $table) {
            $table->dropIndex(['whatsapp_template_id']);
            $table->dropIndex(['team_id', 'status']);
        });

        // Revert enum
        $driver = DB::getDriverName();
        if ($driver === 'mysql') {
            DB::statement("ALTER TABLE whatsapp_templates MODIFY COLUMN status ENUM('APPROVED', 'PENDING', 'REJECTED', 'PAUSED', 'DISABLED') DEFAULT 'PENDING'");
        }
    }
};
