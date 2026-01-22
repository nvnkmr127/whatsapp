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
        Schema::create('contact_merge_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('team_id')->constrained()->cascadeOnDelete();
            $table->foreignId('primary_contact_id')->constrained('contacts')->cascadeOnDelete();
            $table->unsignedBigInteger('duplicate_contact_id'); // Don't cascade - keep history
            $table->json('duplicate_data'); // Snapshot of duplicate before merge
            $table->foreignId('merged_by')->nullable()->constrained('users');
            $table->string('merge_strategy')->default('auto'); // auto, manual, suggested
            $table->float('confidence_score')->nullable();
            $table->timestamp('merged_at');
            $table->timestamps();

            $table->index(['team_id', 'primary_contact_id'], 'idx_merge_logs_team_primary');
            $table->index('duplicate_contact_id', 'idx_merge_logs_duplicate');
            $table->index('merged_at', 'idx_merge_logs_merged_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('contact_merge_logs');
    }
};
