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
        Schema::create('whatsapp_setup_audit', function (Blueprint $table) {
            $table->id();
            $table->foreignId('team_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->nullable()->constrained()->onDelete('set null');
            $table->string('action'); // connected, disconnected, token_refreshed, phone_changed, sync_info
            $table->string('status'); // success, failed, partial, in_progress
            $table->json('changes')->nullable(); // What changed
            $table->json('metadata')->nullable(); // Error details, API responses
            $table->ipAddress('ip_address')->nullable();
            $table->string('reference_id')->nullable(); // For support tracking
            $table->timestamps();

            $table->index(['team_id', 'created_at']);
            $table->index('reference_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('whatsapp_setup_audit');
    }
};
