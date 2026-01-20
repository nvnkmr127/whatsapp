<?php

namespace Tests\Feature\Integrations;

use App\Models\Integration;
use App\Models\Team;
use App\Services\BackupService;
use App\Services\Integrations\GoogleDriveService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class GoogleDriveBackupTest extends TestCase
{
    use RefreshDatabase;

    public function test_google_drive_upload_flow()
    {
        $team = Team::factory()->create();
        $integration = Integration::create([
            'team_id' => $team->id,
            'type' => 'google_drive',
            'name' => 'Test Drive',
            'credentials' => [
                'access_token' => 'mock_access_token',
                'refresh_token' => 'mock_refresh_token',
                'expires_in' => 3600,
            ],
            'status' => 'active',
        ]);

        config(['services.google.client_id' => 'test_client_id']);
        config(['services.google.client_secret' => 'test_client_secret']);

        Http::fake([
            // Mock token refresh just in case
            'https://oauth2.googleapis.com/token' => Http::response([
                'access_token' => 'mock_access_token',
                'expires_in' => 3600
            ]),
            // Mock folder check (exists)
            'https://www.googleapis.com/drive/v3/files?q=*' => Http::response([
                'files' => [['id' => 'mock_folder_id']]
            ]),
            // Mock file upload
            'https://www.googleapis.com/upload/drive/v3/files?uploadType=multipart' => Http::response([
                'id' => 'mock_file_id'
            ]),
        ]);

        Storage::fake('local');
        Storage::disk('local')->put('test-backup.zip', 'content');
        $filePath = Storage::disk('local')->path('test-backup.zip');

        $service = new GoogleDriveService($integration);
        $result = $service->uploadFile($filePath, 'backup.zip');

        $this->assertEquals('mock_file_id', $result['id']);

        Http::assertSent(function ($request) {
            return str_contains($request->url(), 'upload/drive/v3/files') &&
                $request->isMultipart();
        });
    }

    public function test_token_refresh_handled()
    {
        $team = Team::factory()->create();

        // Integration that is "expired" because updated_at is old
        $integration = Integration::create([
            'team_id' => $team->id,
            'type' => 'google_drive',
            'name' => 'Refresh Test',
            'credentials' => [
                'access_token' => 'old_token',
                'refresh_token' => 'mock_refresh_token',
                'expires_in' => 3600,
            ],
            'status' => 'active',
        ]);

        config(['services.google.client_id' => 'test_id']);
        config(['services.google.client_secret' => 'test_secret']);

        // Move updated_at back
        $integration->updated_at = now()->subHours(2);
        $integration->save();

        Http::fake([
            // Mock token refresh
            'https://oauth2.googleapis.com/token' => Http::response([
                'access_token' => 'new_token',
                'expires_in' => 3600
            ]),
            // Mock folder check
            'https://www.googleapis.com/drive/v3/files?q=*' => Http::response(['files' => [['id' => 'fid']]]),
            // Mock upload
            'https://www.googleapis.com/upload/drive/v3/files?uploadType=multipart' => Http::response(['id' => 'new_file']),
        ]);

        Storage::fake('local');
        $filePath = Storage::disk('local')->path('test.zip');
        file_put_contents($filePath, 'data');

        $service = new GoogleDriveService($integration);
        $service->uploadFile($filePath, 'test.zip');

        $this->assertEquals('new_token', $integration->fresh()->credentials['access_token']);
    }
}
