<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Adds missing performance indexes on high-traffic tables and soft-delete
 * columns to messages, contacts, campaigns, and automations so cascading
 * hard-deletes cannot orphan related data.
 */
return new class extends Migration
{
    public function up(): void
    {
        // ── messages ────────────────────────────────────────────────────────
        Schema::table('messages', function (Blueprint $table) {
            $table->softDeletes();

            if (!$this->hasIndex('messages', 'messages_status_index')) {
                $table->index('status');
            }
            if (!$this->hasIndex('messages', 'messages_direction_index')) {
                $table->index('direction');
            }
            if (!$this->hasIndex('messages', 'messages_team_id_status_index')) {
                $table->index(['team_id', 'status']);
            }
            if (!$this->hasIndex('messages', 'messages_contact_id_created_at_index')) {
                $table->index(['contact_id', 'created_at']);
            }
            if (!$this->hasIndex('messages', 'messages_campaign_id_index')) {
                $table->index('campaign_id');
            }
            if (!$this->hasIndex('messages', 'messages_conversation_id_index')) {
                $table->index('conversation_id');
            }
        });

        // ── contacts ────────────────────────────────────────────────────────
        Schema::table('contacts', function (Blueprint $table) {
            $table->softDeletes();

            if (!$this->hasIndex('contacts', 'contacts_email_index')) {
                $table->index('email');
            }
            if (!$this->hasIndex('contacts', 'contacts_opt_in_status_index')) {
                $table->index('opt_in_status');
            }
            if (!$this->hasIndex('contacts', 'contacts_team_id_opt_in_status_index')) {
                $table->index(['team_id', 'opt_in_status']);
            }
            if (!$this->hasIndex('contacts', 'contacts_assigned_to_index')) {
                $table->index('assigned_to');
            }
            if (!$this->hasIndex('contacts', 'contacts_last_interaction_at_index')) {
                $table->index('last_interaction_at');
            }
        });

        // ── campaigns ───────────────────────────────────────────────────────
        Schema::table('campaigns', function (Blueprint $table) {
            $table->softDeletes();

            if (!$this->hasIndex('campaigns', 'campaigns_status_index')) {
                $table->index('status');
            }
            if (!$this->hasIndex('campaigns', 'campaigns_team_id_status_index')) {
                $table->index(['team_id', 'status']);
            }
            if (!$this->hasIndex('campaigns', 'campaigns_scheduled_at_index')) {
                $table->index('scheduled_at');
            }
        });

        // ── automations ─────────────────────────────────────────────────────
        Schema::table('automations', function (Blueprint $table) {
            $table->softDeletes();
        });

        // ── automation_runs ─────────────────────────────────────────────────
        Schema::table('automation_runs', function (Blueprint $table) {
            if (!$this->hasIndex('automation_runs', 'automation_runs_automation_id_status_index')) {
                $table->index(['automation_id', 'status']);
            }
            if (!$this->hasIndex('automation_runs', 'automation_runs_contact_id_automation_id_index')) {
                $table->index(['contact_id', 'automation_id']);
            }
        });

        // ── whatsapp_templates ──────────────────────────────────────────────
        Schema::table('whatsapp_templates', function (Blueprint $table) {
            if (!$this->hasIndex('whatsapp_templates', 'whatsapp_templates_status_index')) {
                $table->index('status');
            }
            if (!$this->hasIndex('whatsapp_templates', 'whatsapp_templates_team_id_status_index')) {
                $table->index(['team_id', 'status']);
            }
        });
    }

    public function down(): void
    {
        Schema::table('messages', function (Blueprint $table) {
            $table->dropSoftDeletes();
            $table->dropIndex('messages_status_index');
            $table->dropIndex('messages_direction_index');
            $table->dropIndex('messages_team_id_status_index');
            $table->dropIndex('messages_contact_id_created_at_index');
            $table->dropIndex('messages_campaign_id_index');
            $table->dropIndex('messages_conversation_id_index');
        });

        Schema::table('contacts', function (Blueprint $table) {
            $table->dropSoftDeletes();
            $table->dropIndex('contacts_email_index');
            $table->dropIndex('contacts_opt_in_status_index');
            $table->dropIndex('contacts_team_id_opt_in_status_index');
            $table->dropIndex('contacts_assigned_to_index');
            $table->dropIndex('contacts_last_interaction_at_index');
        });

        Schema::table('campaigns', function (Blueprint $table) {
            $table->dropSoftDeletes();
            $table->dropIndex('campaigns_status_index');
            $table->dropIndex('campaigns_team_id_status_index');
            $table->dropIndex('campaigns_scheduled_at_index');
        });

        Schema::table('automations', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });

        Schema::table('automation_runs', function (Blueprint $table) {
            $table->dropIndex('automation_runs_automation_id_status_index');
            $table->dropIndex('automation_runs_contact_id_automation_id_index');
        });

        Schema::table('whatsapp_templates', function (Blueprint $table) {
            $table->dropIndex('whatsapp_templates_status_index');
            $table->dropIndex('whatsapp_templates_team_id_status_index');
        });
    }

    /**
     * Check if an index already exists (avoids duplicate-index errors on re-run).
     */
    private function hasIndex(string $table, string $index): bool
    {
        $indexes = \Illuminate\Support\Facades\DB::select(
            "SHOW INDEX FROM `{$table}` WHERE Key_name = ?",
            [$index]
        );

        return !empty($indexes);
    }
};
