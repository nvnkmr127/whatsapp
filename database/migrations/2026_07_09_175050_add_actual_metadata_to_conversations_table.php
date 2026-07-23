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
        // Guarded because the column already exists on databases that predate this
        // migration — without it the whole migration queue aborts here.
        if (Schema::hasColumn('conversations', 'metadata')) {
            return;
        }

        Schema::table('conversations', function (Blueprint $table) {
            $table->json('metadata')->nullable()->after('close_reason');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('conversations', function (Blueprint $table) {
            $table->dropColumn('metadata');
        });
    }
};
