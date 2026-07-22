<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    private function payload(array $overrides = []): array
    {
        return array_merge([
            'name' => 'Jane Doe',
            'email' => 'jane@example.com',
            'phone' => '+15550001111',
            'company' => 'Acme Inc',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
            'terms' => true,
        ], $overrides);
    }

    public function test_signup_keeps_the_phone_and_company_it_asked_for(): void
    {
        $this->post('/register', $this->payload());

        $user = User::where('email', 'jane@example.com')->firstOrFail();

        // Regression: these were collected by the form and silently dropped,
        // forcing the user to retype them in the onboarding popup.
        $this->assertSame('+15550001111', $user->phone);
        $this->assertSame('Acme Inc', $user->company_name);
    }

    public function test_team_is_named_after_the_company_not_the_person(): void
    {
        $this->post('/register', $this->payload());

        $user = User::where('email', 'jane@example.com')->firstOrFail();

        // A team still called "Jane's Team" re-triggers the details popup.
        $this->assertSame('Acme Inc', $user->currentTeam->name);
    }

    public function test_phone_and_company_remain_optional(): void
    {
        $this->post('/register', $this->payload([
            'phone' => '',
            'company' => '',
        ]));

        $user = User::where('email', 'jane@example.com')->firstOrFail();

        $this->assertNull($user->phone);
        $this->assertNull($user->company_name);
        $this->assertSame("Jane's Team", $user->currentTeam->name);
    }

    public function test_utm_attribution_is_recorded(): void
    {
        $this->post('/register', $this->payload([
            'utm_source' => 'google',
            'utm_medium' => 'cpc',
            'utm_campaign' => 'launch',
        ]));

        $user = User::where('email', 'jane@example.com')->firstOrFail();

        // Regression: utm_* were missing from $fillable, so every signup lost
        // its attribution in production and 500'd everywhere else.
        $this->assertSame('google', $user->utm_source);
        $this->assertSame('cpc', $user->utm_medium);
        $this->assertSame('launch', $user->utm_campaign);
    }

    public function test_terms_must_be_accepted(): void
    {
        $this->post('/register', $this->payload(['terms' => false]))
            ->assertSessionHasErrors('terms');

        $this->assertNull(User::where('email', 'jane@example.com')->first());
    }

    public function test_new_signups_start_unverified(): void
    {
        $this->post('/register', $this->payload());

        // User implements MustVerifyEmail, so the `verified` middleware on the
        // app routes now actually gates password signups.
        $this->assertNull(User::where('email', 'jane@example.com')->firstOrFail()->email_verified_at);
    }
}
