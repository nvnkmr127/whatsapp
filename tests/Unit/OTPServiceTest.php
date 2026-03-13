<?php

namespace Tests\Unit;

use App\Models\Team;
use App\Models\WhatsappTemplate;
use App\Services\OTPService;
use App\Services\EntitlementService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;
use Mockery;
use Tests\TestCase;

class OTPServiceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        if (!Schema::hasTable('users')) {
            Schema::create('users', function (Blueprint $table) {
                $table->id();
                $table->string('email')->nullable();
                $table->string('phone')->nullable();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('teams')) {
            Schema::create('teams', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->nullable();
                $table->string('name');
                $table->boolean('personal_team')->default(false);
                $table->text('whatsapp_access_token')->nullable();
                $table->string('whatsapp_phone_number_id')->nullable();
                $table->string('whatsapp_business_account_id')->nullable();
                $table->string('subscription_status')->nullable();
                $table->string('subscription_plan')->nullable();
                $table->timestamp('trial_ends_at')->nullable();
                $table->timestamp('subscription_ends_at')->nullable();
                $table->timestamp('offer_claimed_at')->nullable();
                $table->boolean('offer_excluded')->default(false);
                $table->boolean('offer_converted_churned')->default(false);
                $table->json('offer_snapshot')->nullable();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('whatsapp_templates')) {
            Schema::create('whatsapp_templates', function (Blueprint $table) {
                $table->id();
                $table->foreignId('team_id');
                $table->string('name');
                $table->string('language');
                $table->string('category')->nullable();
                $table->string('status')->nullable();
                $table->json('components')->nullable();
                $table->timestamps();
                $table->softDeletes();
            });
        }
    }

    protected function tearDown(): void
    {
        Mockery::close();

        parent::tearDown();
    }

    public function test_send_does_not_cache_email_otp_when_delivery_fails()
    {
        $service = new class extends OTPService {
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
        $service = new class extends OTPService {
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

    public function test_send_custom_whatsapp_otp_does_not_cache_when_template_send_fails()
    {
        $mock = Mockery::mock('overload:App\Services\WhatsAppService');
        $mock->shouldReceive('sendTemplate')
            ->once()
            ->andReturn([
                'success' => false,
                'error' => "Your Expired subscription does not permit access to 'send_message'.",
            ]);

        $service = new class extends OTPService {
            public function cacheKeyFor(string $identifier): string
            {
                return $this->getCacheKey($identifier);
            }
        };

        $phone = '+918688771397';
        $team = new Team();
        $team->id = 123;

        $this->assertFalse($service->sendCustomWhatsAppOtp($phone, '123456', 'verification', 'en', ['123456'], $team));
        $this->assertNull(Cache::get($service->cacheKeyFor($phone)));
    }

    public function test_send_custom_whatsapp_otp_caches_after_successful_template_send()
    {
        $mock = Mockery::mock('overload:App\Services\WhatsAppService');
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

        app()->instance(\App\Services\WebhookService::class, new class {
            public function dispatch($teamId, $event, $payload): void
            {
            }
        });

        $service = new class extends OTPService {
            public function cacheKeyFor(string $identifier): string
            {
                return $this->getCacheKey($identifier);
            }
        };

        $phone = '+918688771398';
        $code = '654321';
        $team = new Team();
        $team->id = 456;

        $this->assertTrue($service->sendCustomWhatsAppOtp($phone, $code, 'verification', 'en', [$code], $team));
        $this->assertTrue($service->verify($phone, $code, false));
    }

    public function test_find_sending_team_skips_teams_without_send_message_access()
    {
        $expiredTeam = Team::query()->create([
            'name' => 'Expired Team',
            'whatsapp_access_token' => 'expired-token',
            'whatsapp_phone_number_id' => 'phone-expired',
            'subscription_status' => 'expired',
        ]);

        $activeTeam = Team::query()->create([
            'name' => 'Active Team',
            'whatsapp_access_token' => 'active-token',
            'whatsapp_phone_number_id' => 'phone-active',
            'subscription_status' => 'active',
        ]);

        WhatsappTemplate::query()->create([
            'team_id' => $expiredTeam->id,
            'name' => 'verification',
            'language' => 'en',
            'status' => 'APPROVED',
        ]);

        WhatsappTemplate::query()->create([
            'team_id' => $activeTeam->id,
            'name' => 'verification',
            'language' => 'en',
            'status' => 'APPROVED',
        ]);

        app()->instance(EntitlementService::class, new class($activeTeam->id) {
            public function __construct(private int $allowedTeamId)
            {
            }

            public function for(Team $team): object
            {
                $allowed = $team->id === $this->allowedTeamId;

                return new class($allowed) {
                    public function __construct(private bool $allowed)
                    {
                    }

                    public function can(string $capability): bool
                    {
                        return $capability === 'send_message' ? $this->allowed : false;
                    }
                };
            }
        });

        $service = new class extends OTPService {
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
        $dbTeam = Team::query()->create([
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
        ]);

        $_ENV['WHATSAPP_SYSTEM_ACCESS_TOKEN']    = 'sys-token-abc';
        $_ENV['WHATSAPP_SYSTEM_PHONE_NUMBER_ID'] = 'sys-phone-123';
        putenv('WHATSAPP_SYSTEM_ACCESS_TOKEN=sys-token-abc');
        putenv('WHATSAPP_SYSTEM_PHONE_NUMBER_ID=sys-phone-123');

        $service = new class extends OTPService {
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

        putenv('WHATSAPP_SYSTEM_ACCESS_TOKEN');
        putenv('WHATSAPP_SYSTEM_PHONE_NUMBER_ID');
        unset($_ENV['WHATSAPP_SYSTEM_ACCESS_TOKEN'], $_ENV['WHATSAPP_SYSTEM_PHONE_NUMBER_ID']);
    }
}
