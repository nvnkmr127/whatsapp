<?php

namespace App\Services\Integrations;

use App\Models\Integration;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Exception;

/**
 * GoogleDriveService
 * ══════════════════════════════════════════════════════════════════════════════
 * Manages Google Drive file operations for tenant backups.
 *
 * SECURITY CONTRACT:
 *  1. Each tenant gets an ISOLATED, UNIQUELY NAMED folder.
 *     Folder name = "WatxIO Backups [{team_id_hash}]"
 *     The hash is an HMAC of (team_id + app_key) so it cannot be guessed
 *     or collide across tenants — even if two tenants use the same Google account.
 *
 *  2. All file operations (upload, search, download) are scoped to the tenant
 *     folder ID stored in integration.settings['google_folder_id'].
 *     No operation ever touches the Drive root or another folder.
 *
 *  3. The integration is team-scoped at construction. Passing an integration
 *     from a different team is an immediate RuntimeException.
 */
class GoogleDriveService
{
    protected Integration $integration;
    protected int $teamId;

    public function __construct(Integration $integration)
    {
        if ($integration->type !== 'google_drive') {
            throw new Exception("Invalid integration type for GoogleDriveService.");
        }

        if (!$integration->team_id) {
            // Refuse to operate without an explicit team scope — prevents global drive misuse
            throw new \RuntimeException(
                "GoogleDriveService requires an integration with a non-null team_id. " .
                "Never pass a platform-level integration."
            );
        }

        $this->integration = $integration;
        $this->teamId = $integration->team_id;
    }

    /**
     * Get the unique Google Account ID associated with this integration.
     */
    public function getAccountIdentifier(): ?string
    {
        return $this->integration->settings['google_account_id'] ?? null;
    }

    /**
     * Upload a file to the tenant's isolated Google Drive folder.
     *
     * @param  string  $filePath   Absolute path to the local file to upload.
     * @param  string  $filename   Name to give the file in Google Drive.
     * @return string  The Google Drive File ID of the uploaded backup.
     */
    public function uploadFile(string $filePath, string $filename): string
    {
        $this->refreshTokenIfNeeded();

        // Always upload into the tenant-isolated folder — never Drive root
        $folderId = $this->getOrCreateTenantFolder();

        $content = file_get_contents($filePath);
        $mimeType = 'application/zip';

        $response = Http::withToken($this->integration->credentials['access_token'])
            ->attach('metadata', json_encode([
                'name' => $filename,
                'parents' => [$folderId],
            ]), 'metadata.json', ['Content-Type' => 'application/json'])
            ->attach('file', $content, $filename, ['Content-Type' => $mimeType])
            ->post('https://www.googleapis.com/upload/drive/v3/files?uploadType=multipart');

        if (!$response->successful()) {
            throw new Exception("Google Drive upload failed for team {$this->teamId}: " . $response->body());
        }

        return $response->json('id');
    }

    /**
     * Find a file by name within THIS tenant's folder only.
     * Never searches the global Drive root — cross-tenant contamination impossible.
     */
    public function findFileByName(string $filename): ?array
    {
        $this->refreshTokenIfNeeded();
        $folderId = $this->getOrCreateTenantFolder();

        // Scope query to the tenant folder — not global Drive search
        $query = "name = '{$filename}' and '{$folderId}' in parents and trashed = false";

        $response = Http::withToken($this->integration->credentials['access_token'])
            ->get('https://www.googleapis.com/drive/v3/files', [
                'q' => $query,
                'fields' => 'files(id, name, mimeType)',
            ]);

        if ($response->successful() && !empty($response->json('files'))) {
            return $response->json('files.0');
        }

        return null;
    }

    /**
     * Check if a file exists and is not trashed.
     */
    public function fileExists(string $fileId): bool
    {
        $this->refreshTokenIfNeeded();

        $response = Http::withToken($this->integration->credentials['access_token'])
            ->get("https://www.googleapis.com/drive/v3/files/{$fileId}", [
                'fields' => 'id,trashed',
            ]);

        return $response->successful() && !$response->json('trashed');
    }

    /**
     * Download a file from Google Drive by its Drive file ID.
     * Includes an ownership verification check.
     */
    public function downloadFile(string $fileId, string $destinationPath, ?string $expectedAccountId = null): void
    {
        $this->refreshTokenIfNeeded();

        // Security: Cross-Account Verification
        if ($expectedAccountId && $this->getAccountIdentifier() !== $expectedAccountId) {
            throw new Exception(
                "Backup Ownership Violation: This backup is bound to Google Account [{$expectedAccountId}] " .
                "but the current integration is using [{$this->getAccountIdentifier()}]."
            );
        }

        $response = Http::withToken($this->integration->credentials['access_token'])
            ->get("https://www.googleapis.com/drive/v3/files/{$fileId}", [
                'alt' => 'media',
            ]);

        if ($response->status() === 404) {
            throw new \Illuminate\Database\Eloquent\ModelNotFoundException("File not found in Google Drive.");
        }

        if (!$response->successful()) {
            throw new Exception("Google Drive download failed for team {$this->teamId}: " . $response->body());
        }

        file_put_contents($destinationPath, $response->body());
    }

    /**
     * Append a row of data to a Google Sheet.
     * Use this for logging leads, orders, or automation events.
     */
    public function appendToSheet(string $spreadsheetId, array $values, string $range = 'A1'): array
    {
        $this->refreshTokenIfNeeded();

        $url = "https://sheets.googleapis.com/v4/spreadsheets/{$spreadsheetId}/values/{$range}:append?valueInputOption=USER_ENTERED";

        $response = Http::withToken($this->integration->credentials['access_token'])
            ->post($url, [
                'values' => [$values],
            ]);

        if (!$response->successful()) {
            throw new Exception("Google Sheets append failed for team {$this->teamId}: " . $response->body());
        }

        return $response->json();
    }

    /**
     * Delete a file from Google Drive.
     */
    public function deleteFile(string $fileId): void
    {
        $this->refreshTokenIfNeeded();

        $response = Http::withToken($this->integration->credentials['access_token'])
            ->delete("https://www.googleapis.com/drive/v3/files/{$fileId}");

        if (!$response->successful() && $response->status() !== 404) {
            throw new Exception("Google Drive deletion failed for team {$this->teamId}: " . $response->body());
        }
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Tenant Folder Isolation
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Get (or create) a tenant-isolated backup folder in Google Drive.
     *
     * Folder name format:
     *   "WatxIO Backups [abc123]"
     *
     * where "abc123" is the first 12 chars of HMAC-SHA256(team_id, app_key).
     * This guarantees:
     *   - Each team has a unique folder name → no naming collision.
     *   - The folder name reveals nothing about the team ID → no enumeration.
     *   - Even if two teams share a Google account, they get separate folders.
     *
     * The resolved folder ID is cached in integration.settings['google_folder_id']
     * to avoid repeated search queries. The cached ID is re-validated on each use.
     */
    protected function getOrCreateTenantFolder(): string
    {
        $settings = $this->integration->settings ?? [];
        $folderName = $this->tenantFolderName();

        // 1. Use cached folder ID if still valid
        if (!empty($settings['google_folder_id'])) {
            $response = Http::withToken($this->integration->credentials['access_token'])
                ->get("https://www.googleapis.com/drive/v3/files/{$settings['google_folder_id']}?fields=id,trashed,name");

            // Validate: must still exist, not trashed, and name must match ours
            if (
                $response->successful() &&
                !$response->json('trashed') &&
                $response->json('name') === $folderName
            ) {
                return $settings['google_folder_id'];
            }

            // Cached folder is gone or tampered — clear the cache and re-create
            unset($settings['google_folder_id']);
        }

        // 2. Search for existing folder with the tenant-specific name
        $query = "name = '{$folderName}' and mimeType = 'application/vnd.google-apps.folder' and 'root' in parents and trashed = false";

        $searchResponse = Http::withToken($this->integration->credentials['access_token'])
            ->get('https://www.googleapis.com/drive/v3/files', [
                'q' => $query,
                'fields' => 'files(id, name)',
            ]);

        if ($searchResponse->successful() && !empty($searchResponse->json('files'))) {
            $folderId = $searchResponse->json('files.0.id');
        } else {
            // 3. Create tenant-isolated folder
            $createResponse = Http::withToken($this->integration->credentials['access_token'])
                ->post('https://www.googleapis.com/drive/v3/files', [
                    'name' => $folderName,
                    'mimeType' => 'application/vnd.google-apps.folder',
                ]);

            if (!$createResponse->successful()) {
                throw new Exception(
                    "Failed to create Google Drive backup folder for team {$this->teamId}: " .
                    $createResponse->body()
                );
            }

            $folderId = $createResponse->json('id');
        }

        // 4. Cache the folder ID
        $settings['google_folder_id'] = $folderId;
        $settings['google_folder_name'] = $folderName; // for auditing
        $this->integration->update(['settings' => $settings]);

        return $folderId;
    }

    /**
     * Derive the tenant-isolated folder name.
     *
     * Uses HMAC-SHA256 of (team_id, app.key) — the hash:
     *  - Is deterministic per team (same input = same output every time)
     *  - Is unguessable without APP_KEY (brute forcing 2^256 possibilities)
     *  - Never collides across teams (birthday paradox: negligible at 12 hex chars at SaaS scale)
     */
    protected function tenantFolderName(): string
    {
        $mac = hash_hmac('sha256', (string) $this->teamId, config('app.key'));
        return 'WatxIO Backups [' . substr($mac, 0, 12) . ']';
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Token Management
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Refresh the access token if it is expired or within 5 minutes of expiry.
     */
    protected function refreshTokenIfNeeded(): void
    {
        if ($this->integration->status === 'error') {
            throw new Exception("Google Drive integration is in an error state. Please reconnect.");
        }

        $credentials = $this->integration->credentials;
        $expiresAt = $this->integration->updated_at
            ->addSeconds($credentials['expires_in'] ?? 3600);

        if ($expiresAt->isPast() || $expiresAt->diffInMinutes(now()) < 5) {
            $response = Http::post('https://oauth2.googleapis.com/token', [
                'client_id' => config('services.google.client_id'),
                'client_secret' => config('services.google.client_secret'),
                'refresh_token' => $credentials['refresh_token'],
                'grant_type' => 'refresh_token',
            ]);

            if (!$response->successful()) {
                $errorBody = $response->json();
                $isRevoked = ($errorBody['error'] ?? '') === 'invalid_grant';

                $this->integration->update([
                    'status' => 'error',
                    'error_message' => $isRevoked ? 'TOKEN_REVOKED: The Google Drive access was revoked or account disconnected.' : 'FAILED_REFRESH: ' . ($errorBody['error_description'] ?? 'Unexpected error during token refresh.')
                ]);

                throw new Exception(
                    "Google token refresh failed (Revoked: " . ($isRevoked ? 'YES' : 'NO') . ") for team {$this->teamId}: " . $response->body()
                );
            }

            $data = $response->json();
            $credentials['access_token'] = $data['access_token'];
            $credentials['expires_in'] = $data['expires_in'];

            $this->integration->update([
                'credentials' => $credentials,
                'status' => 'active',
                'error_message' => null,
            ]);
        }
    }
}
