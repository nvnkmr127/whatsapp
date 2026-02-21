<?php

namespace App\Http\Controllers\Backup;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\TenantBackup;
use App\Services\BackupService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Exception;

class RestoreController extends Controller
{
    protected $backupService;

    public function __construct(BackupService $backupService)
    {
        $this->backupService = $backupService;
    }

    /**
     * Trigger a restoration for a tenant.
     *
     * Security checks enforced:
     *  1. User must have `manage-settings` (Admin role only).
     *  2. Backup must belong to current team (tenant isolation).
     *  3. RESTORE confirmation word must be typed explicitly.
     *  4. Audit log entry written before AND after restore attempt.
     */
    public function restore(Request $request, $id)
    {
        // 1. Enforce: only team admins may trigger a restore
        Gate::authorize('manage-settings');

        $user = auth()->user();
        $team = $user->currentTeam;

        // 2. Team-scoped lookup — prevents cross-tenant restore attempts
        //    404 is returned (not 403) to avoid ID enumeration
        $backup = TenantBackup::where('id', $id)
            ->where('team_id', $team->id)
            ->firstOrFail();

        // 3. ENFORCE MODEL B ARCHITECTURE:
        // "Tenants must NOT access raw backups. Only platform-level access allowed."
        if ($backup->isModelB() && !$user->isSuperAdmin()) {
            return redirect()->back()->with('error', 'This backup is platform-managed. Restoration must be initiated by a system administrator.');
        }

        // 4. Must type "RESTORE" to confirm — prevents accidental triggers
        if ($request->confirmation !== 'RESTORE') {
            return redirect()->back()->with('error', 'Please type RESTORE to confirm.');
        }

        // 5. Only restore from completed or verified backups
        if (!in_array($backup->status, ['completed', 'verified', 'uploaded'])) {
            return redirect()->back()->with('error', 'Only completed/verified backups can be restored.');
        }

        // 5. Audit log: record that a restore was INITIATED before we begin
        $this->auditLog(
            $team->id,
            'backup.restore_initiated',
            "Tenant restore initiated from backup: {$backup->filename}",
            [
                'backup_id' => $backup->id,
                'filename' => $backup->filename,
                'created_at' => $backup->created_at?->toIso8601String(),
            ]
        );

        try {
            $this->backupService->restoreTenant($team, $backup);

            // 6. Audit log: success
            $this->auditLog(
                $team->id,
                'backup.restore_completed',
                "Tenant restore completed from backup: {$backup->filename}",
                [
                    'backup_id' => $backup->id,
                ]
            );

            return redirect()->back()->with('success', 'Restoration successful! Your data has been restored.');
        } catch (Exception $e) {
            // 7. Audit log: failure
            $this->auditLog(
                $team->id,
                'backup.restore_failed',
                "Tenant restore FAILED from backup: {$backup->filename}",
                [
                    'backup_id' => $backup->id,
                    'error' => $e->getMessage(),
                ]
            );

            logger()->error("Restoration failed for Team {$team->id}: " . $e->getMessage());
            return redirect()->back()->with('error', 'Restoration failed: ' . $e->getMessage());
        }
    }

    /**
     * Handle manual file upload for restoration.
     * Restricted to Admins only.
     */
    public function uploadAndRestore(Request $request)
    {
        // Enforce: only team admins may upload restore files
        Gate::authorize('manage-settings');

        $request->validate([
            'backup_file' => 'required|file|mimes:zip,enc',
        ]);

        $team = auth()->user()->currentTeam;
        $file = $request->file('backup_file');

        $tempPath = $file->storeAs('backup-temp', 'manual_restore_' . time() . '.' . $file->extension());
        $fullPath = storage_path('app/' . $tempPath);

        $this->auditLog(
            $team->id,
            'backup.manual_restore_attempted',
            "Manual file restore attempted: {$file->getClientOriginalName()}",
            [
                'original_name' => $file->getClientOriginalName(),
                'size' => $file->getSize(),
            ]
        );

        try {
            $this->backupService->restoreManual($team, $fullPath);

            $this->auditLog(
                $team->id,
                'backup.manual_restore_completed',
                "Manual file restore completed: {$file->getClientOriginalName()}",
                [
                    'size' => $file->getSize(),
                ]
            );

            return redirect()->back()->with('success', 'Manual restoration successful!');
        } catch (Exception $e) {
            $this->auditLog(
                $team->id,
                'backup.manual_restore_failed',
                "Manual file restore FAILED: {$file->getClientOriginalName()}",
                [
                    'error' => $e->getMessage(),
                ]
            );
            return redirect()->back()->with('error', 'Manual restoration failed: ' . $e->getMessage());
        }
    }

    // ─────────────────────────────────────────────────────────────────────
    // Internal Helpers
    // ─────────────────────────────────────────────────────────────────────

    private function auditLog(int $teamId, string $action, string $description, array $properties = []): void
    {
        try {
            ActivityLog::create([
                'team_id' => $teamId,
                'user_id' => auth()->id(),
                'action' => $action,
                'description' => $description,
                'ip_address' => request()->ip(),
                'properties' => array_merge($properties, [
                    'user_agent' => request()->userAgent(),
                ]),
            ]);
        } catch (Exception $e) {
            \Log::error("Restore audit log failed: " . $e->getMessage());
        }
    }
}
