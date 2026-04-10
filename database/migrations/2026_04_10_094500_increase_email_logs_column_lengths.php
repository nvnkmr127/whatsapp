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
            // Drop existing index first as TEXT columns cannot have default indexes in MySQL
            // without a specified length. 
            $table->dropIndex(['recipient']);
        });

        Schema::table('email_logs', function (Blueprint $table) {
            $table->text('recipient')->change();
            $table->text('subject')->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('email_logs', function (Blueprint $table) {
            $table->string('recipient', 255)->change();
            $table->string('subject', 255)->change();
        });

        Schema::table('email_logs', function (Blueprint $table) {
            $table->index('recipient');
        });
    }
};
