<?php

namespace Tests\Feature\Backup;

use App\Models\Team;
use App\Models\TenantBackup;
use App\Models\User;
use App\Services\BackupService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;
use ZipArchive;

class RestoreTest extends TestCase
{
    use RefreshDatabase;

    protected $backupService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->backupService = new BackupService();

        // Setup default plans for feature gating
        \App\Models\Plan::create([
            'name' => 'basic',
            'monthly_price' => 0,
            'message_limit' => 100,
            'agent_limit' => 1,
            'features' => ['backups' => true, 'cloud_backups' => false]
        ]);
    }

    public function test_tenant_restore_atomic_success()
    {
        $team = Team::factory()->create();
        $user = User::factory()->create(['current_team_id' => $team->id]);
        $this->actingAs($user);

        // 1. Create a dummy backup file
        $sqlContent = "INSERT INTO integrations (team_id, name, type, status, created_at, updated_at) VALUES ({$team->id}, 'Restored Integration', 'google_drive', 'active', CURRENT_TIMESTAMP, CURRENT_TIMESTAMP);";
        $zipPath = storage_path('app/backup-temp/test_restore.zip');
        $zip = new ZipArchive();
        $zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE);
        $zip->addFromString('database.sql', $sqlContent);
        $zip->close();

        // 2. Encrypt it
        $key = config('app.key');
        if (str_starts_with($key, 'base64:'))
            $key = base64_decode(substr($key, 7));
        $iv = openssl_random_pseudo_bytes(openssl_cipher_iv_length('aes-256-cbc'));
        $encryptedData = openssl_encrypt(file_get_contents($zipPath), 'aes-256-cbc', $key, 0, $iv);
        file_put_contents($zipPath . '.enc', $iv . $encryptedData);

        $backup = TenantBackup::create([
            'team_id' => $team->id,
            'filename' => 'test_restore.zip.enc',
            'path' => 'tenants/' . $team->id . '/',
            'status' => 'completed',
            'checksum' => hash_file('sha256', $zipPath . '.enc'),
            'type' => 'tenant'
        ]);

        Storage::disk('local')->put('backups/tenants/' . $team->id . '/test_restore.zip.enc', file_get_contents($zipPath . '.enc'));

        // Pre-fill with an old integration that should be deleted
        DB::table('integrations')->insert([
            'team_id' => $team->id,
            'name' => 'Old Integration',
            'type' => 'shopify',
            'status' => 'active',
            'created_at' => now(),
            'updated_at' => now()
        ]);

        // 3. Perform Restore
        $response = $this->post(route('backups.restore', $backup->id), [
            'confirmation' => 'RESTORE'
        ]);

        $response->assertSessionHasNoErrors();
        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('integrations', [
            'team_id' => $team->id,
            'name' => 'Restored Integration'
        ]);

        $this->assertDatabaseMissing('integrations', [
            'name' => 'Old Integration'
        ]);

        if (file_exists($zipPath))
            unlink($zipPath);
        if (file_exists($zipPath . '.enc'))
            unlink($zipPath . '.enc');
    }

    public function test_restore_fails_on_wrong_confirmation()
    {
        $team = Team::factory()->create();
        $user = User::factory()->create(['current_team_id' => $team->id]);
        $backup = TenantBackup::create(['team_id' => $team->id, 'filename' => 'f.enc', 'path' => 'p/', 'type' => 'tenant', 'status' => 'completed', 'checksum' => 'c', 'name' => 'n']);

        $response = $this->actingAs($user)->post(route('backups.restore', $backup->id), [
            'confirmation' => 'WRONG'
        ]);

        $response->assertSessionHas('error', 'Please type RESTORE to confirm.');
    }
}
