<?php

namespace Tests\Unit;

use App\Models\Team;
use App\Models\WhatsappTemplate;
use App\Services\EntitlementService;
use App\Services\OTPService;
use Illuminate\Support\Facades\Cache;
use Mockery;
use Tests\TestCase;

class OTPServiceTest extends TestCase
{
    use \Illuminate\Foundation\Testing\RefreshDatabase;

    protected function tearDown(): void
    {
        Mockery::close();

        parent::tearDown();
    }

    public function test_send_does_not_cache_email_otp_when_delivery_fails()
    {
        $service = new class extends OTPService
        {
            protected function sendEmail(string $email, string $code): bool
            {
                return false;
            }

            public function cacheKeyFor(string $identifier): string
            {
                return $this->getCacheKey($identifier);
            }
        };

        $identifier = 'fail@example.com';

        $this->assertFalse($service->send($identifier, 'email'));
        $this->assertNull(Cache::get($service->cacheKeyFor($identifier)));
    }

    public function test_send_caches_email_otp_after_successful_delivery()
    {
        $service = new class extends OTPService
        {
            protected function sendEmail(string $email, string $code): bool
            {
                return true;
            }

            public function cacheKeyFor(string $identifier): string
            {
                return $this->getCacheKey($identifier);
            }
        };

        $identifier = 'success@example.com';

        $this->assertTrue($service->send($identifier, 'email', 1));
        $this->assertNotNull(Cache::get($service->cacheKeyFor($identifier)));
    }

    public function test_send_custom_whatsapp_otp_caches_after_successful_template_send()
    {
        $mock = Mockery::mock(\App\Services\WhatsAppService::class);
        $mock->shouldReceive('setTeam')->andReturnSelf();
        $mock->shouldReceive('sendTemplate')
            ->once()
            ->andReturn([
                'success' => false,
                'error' => "Your Expired subscription does not permit access to 'send_message'.",
            ]);
        app()->instance(\App\Services\WhatsAppService::class, $mock);

        $service = new class extends OTPService
        {
            public function cacheKeyFor(string $identifier): string
            {
                return $this->getCacheKey($identifier);
            }
        };

        $phone = '+918688771397';
        $team = new Team;
        $team->id = 123;

        $this->assertFalse($service->sendCustomWhatsAppOtp($phone, '123456', 'verification', 'en', ['123456'], $team));
        $this->assertNull(Cache::get($service->cacheKeyFor($phone)));
    }

    public function test_send_custom_whatsapp_otp_does_not_cache_when_template_send_fails()
    {
        $mock = Mockery::mock(\App\Services\WhatsAppService::class);
        $mock->shouldReceive('setTeam')->andReturnSelf();
        $mock->shouldReceive('sendTemplate')
            ->once()
            ->andReturn([
                'success' => true,
                'data' => [
                    'messages' => [
                        ['id' => 'wamid.test'],
                    ],
                ],
            ]);
        app()->instance(\App\Services\WhatsAppService::class, $mock);

        app()->instance(\App\Services\WebhookService::class, new class
        {
            public function dispatch($teamId, $event, $payload): void {}
        });

        $service = new class extends OTPService
        {
            public function cacheKeyFor(string $identifier): string
            {
                return $this->getCacheKey($identifier);
            }
        };

        $phone = '+918688771398';
        $code = '654321';
        $team = new Team;
        $team->id = 456;

        $this->assertTrue($service->sendCustomWhatsAppOtp($phone, $code, 'verification', 'en', [$code], $team));
        $this->assertTrue($service->verify($phone, $code, false));
    }

    public function test_find_sending_team_skips_teams_without_send_message_access()
    {
        $user = \App\Models\User::factory()->create();

        $expiredTeam = Team::factory()->create([
            'user_id' => $user->id,
            'name' => 'Expired Team',
            'whatsapp_access_token' => 'expired-token',
            'whatsapp_phone_number_id' => 'phone-expired',
            'subscription_status' => 'expired',
        ]);

        $activeTeam = Team::factory()->create([
            'user_id' => $user->id,
            'name' => 'Active Team',
            'whatsapp_access_token' => 'active-token',
            'whatsapp_phone_number_id' => 'phone-active',
            'subscription_status' => 'active',
        ]);

        WhatsappTemplate::query()->create([
            'team_id' => $expiredTeam->id,
            'name' => 'verification',
            'language' => 'en',
            'category' => 'AUTHENTICATION',
            'status' => 'APPROVED',
            'components' => [],
        ]);

        WhatsappTemplate::query()->create([
            'team_id' => $activeTeam->id,
            'name' => 'verification',
            'language' => 'en',
            'category' => 'AUTHENTICATION',
            'status' => 'APPROVED',
            'components' => [],
        ]);

        app()->instance(EntitlementService::class, new class($activeTeam->id)
        {
            public function __construct(private int $allowedTeamId) {}

            public function for(Team $team): object
            {
                $allowed = $team->id === $this->allowedTeamId;

                return new class($allowed)
                {
                    public function __construct(private bool $allowed) {}

                    public function can(string $capability): bool
                    {
                        return $capability === 'send_message' ? $this->allowed : false;
                    }
                };
            }
        });

        $service = new class extends OTPService
        {
            public function resolveSendingTeam(): ?Team
            {
                return $this->findSendingTeam();
            }
        };

        $selectedTeam = $service->resolveSendingTeam();

        $this->assertNotNull($selectedTeam);
        $this->assertSame($activeTeam->id, $selectedTeam->id);
    }

    public function test_find_sending_team_with_system_env_uses_real_team_id_not_zero()
    {
        $user = \App\Models\User::factory()->create();

        $dbTeam = Team::factory()->create([
            'user_id' => $user->id,
            'name' => 'System Overlay Team',
            'whatsapp_access_token' => 'original-token',
            'whatsapp_phone_number_id' => 'original-phone-id',
            'subscription_status' => 'active',
        ]);

        WhatsappTemplate::query()->create([
            'team_id' => $dbTeam->id,
            'name' => 'verification_code',
            'language' => 'en',
            'category' => 'AUTHENTICATION',
            'status' => 'APPROVED',
            'components' => [],
        ]);

        config([
            'whatsapp.system_access_token' => 'sys-token-abc',
            'whatsapp.system_phone_number_id' => 'sys-phone-123',
        ]);

        $service = new class extends OTPService
        {
            public function resolveSendingTeam(): ?Team
            {
                return $this->findSendingTeam();
            }
        };

        $selectedTeam = $service->resolveSendingTeam();

        $this->assertNotNull($selectedTeam);
        $this->assertNotEquals(0, $selectedTeam->id, 'System team must not be id=0 (breaks template lookup)');
        $this->assertSame($dbTeam->id, $selectedTeam->id);
        $this->assertSame('sys-token-abc', (string) $selectedTeam->whatsapp_access_token);
        $this->assertSame('sys-phone-123', $selectedTeam->whatsapp_phone_number_id);

    }
}
