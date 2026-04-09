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
        Schema::table('tenant_backups', function (Blueprint $table) {
            // Change enum to string for flexibility and add new states
            $table->string('status')->default('pending')->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tenant_backups', function (Blueprint $table) {
            $table->enum('status', ['pending', 'processing', 'completed', 'failed', 'pruned'])->default('pending')->change();
        });
    }
};
