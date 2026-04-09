<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * identity_fingerprints
 * ─────────────────────
 * Stores normalised/hashed identity signals captured at registration time.
 * Used by UniqueIdentityService to detect duplicate accounts before a trial
 * is assigned.
 *
 * Types tracked here (WABA ID / Phone Number ID / Email are stored directly
 * on teams/users, so we only need this table for the remaining three):
 *
 *   email_domain   – normalised apex domain from the registrant email
 *   signup_ip      – hashed IPv4/IPv6 address (HMAC-SHA256, not reversible)
 *   payment_method – opaque fingerprint provided by the payment gateway
 *                    (e.g. Stripe card fingerprint)
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('identity_fingerprints', function (Blueprint $table) {
            $table->id();

            // One of: email_domain | signup_ip | payment_method
            $table->string('type', 32);

            // Hashed/normalised value – safe to index, not reversible for IPs
            $table->string('fingerprint', 64);

            // Association – at least one must be set
            $table->foreignId('team_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();

            // Super Admin can hard-block a fingerprint (e.g. known fraudster domain)
            $table->boolean('is_blocked')->default(false);
            $table->text('blocked_reason')->nullable();
            $table->foreignId('blocked_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('blocked_at')->nullable();

            $table->timestamps();

            // Fast lookup by type + fingerprint (the critical query path)
            $table->index(['type', 'fingerprint'], 'idx_type_fingerprint');

            // Prevent exact duplicates for the same team
            $table->unique(['type', 'fingerprint', 'team_id'], 'uniq_type_fp_team');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('identity_fingerprints');
    }
};
