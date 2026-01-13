<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // 1. Add Consent Columns to Contacts
        Schema::table('contacts', function (Blueprint $table) {
            $table->enum('opt_in_status', ['none', 'opted_in', 'opted_out'])->default('none')->after('email');
            $table->string('opt_in_source')->nullable()->after('opt_in_status'); // e.g., 'QR', 'WEB', 'API'
            $table->timestamp('opt_in_at')->nullable()->after('opt_in_source');
            $table->timestamp('last_interaction_at')->nullable(); // For 24h window tracking
        });

        // 2. Create Consent Audit Log (Compliance Requirement)
        Schema::create('consent_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('team_id')->constrained()->cascadeOnDelete();
            $table->foreignId('contact_id')->constrained()->cascadeOnDelete();

            $table->string('action'); // 'OPT_IN', 'OPT_OUT'
            $table->string('source')->nullable(); // 'STOP_KEYWORD', 'MANUAL_AGENT', 'API'
            $table->text('notes')->nullable(); // "User replied STOP"
            $table->ipAddress('ip_address')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('consent_logs');

        Schema::table('contacts', function (Blueprint $table) {
            $table->dropColumn(['opt_in_status', 'opt_in_source', 'opt_in_at', 'last_interaction_at']);
        });
    }
};
