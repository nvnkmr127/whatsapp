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
        Schema::table('knowledge_base_sources', function (Blueprint $table) {
            $table->string('status')->default('pending')->after('is_active'); // pending, processing, indexed, failed
            $table->text('error_message')->nullable()->after('status');
            $table->timestamp('last_synced_at')->nullable()->after('updated_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('knowledge_base_sources', function (Blueprint $table) {
            $table->dropColumn(['status', 'error_message', 'last_synced_at']);
        });
    }
};
