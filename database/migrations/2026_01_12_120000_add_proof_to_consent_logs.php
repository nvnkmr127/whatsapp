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
        Schema::table('consent_logs', function (Blueprint $table) {
            $table->string('proof_url')->nullable()->after('notes'); // URL to screenshot/doc
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('consent_logs', function (Blueprint $table) {
            $table->dropColumn('proof_url');
        });
    }
};
