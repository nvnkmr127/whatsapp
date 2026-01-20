<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\Team;
use App\Models\User;
use App\Models\TenantBackup;
use App\Services\BackupService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;

class BackupTest extends TestCase
{
    use RefreshDatabase;

    protected $backupService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->backupService = new BackupService();
        Storage::fake('local');
        Storage::fake('public');
    }

    public function test_tenant_backup_creates_record_and_encrypted_file()
    {
        $team = Team::factory()->create(['name' => 'Team Alpha']);

        $backupRecord = $this->backupService->backupTenant($team);

        // Verify record
        $this->assertDatabaseHas('tenant_backups', [
            'id' => $backupRecord->id,
            'team_id' => $team->id,
            'status' => 'completed',
        ]);

        // Verify file exists with .enc suffix
        Storage::disk('local')->assertExists("backups/tenants/{$team->id}/{$backupRecord->filename}");
        $this->assertStringEndsWith('.enc', $backupRecord->filename);
    }

    public function test_retention_policy_removes_old_records_and_files()
    {
        $id = \Illuminate\Support\Str::uuid();
        DB::table('tenant_backups')->insert([
            'id' => $id,
            'type' => 'global',
            'filename' => 'old_backup.zip.enc',
            'path' => 'global/',
            'status' => 'completed',
            'created_at' => now()->subDays(8),
            'updated_at' => now()->subDays(8),
        ]);

        Storage::disk('local')->put('backups/global/old_backup.zip.enc', 'content');

        $this->backupService->cleanOldBackups();

        Storage::disk('local')->assertMissing('backups/global/old_backup.zip.enc');
        $this->assertEquals('pruned', DB::table('tenant_backups')->where('id', $id)->first()->status);
    }
}
