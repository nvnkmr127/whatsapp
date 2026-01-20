<?php

namespace App\Http\Controllers\Backup;

use App\Http\Controllers\Controller;
use App\Models\TenantBackup;
use App\Services\BackupService;
use Illuminate\Http\Request;
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
     */
    public function restore(Request $request, $id)
    {
        $backup = TenantBackup::findOrFail($id);
        $team = auth()->user()->currentTeam;

        if ($backup->team_id !== $team->id && !auth()->user()->isSuperAdmin()) {
            abort(403);
        }

        // Validate "RESTORE" confirmation if sent via request
        if ($request->confirmation !== 'RESTORE') {
            return redirect()->back()->with('error', 'Please type RESTORE to confirm.');
        }

        try {
            $this->backupService->restoreTenant($team, $backup);
            return redirect()->back()->with('success', 'Restoration successful! Your data has been restored.');
        } catch (Exception $e) {
            logger()->error("Restoration failed for Team {$team->id}: " . $e->getMessage());
            return redirect()->back()->with('error', 'Restoration failed: ' . $e->getMessage());
        }
    }

    /**
     * Handle manual file upload for restoration.
     */
    public function uploadAndRestore(Request $request)
    {
        $request->validate([
            'backup_file' => 'required|file|mimes:zip,enc',
        ]);

        $team = auth()->user()->currentTeam;
        $file = $request->file('backup_file');

        // Save to temp path for processing
        $tempPath = $file->storeAs('backup-temp', 'manual_restore_' . time() . '.' . $file->extension());
        $fullPath = storage_path('app/' . $tempPath);

        try {
            // Manual restore logic usually involves direct decryption/unzip
            // For now, we'll wrap it in a mock TenantBackup object or update 서비스 to handle direct files
            throw new Exception("Manual file restoration is not yet bridged to the service.");
        } catch (Exception $e) {
            return redirect()->back()->with('error', 'Manual restoration failed: ' . $e->getMessage());
        }
    }
}
