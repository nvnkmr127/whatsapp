<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // The Flow/Bot definition
        Schema::create('automations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('team_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->boolean('is_active')->default(false);
            $table->string('trigger_type'); // keyword, conversation_opened, tag_added, manual
            $table->json('trigger_config')->nullable(); // e.g. {"keywords": ["hi", "hello"]}
            $table->timestamps();
        });

        // Steps in the Flow
        Schema::create('automation_steps', function (Blueprint $table) {
            $table->id();
            $table->foreignId('automation_id')->constrained()->cascadeOnDelete();
            $table->string('type'); // message, delay, condition, action
            $table->json('config')->nullable(); // content, delay_seconds, etc.
            $table->foreignId('parent_step_id')->nullable()->constrained('automation_steps')->nullOnDelete();
            $table->integer('order_index')->default(0);
            $table->timestamps();
        });

        // Running state for contacts in a flow
        Schema::create('automation_runs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('automation_id')->constrained()->cascadeOnDelete();
            $table->foreignId('contact_id')->constrained()->cascadeOnDelete();
            $table->foreignId('current_step_id')->nullable()->constrained('automation_steps');
            $table->enum('status', ['active', 'completed', 'failed', 'paused'])->default('active');
            $table->timestamp('last_processed_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('automation_runs');
        Schema::dropIfExists('automation_steps');
        Schema::dropIfExists('automations');
    }
};
