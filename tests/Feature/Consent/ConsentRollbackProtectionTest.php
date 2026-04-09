<?php

namespace Tests\Feature\Consent;

use App\Models\ConsentRegistry;
use App\Models\Contact;
use App\Models\Team;
use App\Models\TenantBackup;
use App\Models\User;
use App\Services\BackupService;
use App\Services\ConsentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Tests\TestCase;
use ZipArchive;

/**
 * ConsentRollbackProtectionTest
 * ══════════════════════════════════════════════════════════════════════════════
 * Verifies the GDPR-level protection against backup restore overwriting opt-outs.
 *
 * Scenario being tested:
 *   T+0  Contact is opted_in  → backup is taken.
 *   T+1  Contact replies STOP → ConsentService records OPT_OUT in consent_registry.
 *   T+2  Admin triggers restore from the T+0 backup.
 *        → Contacts table gets restored to opted_in (from backup SQL).
 *        → ConsentService::rehydrateTeamFromRegistry() re-applies OPT_OUT.
 *        → Final state: opted_out. No illegal messaging possible.
 *
 * NOTE: Contact::factory()->create() is intentionally avoided in most tests.
 * The ContactObserver fires updateDerivedFields() which uses TIMESTAMPDIFF(),
 * a MySQL-only function that crashes on the SQLite test driver. We bypass
 * the observer by using direct DB inserts via makeContact().
 */
class ConsentRollbackProtectionTest extends TestCase
{
    use RefreshDatabase;

    protected ConsentService $consentService;

    protected BackupService $backupService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->consentService = app(ConsentService::class);
        $this->backupService = app(BackupService::class);

        // Plan with backups enabled
        \App\Models\Plan::create([
            'name' => 'pro',
            'monthly_price' => 49,
            'message_limit' => 10000,
            'agent_limit' => 10,
            'features' => ['backups' => true, 'cloud_backups' => false],
        ]);
    }

    /**
     * Insert a contact directly via DB to bypass ContactObserver
     * (which runs MySQL-specific TIMESTAMPDIFF that fails on SQLite test driver).
     */
    private function makeContact(Team $team, array $attrs = []): Contact
    {
        $id = DB::table('contacts')->insertGetId(array_merge([
            'team_id' => $team->id,
            'phone_number' => '+155500'.rand(10000, 99999),
            'opt_in_status' => 'opted_in',
            'created_at' => now(),
            'updated_at' => now(),
        ], $attrs));

        return Contact::find($id);
    }

    // ──────────────────────────────────────────────────────────────────────────
    // Test 1: ConsentRegistry is immutable — UPDATE throws
    // ──────────────────────────────────────────────────────────────────────────

    public function test_consent_registry_row_cannot_be_updated(): void
    {
        $team = Team::factory()->create();
        $contact = $this->makeContact($team, ['opt_in_status' => 'opted_in']);

        $entry = ConsentRegistry::create([
            'team_id' => $team->id,
            'contact_id' => $contact->id,
            'phone_number' => $contact->phone_number,
            'action' => 'OPT_IN',
            'source' => 'TEST',
            'channel' => 'whatsapp',
            'recorded_at' => now(),
        ]);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessageMatches('/immutable/i');

        $entry->update(['action' => 'OPT_OUT']); // Must throw
    }

    // ──────────────────────────────────────────────────────────────────────────
    // Test 2: ConsentRegistry row cannot be deleted
    // ──────────────────────────────────────────────────────────────────────────

    public function test_consent_registry_row_cannot_be_deleted(): void
    {
        $team = Team::factory()->create();
        $contact = $this->makeContact($team);

        $entry = ConsentRegistry::create([
            'team_id' => $team->id,
            'contact_id' => $contact->id,
            'phone_number' => $contact->phone_number,
            'action' => 'OPT_OUT',
            'source' => 'STOP_KEYWORD',
            'channel' => 'whatsapp',
            'recorded_at' => now(),
        ]);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessageMatches('/cannot be deleted/i');

        $entry->delete(); // Must throw
    }

    // ──────────────────────────────────────────────────────────────────────────
    // Test 3: ConsentLog rows are also immutable
    // ──────────────────────────────────────────────────────────────────────────

    public function test_consent_log_row_cannot_be_updated(): void
    {
        $team = Team::factory()->create();
        $contact = $this->makeContact($team);

        $log = \App\Models\ConsentLog::create([
            'team_id' => $team->id,
            'contact_id' => $contact->id,
            'action' => 'OPT_IN',
            'source' => 'TEST',
        ]);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessageMatches('/append-only/i');

        $log->update(['action' => 'OPT_OUT']); // Must throw
    }

    // ──────────────────────────────────────────────────────────────────────────
    // Test 4: optOut() dual-writes to ConsentRegistry
    // ──────────────────────────────────────────────────────────────────────────

    public function test_opt_out_is_written_to_consent_registry(): void
    {
        $team = Team::factory()->create();
        $contact = $this->makeContact($team, ['opt_in_status' => 'opted_in']);

        $this->consentService->optOut($contact, 'STOP_KEYWORD', 'User replied STOP');

        // 1. Contact table is updated
        $this->assertDatabaseHas('contacts', [
            'id' => $contact->id,
            'opt_in_status' => 'opted_out',
        ]);

        // 2. ConsentLog (legacy audit) has entry
        $this->assertDatabaseHas('consent_logs', [
            'contact_id' => $contact->id,
            'action' => 'OPT_OUT',
            'source' => 'STOP_KEYWORD',
        ]);

        // 3. ConsentRegistry (authoritative) has entry
        $this->assertDatabaseHas('consent_registry', [
            'contact_id' => $contact->id,
            'action' => 'OPT_OUT',
            'source' => 'STOP_KEYWORD',
        ]);
    }

    // ──────────────────────────────────────────────────────────────────────────
    // Test 5: isOptedOut() reads from registry correctly
    // ──────────────────────────────────────────────────────────────────────────

    public function test_is_opted_out_reads_from_registry(): void
    {
        $team = Team::factory()->create();
        $contact = $this->makeContact($team, [
            'opt_in_status' => 'opted_in',
            'phone_number' => '+15550000001',
        ]);

        // Pre-state: opted_in, no registry entry
        $this->assertFalse(ConsentRegistry::isOptedOut($team->id, $contact->phone_number));

        // Opt out
        $this->consentService->optOut($contact, 'STOP_KEYWORD');

        // Post-state: registry says opted_out
        $this->assertTrue(ConsentRegistry::isOptedOut($team->id, $contact->phone_number));

        // Later opt back in — reload the contact from DB (it was updated)
        $contact->refresh();
        $this->consentService->optIn($contact, 'START_KEYWORD');

        // Registry latest is now OPT_IN → no longer opted out
        $this->assertFalse(ConsentRegistry::isOptedOut($team->id, $contact->phone_number));
    }

    // ──────────────────────────────────────────────────────────────────────────
    // Test 6 (CORE): Restore cannot silently re-enable an opted-out contact
    //
    // T+0  Contact opted_in  → backup taken (backup SQL will restore opted_in)
    // T+1  Contact sends STOP → OPT_OUT in registry
    // T+2  Admin restores T+0 backup → contacts.opt_in_status is reset to opted_in
    //      ← rehydrateTeamFromRegistry() MUST flip it back to opted_out
    // ──────────────────────────────────────────────────────────────────────────

    public function test_restore_cannot_revert_later_opt_out(): void
    {
        // Team needs a subscription_status so EntitlementService doesn't crash
        // when BackupService::backupTenant() calls hasFeature('backups')
        $team = Team::factory()->create([
            'subscription_status' => 'trial',
            'subscription_plan' => 'pro',
            'trial_ends_at' => now()->addDays(14),
        ]);
        $user = User::factory()->create(['current_team_id' => $team->id]);
        $this->actingAs($user);

        // T+0: Contact is opted_in (insert directly to bypass observer)
        $contact = $this->makeContact($team, [
            'opt_in_status' => 'opted_in',
            'phone_number' => '+15550000002',
        ]);

        // --- Simulate the backup SQL that will be "restored" ---
        // SQLite-compatible UPSERT (no TIMESTAMPDIFF involved in this raw SQL,
        // and clearTenantData will DELETE the contact, so backup SQL re-inserts it)
        $sqlContent = 'INSERT OR REPLACE INTO contacts '
            .'(id, team_id, phone_number, opt_in_status, created_at, updated_at) '
            ."VALUES ({$contact->id}, {$team->id}, '+15550000002', 'opted_in', datetime('now'), datetime('now'));";

        // --- Create an encrypted backup ZIP ---
        $zipPath = storage_path('app/backup-temp/consent_test_'.$team->id.'.zip');
        @mkdir(dirname($zipPath), 0755, true);
        $zip = new ZipArchive;
        $zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE);
        $zip->addFromString('database.sql', $sqlContent);
        $innerSignature = hash_hmac('sha256', $sqlContent, config('app.key'));
        $zip->addFromString('backup.signature', $innerSignature);
        $zip->close();

        $key = config('app.key');
        if (str_starts_with($key, 'base64:')) {
            $key = base64_decode(substr($key, 7));
        }

        $teamKey = hash_hmac('sha256', (string) $team->id, $key, true);

        $iv = openssl_random_pseudo_bytes(openssl_cipher_iv_length('aes-256-cbc'));
        $encrypted = openssl_encrypt(file_get_contents($zipPath), 'aes-256-cbc', $teamKey, 0, $iv);
        file_put_contents($zipPath.'.enc', $iv.$encrypted);

        $backupFilename = 'consent_test_'.$team->id.'.zip.enc';

        $backup = TenantBackup::create([
            'team_id' => $team->id,
            'filename' => $backupFilename,
            'path' => "tenants/{$team->id}/",
            'status' => 'completed',
            'checksum' => hash_file('sha256', $zipPath.'.enc'),
            'type' => 'tenant',
        ]);

        Storage::disk('local')->put(
            "backups/tenants/{$team->id}/{$backupFilename}",
            file_get_contents($zipPath.'.enc')
        );

        // T+1: Contact sends STOP AFTER backup → OPT_OUT in registry
        // Must be actingAs a user because ContactOptedOut event reads request()->user()
        $this->consentService->optOut($contact, 'STOP_KEYWORD', 'User replied STOP after backup');

        // Verify registry has OPT_OUT
        $this->assertTrue(ConsentRegistry::isOptedOut($team->id, $contact->phone_number));

        // T+2: Restore the T+0 backup (which has opted_in for this contact).
        // The restore will: delete all contacts → re-insert from SQL (opted_in)
        //                  → rehydrateTeamFromRegistry() → flip back to opted_out
        $this->backupService->restoreTenant($team, $backup);

        // ── ASSERTION: Contact must still be opted_out ──────────────────────
        $refreshed = Contact::where('team_id', $team->id)
            ->where('phone_number', '+15550000002')
            ->first();

        $this->assertNotNull($refreshed, 'Contact should exist after restore');

        $this->assertEquals(
            'opted_out',
            $refreshed->opt_in_status,
            'GDPR VIOLATION: Restore must not re-enable a contact who opted out after backup was taken'
        );

        // Registry must still have original OPT_OUT (immutable)
        $this->assertTrue(ConsentRegistry::isOptedOut($team->id, '+15550000002'));

        // Cleanup temp files
        foreach ([$zipPath, $zipPath.'.enc'] as $f) {
            if (file_exists($f)) {
                unlink($f);
            }
        }
    }

    // ──────────────────────────────────────────────────────────────────────────
    // Test 7: Rehydration cancels pending campaign messages for re-blocked contacts
    // ──────────────────────────────────────────────────────────────────────────

    public function test_rehydration_cancels_pending_messages_for_opted_out_contacts(): void
    {
        $team = Team::factory()->create();
        // Start as opted_in (simulating a post-restore state where backup reverted status)
        // The registry OPT_OUT will trigger rehydration to flip back AND cancel messages.
        $contact = $this->makeContact($team, [
            'opt_in_status' => 'opted_in',
            'phone_number' => '+15550000003',
        ]);

        // Simulate a pending campaign snapshot entry (as if restore re-enabled them)
        $campaignId = DB::table('campaigns')->insertGetId([
            'team_id' => $team->id,
            'name' => 'Test Campaign',
            'campaign_name' => 'Test Campaign',
            'status' => 'processing',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $snapshotId = DB::table('campaign_snapshots')->insertGetId([
            'campaign_id' => $campaignId,
            'template_name' => 'test_template',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $pendingId = DB::table('campaign_snapshot_contacts')->insertGetId([
            'snapshot_id' => $snapshotId,
            'contact_id' => $contact->id,
            'phone_number' => '+15550000003',
            'status' => 'pending',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Write OPT_OUT to registry
        ConsentRegistry::create([
            'team_id' => $team->id,
            'contact_id' => $contact->id,
            'phone_number' => '+15550000003',
            'action' => 'OPT_OUT',
            'source' => 'STOP_KEYWORD',
            'channel' => 'whatsapp',
            'recorded_at' => now(),
        ]);

        // Run rehydration (as would happen after restore)
        $this->consentService->rehydrateTeamFromRegistry($team->id);

        // Pending message must be cancelled
        $this->assertDatabaseHas('campaign_snapshot_contacts', [
            'id' => $pendingId,
            'status' => 'cancelled',
        ]);
    }

    // ──────────────────────────────────────────────────────────────────────────
    // Test 8: getLatestAuthoritativeState respects chronological order
    //
    // This test is the purest — no contacts table, no factories, no observers.
    // ──────────────────────────────────────────────────────────────────────────

    public function test_latest_authoritative_state_is_chronologically_correct(): void
    {
        $team = Team::factory()->create();
        $phone = '+15550000004';

        // OPT_IN at T+0
        ConsentRegistry::create([
            'team_id' => $team->id,
            'phone_number' => $phone,
            'action' => 'OPT_IN',
            'source' => 'WEB',
            'channel' => 'web',
            'recorded_at' => now()->subHours(2),
            'created_at' => now()->subHours(2),
            'updated_at' => now()->subHours(2),
        ]);

        // OPT_OUT at T+1
        ConsentRegistry::create([
            'team_id' => $team->id,
            'phone_number' => $phone,
            'action' => 'OPT_OUT',
            'source' => 'STOP_KEYWORD',
            'channel' => 'whatsapp',
            'recorded_at' => now()->subHour(),
            'created_at' => now()->subHour(),
            'updated_at' => now()->subHour(),
        ]);

        $latest = ConsentRegistry::getLatestAuthoritativeState($team->id, $phone);
        $this->assertEquals('OPT_OUT', $latest->action, 'Latest authoritative state must be OPT_OUT');
        $this->assertTrue(ConsentRegistry::isOptedOut($team->id, $phone));

        // OPT_IN again at T+2
        ConsentRegistry::create([
            'team_id' => $team->id,
            'phone_number' => $phone,
            'action' => 'OPT_IN',
            'source' => 'START_KEYWORD',
            'channel' => 'whatsapp',
            'recorded_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $latest = ConsentRegistry::getLatestAuthoritativeState($team->id, $phone);
        $this->assertEquals('OPT_IN', $latest->action, 'Latest authoritative state must be OPT_IN after re-opt-in');
        $this->assertFalse(ConsentRegistry::isOptedOut($team->id, $phone));
    }
}
