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
        Schema::create('calling_consent_log', function (Blueprint $table) {
            $table->id();
            $table->foreignId('team_id')->constrained()->onDelete('cascade');
            $table->foreignId('contact_id')->constrained()->onDelete('cascade');

            // Consent details
            $table->enum('consent_type', ['explicit', 'implicit', 'scheduled'])->default('implicit');
            $table->string('consent_signal')->nullable(); // "button_click", "message_keyword", etc.
            $table->text('consent_message')->nullable(); // Original user message
            $table->timestamp('consent_given_at');
            $table->timestamp('consent_expires_at')->nullable();

            // Context
            $table->enum('trigger_type', ['user_initiated', 'agent_offered']);
            $table->foreignId('conversation_id')->nullable()->constrained()->onDelete('set null');
            $table->unsignedBigInteger('automation_id')->nullable();
            $table->foreignId('agent_id')->nullable()->constrained('users')->onDelete('set null');

            // Validation
            $table->boolean('within_24h_window')->default(false);
            $table->boolean('active_chat')->default(false);

            // Metadata
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();

            $table->timestamps();

            // Indexes
            $table->index(['team_id', 'contact_id']);
            $table->index('consent_given_at');
            $table->index('trigger_type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('calling_consent_log');
    }
};
