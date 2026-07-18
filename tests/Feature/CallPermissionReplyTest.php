<?php

namespace Tests\Feature;

use App\Core\Webhooks\WhatsAppEventRouter;
use App\Helpers\PhoneNumberHelper;
use App\Models\CallPermission;
use App\Models\Contact;
use App\Models\Team;
use App\Services\EventBusService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use ReflectionMethod;
use Tests\TestCase;

/**
 * Verifies Meta's native call_permission_reply webhook is recorded as an
 * actual grant/reject and honors Meta's expiration_timestamp — not a
 * hardcoded 72h and not "granted" on every reply.
 */
class CallPermissionReplyTest extends TestCase
{
    use RefreshDatabase;

    private function reply(Team $team, string $fromRaw, array $replyBody): void
    {
        $router = new WhatsAppEventRouter(new EventBusService, $team->id, $team->whatsapp_phone_number_id);

        $method = new ReflectionMethod($router, 'handleInteractiveResponse');
        $method->setAccessible(true);
        $method->invoke($router, [
            'from' => $fromRaw,
            'type' => 'interactive',
            'interactive' => [
                'type' => 'call_permission_reply',
                'call_permission_reply' => $replyBody,
            ],
        ]);
    }

    public function test_accept_grants_permission_with_metas_expiration(): void
    {
        $team = Team::factory()->create(['whatsapp_phone_number_id' => '555']);
        $raw = '15551230000';
        $contact = Contact::factory()->create(['team_id' => $team->id, 'phone_number' => PhoneNumberHelper::normalize($raw)]);
        $permission = CallPermission::create([
            'team_id' => $team->id,
            'phone_number_id' => '555',
            'contact_id' => $contact->id,
            'permission_status' => 'requested',
            'permission_requested_at' => now(),
        ]);

        $metaExpiry = now()->addHours(6)->timestamp;
        $this->reply($team, $raw, [
            'response' => 'accept',
            'is_permanent' => false,
            'expiration_timestamp' => $metaExpiry,
        ]);

        $permission->refresh();
        $this->assertSame('granted', $permission->permission_status);
        // Honors Meta's 6h window, not the default 72h fallback.
        $this->assertSame($metaExpiry, $permission->permission_expires_at->timestamp);
    }

    public function test_reject_revokes_permission(): void
    {
        $team = Team::factory()->create(['whatsapp_phone_number_id' => '555']);
        $raw = '15551230001';
        $contact = Contact::factory()->create(['team_id' => $team->id, 'phone_number' => PhoneNumberHelper::normalize($raw)]);
        $permission = CallPermission::create([
            'team_id' => $team->id,
            'phone_number_id' => '555',
            'contact_id' => $contact->id,
            'permission_status' => 'requested',
            'permission_requested_at' => now(),
        ]);

        $this->reply($team, $raw, ['response' => 'reject']);

        $this->assertSame('revoked', $permission->refresh()->permission_status);
    }
}
