<?php

namespace App\Http\Controllers\Backup;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Integration;
use App\Models\TenantBackup;
use App\Services\BackupService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\URL;
use Exception;

class BackupController extends Controller
{
    protected $backupService;

    public function __construct(BackupService $backupService)
    {
        $this->backupService = $backupService;
    }

    /**
     * Display the backup dashboard.
     * Restricted to Admins only (manage-settings permission).
     */
    public function index(Request $request)
    {
        // Enforce: only team admins may view backup section
        Gate::authorize('manage-settings');

        $team = auth()->user()->currentTeam;
        $hasAccess = $team ? $team->hasFeature('backups') : false;
        $hasCloudAccess = $team ? $team->hasFeature('cloud_backups') : false;

        if (!$team) {
            return redirect()->route('teams.create')->with('flash.banner', 'Please create a team to access backups.');
        }

        $backups = TenantBackup::where('team_id', $team->id)
            ->latest()
            ->paginate(10);

        $googleDrive = Integration::where('team_id', $team->id)
            ->where('type', 'google_drive')
            ->first();

        return view('backups.index', [
            'backups' => $backups,
            'googleDrive' => $googleDrive,
            'hasAccess' => $hasAccess,
            'hasCloudAccess' => $hasCloudAccess,
        ]);
    }

    /**
     * Trigger a manual backup.
     * Restricted to Admins only.
     */
    public function store(Request $request)
    {
        // Enforce: only team admins may trigger backups
        Gate::authorize('manage-settings');

        $team = auth()->user()->currentTeam;

        try {
            $this->backupService->backupTenant($team);

            $this->auditLog($team->id, 'backup.created', 'Manual backup triggered.');

            return redirect()
                ->back()
                ->with('flash.banner', 'Backup started successfully.')
                ->with('flash.bannerStyle', 'success');
        } catch (Exception $e) {
            return redirect()
                ->back()
                ->with('flash.banner', 'Failed to start backup: ' . $e->getMessage())
                ->with('flash.bannerStyle', 'danger');
        }
    }

    /**
     * Generate a temporary signed download URL for a backup file.
     *
     * Security checks enforced:
     *  1. User must have `manage-settings` (Admin role).
     *  2. Backup must belong to the user's current team (tenant isolation).
     *  3. Backup must be in `completed` status (no broken/partial files).
     *  4. Returns a signed URL that expires in 5 minutes (not a permanent link).
     *  5. Audit log entry is written for every download URL generated.
     *
     * No file content is served from this method — it only issues the signed URL.
     * The actual file stream is handled by SecureDownloadController::stream().
     */
    public function download($id)
    {
        // 1. Admin role check
        Gate::authorize('manage-settings');

        $user = auth()->user();
        $team = $user->currentTeam;

        // 2. Load and validate ownership — scoped to current team
        $backup = TenantBackup::where('id', $id)
            ->where('team_id', $team->id)
            ->firstOrFail(); // 404 if not found in this team; prevents ID enumeration

        // 3. ENFORCE MODEL B ARCHITECTURE:
        // "Tenants must NOT access raw backups. Only platform-level access allowed."
        if ($backup->isModelB() && !$user->isSuperAdmin()) {
            return redirect()
                ->back()
                ->with('flash.banner', 'This backup is platform-managed and cannot be downloaded directly. Please contact support for assistance.')
                ->with('flash.bannerStyle', 'danger');
        }

        // 4. Only allow download of completed or verified backups
        if (!in_array($backup->status, ['completed', 'verified', 'uploaded'])) {
            return redirect()
                ->back()
                ->with('flash.banner', 'Only completed backups can be downloaded.')
                ->with('flash.bannerStyle', 'danger');
        }

        // 4. Verify the file actually exists on disk before issuing a URL
        $path = "backups/{$backup->path}{$backup->filename}";
        if (!\Storage::disk('local')->exists($path)) {
            return redirect()
                ->back()
                ->with('flash.banner', 'Backup file not found on server.')
                ->with('flash.bannerStyle', 'danger');
        }

        // 5. Generate a temporary signed URL (expires in 5 minutes)
        //    This URL is single-use in spirit — it encodes the backup ID and
        //    team context, and is cryptographically signed by Laravel.
        $signedUrl = URL::temporarySignedRoute(
            'backups.download.stream',
            now()->addMinutes(5),
            ['id' => $backup->id, 'team' => $team->id]
        );

        // 6. Audit log: record WHO requested a download and WHEN
        $this->auditLog($team->id, 'backup.download_url_issued', "Download URL issued for backup: {$backup->filename}", [
            'backup_id' => $backup->id,
            'filename' => $backup->filename,
            'expires_at' => now()->addMinutes(5)->toIso8601String(),
        ]);

        // 7. Redirect to the signed stream URL — browser will follow and download
        return redirect($signedUrl);
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
            // Audit failures must never block the main operation, but must be logged.
            \Log::error("Backup audit log failed: " . $e->getMessage(), [
                'team_id' => $teamId,
                'action' => $action,
            ]);
        }
    }
}
