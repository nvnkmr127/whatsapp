<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Consent Registry — Immutable, Append-Only, Time-Based Authoritative Table
 * ══════════════════════════════════════════════════════════════════════════════
 *
 * GDPR / WhatsApp Business Policy Compliance Requirement:
 *
 * This table is the SINGLE SOURCE OF TRUTH for consent decisions.
 * It is NEVER overwritten, rolled back, or cleared during restore operations.
 *
 * Rules:
 *   1. IMMUTABLE  — No UPDATE or DELETE operations are ever permitted on rows.
 *   2. APPEND-ONLY — Every consent change appends a new row; nothing is mutated.
 *   3. TIME-AUTHORITATIVE — The most-recent action row for a contact/team pair
 *      is always the authoritative consent state, regardless of what the contacts
 *      table says (the contacts table is a cache / denormalized view).
 *   4. RESTORE-SAFE — BackupService explicitly excludes this table from both
 *      clearTenantData() and getTenantSqlData(), so restores cannot overwrite it.
 *
 * If a contact replied STOP (OPT_OUT) *after* a backup was taken and you restore
 * from that backup, this registry still records their opt-out. The
 * BackupService::rehydrateConsentFromRegistry() method will re-apply the most
 * recent registry row to the contacts table after every restore.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('consent_registry', function (Blueprint $table) {
            $table->id();

            // Tenant scope
            $table->unsignedBigInteger('team_id')->index();
            $table->unsignedBigInteger('contact_id')->nullable()->index();

            // Phone number stored separately so the record survives contact deletion
            $table->string('phone_number', 30)->index();

            // The consent action — only OPT_IN and OPT_OUT are authoritative actions.
            // Attempt variants are for audit trail only.
            $table->enum('action', [
                'OPT_IN',
                'OPT_OUT',
                'OPT_IN_ATTEMPT',
                'OPT_OUT_ATTEMPT',
                'CONSENT_EXPIRED',
                'CONSENT_RENEWED',
            ]);

            // Who / what triggered the action
            $table->string('source')->nullable();        // 'STOP_KEYWORD', 'API', 'MANUAL_ADMIN'
            $table->string('channel')->nullable();       // 'whatsapp', 'sms', 'web'

            // Audit evidence
            $table->text('notes')->nullable();
            $table->string('proof_url')->nullable();     // Screenshot, form submission URL, etc.
            $table->ipAddress('ip_address')->nullable();
            $table->string('user_agent')->nullable();

            // Consent validity window (for OPT_IN rows only)
            $table->timestamp('expires_at')->nullable(); // Null = no expiry for opt-outs

            // Immutability guard — this column is set on insert and never changed.
            // The model's saving() hook enforces this.
            $table->timestamp('recorded_at')->useCurrent();

            // Standard timestamps — created_at is the authoritative decision time.
            $table->timestamps();

            // Foreign key references (soft — contact may be deleted but record must survive)
            $table->foreign('team_id')->references('id')->on('teams')->cascadeOnDelete();

            // Index for fast "latest consent state" lookups
            $table->index(['team_id', 'phone_number', 'created_at']);
            $table->index(['contact_id', 'action', 'created_at']);
        });
    }

    public function down(): void
    {
        // IMPORTANT: Dropping this table destroys compliance records.
        // This rollback should NEVER run in production.
        Schema::dropIfExists('consent_registry');
    }
};
