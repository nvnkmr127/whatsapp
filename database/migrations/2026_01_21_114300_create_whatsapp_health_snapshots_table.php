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
        Schema::create('whatsapp_health_snapshots', function (Blueprint $table) {
            $table->id();
            $table->foreignId('team_id')->constrained()->onDelete('cascade');

            // Overall health
            $table->integer('health_score')->default(0);
            $table->enum('health_status', ['healthy', 'warning', 'critical'])->default('warning');

            // Dimension scores
            $table->integer('token_health_score')->default(0);
            $table->integer('phone_health_score')->default(0);
            $table->integer('quality_health_score')->default(0);
            $table->integer('messaging_health_score')->default(0);

            // Token metrics
            $table->boolean('token_valid')->default(false);
            $table->timestamp('token_expires_at')->nullable();
            $table->integer('token_days_until_expiry')->nullable();

            // Phone metrics
            $table->boolean('phone_verified')->default(false);
            $table->string('phone_status')->nullable();

            // Quality metrics
            $table->string('quality_rating')->nullable();
            $table->string('quality_trend')->nullable();

            // Messaging metrics
            $table->string('messaging_tier')->nullable();
            $table->integer('daily_limit')->nullable();
            $table->integer('current_usage')->nullable();
            $table->decimal('usage_percent', 5, 2)->nullable();

            // Metadata
            $table->timestamp('snapshot_at');
            $table->timestamps();

            $table->index(['team_id', 'snapshot_at']);
            $table->index('health_status');
            $table->index('snapshot_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('whatsapp_health_snapshots');
    }
};
