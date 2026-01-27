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
        Schema::create('call_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('team_id')->constrained()->cascadeOnDelete();
            $table->string('phone_number_id')->unique();

            // Basic calling settings
            $table->boolean('calling_enabled')->default(false);
            $table->enum('call_icon_visibility', ['show', 'hide'])->default('hide');

            // Business hours configuration (JSON)
            // Format: {"timezone": "Asia/Kolkata", "hours": [{"day": "MON", "open": "09:00", "close": "17:00"}]}
            $table->json('business_hours')->nullable();

            // Callback permission
            $table->boolean('callback_permission_enabled')->default(false);

            // SIP configuration (JSON) - sensitive data
            // Format: {"uri": "sip:...", "username": "...", "password": "..."}
            $table->json('sip_config')->nullable();

            // Call icon configuration (JSON)
            // Format: {"show_for_countries": ["IN", "US"], "hide_for_countries": []}
            $table->json('call_icons_config')->nullable();

            // Enforcement/restriction tracking
            $table->boolean('is_restricted')->default(false);
            $table->string('restriction_reason')->nullable();
            $table->timestamp('restricted_at')->nullable();

            $table->timestamps();

            // Indexes
            $table->index('team_id');
            $table->index(['calling_enabled', 'is_restricted']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('call_settings');
    }
};
