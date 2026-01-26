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
        Schema::create('whatsapp_calls', function (Blueprint $table) {
            $table->id();
            $table->foreignId('team_id')->constrained()->cascadeOnDelete();
            $table->foreignId('contact_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('conversation_id')->nullable()->constrained()->nullOnDelete();

            // WhatsApp call identifiers
            $table->string('call_id')->unique()->comment('WhatsApp unique call identifier');

            // Call details
            $table->enum('direction', ['inbound', 'outbound'])->index();
            $table->enum('status', [
                'initiated',
                'ringing',
                'in_progress',
                'completed',
                'failed',
                'rejected',
                'missed',
                'no_answer'
            ])->default('initiated')->index();

            // Phone numbers
            $table->string('from_number', 20);
            $table->string('to_number', 20);

            // Timestamps
            $table->timestamp('initiated_at')->nullable();
            $table->timestamp('answered_at')->nullable();
            $table->timestamp('ended_at')->nullable();

            // Duration and billing
            $table->integer('duration_seconds')->default(0)->comment('Call duration in seconds');
            $table->decimal('cost_amount', 10, 4)->default(0)->comment('Cost in USD');

            // Additional metadata
            $table->json('metadata')->nullable()->comment('Additional call data from WhatsApp');
            $table->text('failure_reason')->nullable();

            $table->timestamps();

            // Indexes for common queries
            $table->index(['team_id', 'created_at']);
            $table->index(['contact_id', 'created_at']);
            $table->index(['status', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('whatsapp_calls');
    }
};
