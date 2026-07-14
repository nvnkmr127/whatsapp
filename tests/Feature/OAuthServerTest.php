<?php

namespace Tests\Feature;

use App\Models\OAuthClient;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OAuthServerTest extends TestCase
{
    use RefreshDatabase;

    private const VERIFIER = 'test_verifier_string_43chars_minimum_abcdefgh';

    private const REDIRECT = 'https://claude.ai/api/mcp/auth_callback';

    private function challenge(): string
    {
        return rtrim(strtr(base64_encode(hash('sha256', self::VERIFIER, true)), '+/', '-_'), '=');
    }

    public function test_full_pkce_flow_issues_a_working_token(): void
    {
        // Discovery
        $this->getJson('/.well-known/oauth-authorization-server')
            ->assertOk()
            ->assertJsonPath('code_challenge_methods_supported.0', 'S256');

        // Dynamic client registration
        $clientId = $this->postJson('/oauth/register', [
            'client_name' => 'Claude',
            'redirect_uris' => [self::REDIRECT],
        ])->assertCreated()->json('client_id');

        // Consent + code
        $user = User::factory()->withPersonalTeam()->create();
        $query = [
            'client_id' => $clientId,
            'redirect_uri' => self::REDIRECT,
            'response_type' => 'code',
            'state' => 'xyz',
            'code_challenge' => $this->challenge(),
            'code_challenge_method' => 'S256',
        ];
        $this->actingAs($user)->get('/oauth/authorize?'.http_build_query($query))->assertOk();
        $location = $this->actingAs($user)
            ->post('/oauth/approve', $query + ['decision' => 'approve'])
            ->assertRedirect()
            ->headers->get('Location');
        parse_str(parse_url($location, PHP_URL_QUERY), $params);
        $this->assertSame('xyz', $params['state']);

        // Token exchange
        $token = $this->postJson('/oauth/token', [
            'grant_type' => 'authorization_code',
            'code' => $params['code'],
            'client_id' => $clientId,
            'redirect_uri' => self::REDIRECT,
            'code_verifier' => self::VERIFIER,
        ])->assertOk()->json('access_token');

        // Token authenticates as the approving user via Sanctum
        $this->assertSame($user->id, \Laravel\Sanctum\PersonalAccessToken::findToken($token)->tokenable->id);

        // Codes are single-use
        $this->postJson('/oauth/token', [
            'grant_type' => 'authorization_code',
            'code' => $params['code'],
            'client_id' => $clientId,
            'redirect_uri' => self::REDIRECT,
            'code_verifier' => self::VERIFIER,
        ])->assertStatus(400)->assertJsonPath('error', 'invalid_grant');
    }

    public function test_rejects_wrong_verifier_and_unregistered_redirect(): void
    {
        $client = OAuthClient::create(['name' => 'C', 'redirect_uris' => [self::REDIRECT]]);
        $user = User::factory()->withPersonalTeam()->create();
        $query = [
            'client_id' => $client->id,
            'redirect_uri' => self::REDIRECT,
            'response_type' => 'code',
            'code_challenge' => $this->challenge(),
            'code_challenge_method' => 'S256',
        ];

        // Unregistered redirect_uri never reaches consent
        $this->actingAs($user)
            ->get('/oauth/authorize?'.http_build_query(['redirect_uri' => 'https://evil.example/cb'] + $query))
            ->assertStatus(400);

        // Wrong PKCE verifier is rejected
        $location = $this->actingAs($user)
            ->post('/oauth/approve', $query + ['decision' => 'approve'])
            ->headers->get('Location');
        parse_str(parse_url($location, PHP_URL_QUERY), $params);

        $this->postJson('/oauth/token', [
            'grant_type' => 'authorization_code',
            'code' => $params['code'],
            'client_id' => $client->id,
            'redirect_uri' => self::REDIRECT,
            'code_verifier' => 'wrong_verifier_wrong_verifier_wrong_verifie',
        ])->assertStatus(400)->assertJsonPath('error', 'invalid_grant');
    }
}
