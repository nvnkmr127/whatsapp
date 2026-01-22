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
        Schema::table('contacts', function (Blueprint $table) {
            // Single column indexes for filtering
            $table->index('opt_in_status', 'idx_contacts_opt_in_status');
            $table->index('last_interaction_at', 'idx_contacts_last_interaction');
            $table->index('assigned_to', 'idx_contacts_assigned_to');

            // Composite indexes for common query patterns
            $table->index(['team_id', 'opt_in_status'], 'idx_contacts_team_opt_in');
            $table->index(['team_id', 'last_interaction_at'], 'idx_contacts_team_interaction');
            $table->index(['team_id', 'assigned_to'], 'idx_contacts_team_assigned');
        });

        Schema::table('consent_logs', function (Blueprint $table) {
            // Composite index for compliance reporting
            $table->index(['team_id', 'action'], 'idx_consent_logs_team_action');

            // Index for contact history lookups
            $table->index('contact_id', 'idx_consent_logs_contact');

            // Index for date-based queries
            $table->index('created_at', 'idx_consent_logs_created');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('contacts', function (Blueprint $table) {
            $table->dropIndex('idx_contacts_opt_in_status');
            $table->dropIndex('idx_contacts_last_interaction');
            $table->dropIndex('idx_contacts_assigned_to');
            $table->dropIndex('idx_contacts_team_opt_in');
            $table->dropIndex('idx_contacts_team_interaction');
            $table->dropIndex('idx_contacts_team_assigned');
        });

        Schema::table('consent_logs', function (Blueprint $table) {
            $table->dropIndex('idx_consent_logs_team_action');
            $table->dropIndex('idx_consent_logs_contact');
            $table->dropIndex('idx_consent_logs_created');
        });
    }
};
