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
        Schema::create('call_permissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('team_id')->constrained()->cascadeOnDelete();
            $table->string('phone_number_id')->index();
            $table->foreignId('contact_id')->constrained()->cascadeOnDelete();

            // Permission tracking
            $table->enum('permission_status', [
                'requested',
                'granted',
                'denied',
                'expired',
                'revoked'
            ])->default('requested');

            // Timestamps for permission lifecycle
            $table->timestamp('permission_requested_at')->nullable();
            $table->timestamp('permission_granted_at')->nullable();
            $table->timestamp('permission_expires_at')->nullable();

            // Call tracking
            $table->unsignedInteger('calls_made_count')->default(0);
            $table->timestamp('last_call_at')->nullable();

            // Rate limiting tracking
            $table->unsignedInteger('requests_in_24h')->default(0);
            $table->unsignedInteger('requests_in_7d')->default(0);
            $table->timestamp('first_request_in_24h')->nullable();
            $table->timestamp('first_request_in_7d')->nullable();

            $table->timestamps();

            // Indexes for efficient querying
            $table->index(['team_id', 'contact_id']);
            $table->index(['permission_status', 'permission_expires_at']);
            $table->index('permission_requested_at');

            // Unique constraint: one active permission per contact per phone number
            $table->unique(['contact_id', 'phone_number_id', 'permission_status'], 'call_perm_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('call_permissions');
    }
};
