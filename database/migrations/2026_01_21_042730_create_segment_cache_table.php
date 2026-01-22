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
        Schema::create('segment_cache', function (Blueprint $table) {
            $table->id();
            $table->foreignId('segment_id')->unique()->constrained()->cascadeOnDelete();
            $table->mediumText('contact_ids'); // Comma-separated contact IDs
            $table->unsignedInteger('member_count');
            $table->timestamp('cached_at')->useCurrent();
            $table->timestamp('expires_at')->nullable();

            $table->index('expires_at', 'idx_segment_cache_expires');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('segment_cache');
    }
};
