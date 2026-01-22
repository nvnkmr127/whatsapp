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
        Schema::create('contact_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('team_id')->constrained()->cascadeOnDelete();
            $table->foreignId('contact_id')->constrained()->cascadeOnDelete();
            $table->string('event_type'); // MessageReceived, ContactOptedIn, etc.
            $table->json('event_data');
            $table->timestamp('occurred_at');
            $table->string('idempotency_key')->unique();
            $table->timestamps();

            $table->index(['contact_id', 'occurred_at'], 'idx_contact_events_contact_time');
            $table->index('event_type', 'idx_contact_events_type');
            $table->index('occurred_at', 'idx_contact_events_occurred');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('contact_events');
    }
};
