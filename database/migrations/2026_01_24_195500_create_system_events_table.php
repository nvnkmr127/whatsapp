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
        if (!Schema::hasTable('system_events')) {
            Schema::create('system_events', function (Blueprint $table) {
                $table->id();
                $table->uuid('event_id')->unique();
                $table->string('event_type'); // Class Name
                $table->string('source')->index(); // Module
                $table->string('category')->index(); // business, operational, debug
                $table->boolean('is_signal')->default(false)->index();

                // Correlation
                $table->string('trace_id')->nullable()->index();
                $table->string('span_id')->nullable()->index();
                $table->string('parent_id')->nullable()->index();

                // Context
                $table->foreignId('team_id')->nullable()->index();
                $table->foreignId('actor_id')->nullable(); // User ID

                // Data
                $table->json('payload')->nullable();
                $table->json('metadata')->nullable();

                $table->timestamp('occurred_at')->index();
                $table->timestamps();

                // Indexes for Analytics
                $table->index(['occurred_at', 'category']);
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('system_events');
    }
};
