<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * User now implements MustVerifyEmail, so the `verified` middleware genuinely
 * gates the app. OAuth and OTP users are verified by definition and set
 * email_verified_at at creation — but it was missing from $fillable, so it was
 * silently discarded and those users would have been locked out.
 */
class SocialLoginVerificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_email_verified_at_survives_mass_assignment(): void
    {
        $user = User::create([
            'name' => 'OAuth User',
            'email' => 'oauth@example.com',
            'password' => null,
            'email_verified_at' => now(),
        ]);

        $this->assertNotNull($user->fresh()->email_verified_at);
        $this->assertTrue($user->fresh()->hasVerifiedEmail());
    }

    public function test_a_verified_user_is_not_bounced_by_the_verified_middleware(): void
    {
        $user = User::factory()->withPersonalTeam()->create(['email_verified_at' => now()]);

        $this->actingAs($user)->get('/dashboard')->assertOk();
    }

    public function test_an_unverified_user_is_redirected_to_verify(): void
    {
        $user = User::factory()->withPersonalTeam()->create(['email_verified_at' => null]);

        $this->actingAs($user)->get('/dashboard')->assertRedirect(route('verification.notice'));
    }

    public function test_phone_otp_login_verifies_user(): void
    {
        $user = User::factory()->withPersonalTeam()->create([
            'phone' => '+15551234567',
            'email_verified_at' => null,
        ]);

        $otpService = $this->mock(\App\Services\OTPService::class);
        $otpService->shouldReceive('verify')->with('+15551234567', '123456')->andReturn(true);

        $response = $this->post(route('auth.otp.verify'), [
            'identifier' => '+15551234567',
            'type' => 'phone',
            'code' => '123456',
        ]);

        $this->assertNotNull($user->fresh()->email_verified_at);
        $this->actingAs($user->fresh())->get('/dashboard')->assertOk();
    }

    public function test_team_member_created_with_phone_is_verified(): void
    {
        $owner = User::factory()->withPersonalTeam()->create();
        $action = new \App\Actions\Custom\CreateUserAndAddToTeam();
        
        $action->create($owner, $owner->personalTeam(), [
            'name' => 'Agent Smith',
            'phone' => '+15559876543',
            'role' => 'agent',
        ]);

        $created = User::where('phone', '+15559876543')->first();
        $this->assertNotNull($created);
        $this->assertNotNull($created->email_verified_at);
        $this->actingAs($created)->get('/dashboard')->assertOk();
    }
}
