<?php

namespace App\Http\Controllers\Backup;

use App\Http\Controllers\Controller;
use App\Models\Integration;
use App\Models\TenantBackup;
use App\Services\BackupService;
use Illuminate\Http\Request;
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
     */
    public function index(Request $request)
    {
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
     */
    public function store(Request $request)
    {
        $team = auth()->user()->currentTeam;

        try {
            $this->backupService->backupTenant($team);
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
     * Download a backup file.
     */
    public function download($id)
    {
        $backup = TenantBackup::findOrFail($id);
        $team = auth()->user()->currentTeam;

        if ($backup->team_id !== $team->id && !auth()->user()->isSuperAdmin()) {
            abort(403);
        }

        $path = "backups/{$backup->path}{$backup->filename}";

        if (!\Storage::disk('local')->exists($path)) {
            return redirect()
                ->back()
                ->with('flash.banner', 'Backup file not found locally.')
                ->with('flash.bannerStyle', 'danger');
        }

        return response()->download(\Storage::disk('local')->path($path));
    }
}
