<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('knowledge_base_gaps', function (Blueprint $table) {
            $table->id();
            $table->foreignId('team_id')->constrained()->onDelete('cascade');
            $table->text('query');
            $table->string('gap_type')->default('unanswered'); // unanswered, low_confidence, hallucination_risk
            $table->json('search_metadata')->nullable(); // info about what was found (low scores, etc)
            $table->json('ai_metadata')->nullable(); // reasoning from AI if possible
            $table->string('status')->default('pending'); // pending, resolved, ignored
            $table->text('resolution_note')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('knowledge_base_gaps');
    }
};
