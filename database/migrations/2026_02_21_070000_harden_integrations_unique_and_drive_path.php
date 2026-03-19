<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Harden Integrations Table
 * ══════════════════════════════════════════════════════════════════════════════
 * CVE-5: Adds UNIQUE(team_id, type) so a second google_drive credential can
 * never be silently inserted for the same tenant.
 *
 * Without this constraint an attacker who triggers the OAuth flow twice ends up
 * with two active rows. BackupService uses ->first() — whichever row wins is
 * arbitrary. The losing credential is silently orphaned but retains a live
 * refresh token, meaning it can be used to access the team's Drive indefinitely.
 *
 * Deduplication strategy:
 *   Keep the most recently updated row per (team_id, type); delete all older ones.
 */
return new class extends Migration {
    public function up(): void
    {
        // ── 1. Deduplicate existing rows before adding the constraint ──────────
        // Find the newest row per (team_id, type) and delete all older siblings.
        if (DB::getDriverName() === 'sqlite') {
            $duplicates = DB::table('integrations')
                ->select('team_id', 'type')
                ->groupBy('team_id', 'type')
                ->havingRaw('COUNT(*) > 1')
                ->get();

            foreach ($duplicates as $dup) {
                $latestId = DB::table('integrations')
                    ->where('team_id', $dup->team_id)
                    ->where('type', $dup->type)
                    ->latest('updated_at')
                    ->value('id');

                DB::table('integrations')
                    ->where('team_id', $dup->team_id)
                    ->where('type', $dup->type)
                    ->where('id', '!=', $latestId)
                    ->delete();
            }
        } else {
            DB::statement("
                DELETE i FROM integrations i
                INNER JOIN (
                    SELECT team_id, type, MAX(updated_at) AS max_updated
                    FROM   integrations
                    GROUP  BY team_id, type
                    HAVING COUNT(*) > 1
                ) keeper
                  ON  i.team_id = keeper.team_id
                  AND i.type    = keeper.type
                  AND i.updated_at < keeper.max_updated
            ");
        }

        // ── 2. Add the UNIQUE constraint ──────────────────────────────────────
        Schema::table('integrations', function (Blueprint $table) {
            // Guard against re-running (e.g. in tests with RefreshDatabase)
            if (!$this->uniqueExists()) {
                $table->unique(['team_id', 'type'], 'integrations_team_id_type_unique');
            }
        });
    }

    public function down(): void
    {
        Schema::table('integrations', function (Blueprint $table) {
            $table->dropUnique('integrations_team_id_type_unique');
        });
    }

    /**
     * Check whether the unique index already exists (idempotent re-runs).
     */
    private function uniqueExists(): bool
    {
        try {
            $indexes = DB::select("SHOW INDEX FROM integrations WHERE Key_name = 'integrations_team_id_type_unique'");
            return count($indexes) > 0;
        } catch (\Throwable) {
            return false; // SQLite in tests — no SHOW INDEX
        }
    }
};
