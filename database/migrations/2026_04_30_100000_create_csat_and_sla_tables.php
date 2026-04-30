<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // ── CSAT Ratings ──────────────────────────────────────────────────────
        Schema::create('csat_ratings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('team_id')->constrained('teams')->cascadeOnDelete();
            $table->foreignId('conversation_id')->constrained('conversations')->cascadeOnDelete();
            $table->foreignId('contact_id')->constrained('contacts')->cascadeOnDelete();
            $table->foreignId('agent_id')->nullable()->constrained('users')->nullOnDelete();
            $table->tinyInteger('rating');              // 1–5
            $table->string('feedback')->nullable();     // optional free-text
            $table->string('type')->default('csat');   // csat | nps
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['team_id', 'created_at']);
            $table->index(['agent_id', 'created_at']);
        });

        // ── SLA Policies ───────────────────────────────────────────────────────
        Schema::create('sla_policies', function (Blueprint $table) {
            $table->id();
            $table->foreignId('team_id')->constrained('teams')->cascadeOnDelete();
            $table->string('name');                              // e.g. "Default SLA"
            $table->integer('first_response_minutes');          // 0 = no limit
            $table->integer('resolution_minutes');              // 0 = no limit
            $table->boolean('is_default')->default(false);
            $table->json('business_hours')->nullable();         // JSON schedule override
            $table->timestamps();

            $table->index('team_id');
        });

        // Add SLA policy reference and breach timestamps to conversations
        Schema::table('conversations', function (Blueprint $table) {
            $table->foreignId('sla_policy_id')->nullable()->constrained('sla_policies')->nullOnDelete();
            $table->timestamp('first_response_at')->nullable();
            $table->timestamp('sla_first_response_due_at')->nullable();
            $table->timestamp('sla_resolution_due_at')->nullable();
            $table->string('sla_status')->nullable(); // on_track | breaching_soon | breached
        });
    }

    public function down(): void
    {
        Schema::table('conversations', function (Blueprint $table) {
            $table->dropForeign(['sla_policy_id']);
            $table->dropColumn(['sla_policy_id', 'first_response_at', 'sla_first_response_due_at', 'sla_resolution_due_at', 'sla_status']);
        });
        Schema::dropIfExists('sla_policies');
        Schema::dropIfExists('csat_ratings');
    }
};
