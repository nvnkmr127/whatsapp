<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // conversations: inbox list queries filter by assigned_to and sort by last_message_at
        Schema::table('conversations', function (Blueprint $table) {
            if (! $this->hasIndex('conversations', 'conversations_assigned_to_index')) {
                $table->index('assigned_to');
            }
            if (! $this->hasIndex('conversations', 'conversations_last_message_at_index')) {
                $table->index('last_message_at');
            }
            if (! $this->hasIndex('conversations', 'conversations_team_id_status_index')) {
                $table->index(['team_id', 'status']);
            }
        });

        // campaign_details: bulk send jobs query (campaign_id, status) on every send tick
        Schema::table('campaign_details', function (Blueprint $table) {
            if (! $this->hasIndex('campaign_details', 'campaign_details_campaign_id_status_index')) {
                $table->index(['campaign_id', 'status']);
            }
            if (! $this->hasIndex('campaign_details', 'campaign_details_contact_id_index')) {
                $table->index('contact_id');
            }
        });
    }

    public function down(): void
    {
        Schema::table('conversations', function (Blueprint $table) {
            $table->dropIndex('conversations_assigned_to_index');
            $table->dropIndex('conversations_last_message_at_index');
            $table->dropIndex('conversations_team_id_status_index');
        });

        Schema::table('campaign_details', function (Blueprint $table) {
            $table->dropIndex('campaign_details_campaign_id_status_index');
            $table->dropIndex('campaign_details_contact_id_index');
        });
    }

    private function hasIndex(string $table, string $index): bool
    {
        if (DB::getDriverName() === 'sqlite') {
            $indexes = DB::select("PRAGMA index_list(`{$table}`)");
            return collect($indexes)->contains('name', $index);
        }

        return ! empty(DB::select(
            "SHOW INDEX FROM `{$table}` WHERE Key_name = ?",
            [$index]
        ));
    }
};
