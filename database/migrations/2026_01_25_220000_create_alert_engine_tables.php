<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('alert_rules', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('alert_type')->index(); // system, security, etc.
            $table->string('severity')->index(); // info, warning, critical, emergency
            $table->boolean('is_active')->default(true);

            // Triggering & Templates
            $table->json('trigger_conditions')->nullable();
            $table->string('template_slug')->nullable(); // Refers to email_templates.slug

            // Throttling
            $table->integer('throttle_seconds')->default(3600); // 1 hour default

            // Escalation
            $table->json('escalation_path')->nullable(); // [{level: 1, delay_mins: 0, roles: []}, ...]

            $table->timestamps();
        });

        Schema::create('alert_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('rule_id')->constrained('alert_rules')->onDelete('cascade');
            $table->foreignId('team_id')->nullable()->constrained('teams')->nullOnDelete();
            $table->string('suppression_key')->index(); // Hash for throttling
            $table->string('status')->index(); // processed, throttled, escalated, resolved
            $table->string('severity')->index();
            $table->json('payload')->nullable();
            $table->text('error_message')->nullable();

            $table->timestamp('triggered_at')->useCurrent();
            $table->timestamp('resolved_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('alert_logs');
        Schema::dropIfExists('alert_rules');
    }
};
