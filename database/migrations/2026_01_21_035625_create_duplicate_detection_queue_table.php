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
        Schema::create('duplicate_detection_queue', function (Blueprint $table) {
            $table->id();
            $table->foreignId('team_id')->constrained()->cascadeOnDelete();
            $table->foreignId('contact_id')->constrained()->cascadeOnDelete();
            $table->foreignId('potential_duplicate_id')->constrained('contacts')->cascadeOnDelete();
            $table->float('confidence_score');
            $table->string('confidence_level'); // certain, high, medium, low
            $table->json('match_reasons');
            $table->enum('status', ['pending', 'approved', 'rejected', 'auto_merged'])->default('pending');
            $table->foreignId('reviewed_by')->nullable()->constrained('users');
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamps();

            $table->index(['team_id', 'status'], 'idx_dup_queue_team_status');
            $table->index('confidence_score', 'idx_dup_queue_confidence');
            $table->index(['contact_id', 'potential_duplicate_id'], 'idx_dup_queue_contacts');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('duplicate_detection_queue');
    }
};
