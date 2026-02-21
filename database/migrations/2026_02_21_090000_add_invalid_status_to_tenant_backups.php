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
        // Add 'invalid' to the status enum for tenant_backups.
        // Since many DBs don't support modifying enums directly easily, 
        // we'll use the change() method which works on most modern Laravel setups.
        Schema::table('tenant_backups', function (Blueprint $table) {
            $table->enum('status', ['pending', 'processing', 'completed', 'failed', 'pruned', 'invalid'])
                ->default('pending')
                ->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tenant_backups', function (Blueprint $table) {
            $table->enum('status', ['pending', 'processing', 'completed', 'failed', 'pruned'])
                ->default('pending')
                ->change();
        });
    }
};
