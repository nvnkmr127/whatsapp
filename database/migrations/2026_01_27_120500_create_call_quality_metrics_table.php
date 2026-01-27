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
        Schema::create('call_quality_metrics', function (Blueprint $table) {
            $table->id();
            $table->foreignId('whatsapp_call_id')->constrained('whatsapp_calls')->onDelete('cascade');

            // Timing metrics
            $table->timestamp('sdp_offer_received_at')->nullable();
            $table->timestamp('sdp_answer_sent_at')->nullable();
            $table->timestamp('connection_established_at')->nullable();
            $table->integer('answer_latency_ms')->nullable()->comment('Time from offer to answer in milliseconds');
            $table->integer('connection_latency_ms')->nullable()->comment('Time from answer to connection in milliseconds');

            // Network quality
            $table->integer('ice_candidates_count')->default(0);
            $table->string('selected_codec', 50)->nullable();
            $table->string('connection_type', 50)->nullable()->comment('relay, srflx, host, prflx');
            $table->integer('network_quality_score')->nullable()->comment('1-5 scale, 5 being best');

            // Error tracking
            $table->json('error_logs')->nullable();
            $table->integer('retry_attempts')->default(0);
            $table->boolean('validation_passed')->default(true);
            $table->json('validation_warnings')->nullable();

            // Performance metrics
            $table->integer('api_response_time_ms')->nullable();
            $table->string('webrtc_state', 50)->nullable()->comment('new, connecting, connected, disconnected, failed, closed');

            $table->timestamps();

            // Indexes for common queries
            $table->index('sdp_offer_received_at');
            $table->index('network_quality_score');
            $table->index('validation_passed');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('call_quality_metrics');
    }
};
