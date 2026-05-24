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
        Schema::table('webhook_sources', function (Blueprint $table) {
            $table->foreignId('contact_tag_id')->nullable()->constrained('contact_tags')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('webhook_sources', function (Blueprint $table) {
            $table->dropForeign(['contact_tag_id']);
            $table->dropColumn('contact_tag_id');
        });
    }
};
