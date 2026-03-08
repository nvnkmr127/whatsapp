<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pipelines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('team_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->text('description')->nullable();
            $table->boolean('is_default')->default(false);
            $table->boolean('is_active')->default(true);
            $table->integer('position')->default(0);
            $table->timestamps();
            
            $table->index(['team_id', 'is_active']);
        });

        Schema::create('pipeline_stages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pipeline_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('color', 7)->default('#3B82F6');
            $table->integer('position')->default(0);
            $table->decimal('probability', 5, 2)->default(0);
            $table->boolean('is_closed_won')->default(false);
            $table->boolean('is_closed_lost')->default(false);
            $table->integer('expected_days')->nullable();
            $table->timestamps();
            
            $table->index(['pipeline_id', 'position']);
        });

        Schema::create('deals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('team_id')->constrained()->cascadeOnDelete();
            $table->foreignId('contact_id')->constrained()->cascadeOnDelete();
            $table->foreignId('pipeline_id')->constrained()->cascadeOnDelete();
            $table->foreignId('stage_id')->constrained('pipeline_stages')->cascadeOnDelete();
            $table->foreignId('owner_id')->nullable()->constrained('users')->nullOnDelete();
            
            $table->string('title');
            $table->text('description')->nullable();
            $table->decimal('value', 15, 2)->default(0);
            $table->string('currency', 3)->default('USD');
            $table->date('expected_close_date')->nullable();
            $table->date('actual_close_date')->nullable();
            
            $table->enum('status', ['open', 'won', 'lost', 'abandoned'])->default('open');
            $table->string('lost_reason')->nullable();
            $table->text('notes')->nullable();
            
            $table->decimal('probability', 5, 2)->default(0);
            $table->decimal('weighted_value', 15, 2)->default(0);
            
            $table->json('custom_fields')->nullable();
            $table->string('source')->nullable();
            
            $table->timestamp('last_activity_at')->nullable();
            $table->integer('days_in_stage')->default(0);
            $table->integer('days_in_pipeline')->default(0);
            
            $table->timestamps();
            $table->softDeletes();
            
            $table->index(['team_id', 'status']);
            $table->index(['stage_id', 'status']);
            $table->index(['owner_id', 'status']);
            $table->index('expected_close_date');
            $table->index('created_at');
        });

        Schema::create('deal_activities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('deal_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            
            $table->enum('type', ['note', 'email', 'call', 'meeting', 'task', 'stage_change', 'status_change', 'value_change']);
            $table->text('content')->nullable();
            $table->json('metadata')->nullable();
            
            $table->timestamp('occurred_at');
            $table->timestamps();
            
            $table->index(['deal_id', 'occurred_at']);
            $table->index('type');
        });

        Schema::create('deal_products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('deal_id')->constrained()->cascadeOnDelete();
            
            $table->string('name');
            $table->text('description')->nullable();
            $table->decimal('unit_price', 15, 2)->default(0);
            $table->integer('quantity')->default(1);
            $table->decimal('discount', 5, 2)->default(0);
            $table->decimal('tax', 5, 2)->default(0);
            $table->decimal('total', 15, 2)->default(0);
            
            $table->json('metadata')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('deal_products');
        Schema::dropIfExists('deal_activities');
        Schema::dropIfExists('deals');
        Schema::dropIfExists('pipeline_stages');
        Schema::dropIfExists('pipelines');
    }
};
