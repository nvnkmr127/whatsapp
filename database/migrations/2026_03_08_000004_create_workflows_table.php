<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('workflows', function (Blueprint $table) {
            $table->id();
            $table->foreignId('team_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            
            // Trigger Configuration
            $table->string('trigger_type'); // e.g., 'contact_created', 'deal_stage_changed', 'tag_added'
            $table->json('trigger_config')->nullable(); // Specific conditions (e.g., specific tag ID)
            
            $table->integer('execution_count')->default(0);
            $table->timestamp('last_executed_at')->nullable();
            
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            
            $table->index(['team_id', 'is_active', 'trigger_type']);
        });

        Schema::create('workflow_actions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workflow_id')->constrained()->cascadeOnDelete();
            
            $table->string('action_type'); // e.g., 'send_email', 'add_tag', 'create_task', 'update_deal'
            $table->json('action_config')->nullable(); // e.g., email template ID, tag ID
            
            $table->integer('order')->default(0); // Sequence order
            $table->integer('delay_minutes')->default(0); // Delay before execution
            
            $table->timestamps();
            
            $table->index(['workflow_id', 'order']);
        });

        Schema::create('workflow_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workflow_id')->constrained()->cascadeOnDelete();
            $table->foreignId('team_id')->constrained()->cascadeOnDelete();
            
            // Subject of the workflow (Polymorphic)
            $table->nullableMorphs('subject'); // e.g., Contact, Deal
            
            $table->enum('status', ['pending', 'running', 'completed', 'failed']);
            $table->text('error_message')->nullable();
            $table->json('execution_data')->nullable(); // Snapshot of data at execution time
            
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            
            $table->timestamps();
            
            $table->index(['workflow_id', 'status']);
            $table->index(['team_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('workflow_logs');
        Schema::dropIfExists('workflow_actions');
        Schema::dropIfExists('workflows');
    }
};
