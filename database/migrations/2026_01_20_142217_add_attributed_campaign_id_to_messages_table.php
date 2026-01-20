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
        Schema::table('messages', function (Blueprint $column) {
            $column->foreignId('attributed_campaign_id')
                ->nullable()
                ->constrained('campaigns')
                ->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('messages', function (Blueprint $column) {
            $column->dropForeign(['attributed_campaign_id']);
            $column->dropColumn('attributed_campaign_id');
        });
    }
};
