<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\OAuthClient;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

/**
 * Minimal OAuth 2.1 authorization server for MCP clients (Claude.ai custom
 * connectors, ChatGPT connectors). Public clients only, PKCE (S256) required.
 * The token endpoint mints Sanctum tokens, so the MCP endpoint's existing
 * auth:sanctum guard keeps working unchanged.
 *
 * Implements: RFC 8414 (AS metadata), RFC 9728 (resource metadata),
 * RFC 7591 (dynamic client registration), authorization_code + PKCE.
 */
class OAuthServerController extends Controller
{
    private const CODE_TTL = 300; // seconds an authorization code stays valid

    // GET /.well-known/oauth-authorization-server
    public function authorizationServerMetadata()
    {
        return response()->json([
            'issuer'                                => url('/'),
            'authorization_endpoint'                => url('/oauth/authorize'),
            'token_endpoint'                        => url('/oauth/token'),
            'registration_endpoint'                 => url('/oauth/register'),
            'response_types_supported'              => ['code'],
            'grant_types_supported'                 => ['authorization_code'],
            'code_challenge_methods_supported'      => ['S256'],
            'token_endpoint_auth_methods_supported' => ['none'],
        ]);
    }

    // GET /.well-known/oauth-protected-resource
    public function protectedResourceMetadata()
    {
        return response()->json([
            'resource'              => url('/api/v1/mcp'),
            'authorization_servers' => [url('/')],
            'bearer_methods_supported' => ['header'],
        ]);
    }

    // POST /oauth/register — RFC 7591 dynamic client registration
    public function register(Request $request)
    {
        $data = $request->validate([
            'client_name'     => 'nullable|string|max:255',
            'redirect_uris'   => 'required|array|min:1',
            'redirect_uris.*' => 'required|url|starts_with:https://,http://localhost,http://127.0.0.1',
        ]);

        $client = OAuthClient::create([
            'name'          => $data['client_name'] ?? 'MCP Client',
            'redirect_uris' => $data['redirect_uris'],
        ]);

        return response()->json([
            'client_id'                 => $client->id,
            'client_name'               => $client->name,
            'redirect_uris'             => $client->redirect_uris,
            'token_endpoint_auth_method' => 'none',
            'grant_types'               => ['authorization_code'],
            'response_types'            => ['code'],
        ], 201);
    }

    // GET /oauth/authorize — behind web auth; shows the consent screen
    public function showConsent(Request $request)
    {
        $client = $this->validateAuthorizeRequest($request);
        if (! $client instanceof OAuthClient) {
            return $client; // error response
        }

        return view('auth.oauth-consent', [
            'client' => $client,
            'query'  => $request->query(),
        ]);
    }

    // POST /oauth/approve — consent form submit; issues the code
    public function approve(Request $request)
    {
        $client = $this->validateAuthorizeRequest($request);
        if (! $client instanceof OAuthClient) {
            return $client;
        }

        $redirect = $request->input('redirect_uri');

        if ($request->input('decision') !== 'approve') {
            return redirect()->away($redirect.'?error=access_denied&state='.urlencode($request->input('state', '')));
        }

        $code = Str::random(64);
        Cache::put('oauth_code:'.$code, [
            'user_id'        => $request->user()->id,
            'client_id'      => $client->id,
            'redirect_uri'   => $redirect,
            'code_challenge' => $request->input('code_challenge'),
        ], self::CODE_TTL);

        return redirect()->away($redirect.'?code='.$code.'&state='.urlencode($request->input('state', '')));
    }

    // POST /oauth/token — exchanges code + PKCE verifier for a Sanctum token
    public function token(Request $request)
    {
        if ($request->input('grant_type') !== 'authorization_code') {
            return $this->tokenError('unsupported_grant_type');
        }

        // Single use: pull() deletes the code even if the rest fails
        $stored = Cache::pull('oauth_code:'.$request->input('code'));

        if (! $stored
            || $stored['client_id'] !== $request->input('client_id')
            || $stored['redirect_uri'] !== $request->input('redirect_uri')) {
            return $this->tokenError('invalid_grant');
        }

        $expected = rtrim(strtr(base64_encode(hash('sha256', (string) $request->input('code_verifier'), true)), '+/', '-_'), '=');
        if (! hash_equals($stored['code_challenge'], $expected)) {
            return $this->tokenError('invalid_grant', 'PKCE verification failed');
        }

        $user   = \App\Models\User::findOrFail($stored['user_id']);
        $client = OAuthClient::find($stored['client_id']);
        $token  = $user->createToken('MCP: '.($client?->name ?? 'Connector'))->plainTextToken;

        return response()->json([
            'access_token' => $token,
            'token_type'   => 'Bearer',
            'scope'        => '',
        ]);
    }

    /** Validate client_id / redirect_uri / PKCE params shared by authorize + approve. */
    private function validateAuthorizeRequest(Request $request): OAuthClient|\Illuminate\Http\Response
    {
        $client = OAuthClient::find($request->input('client_id'));
        if (! $client) {
            return response('Unknown client_id.', 400);
        }

        // Exact-match redirect_uri against the registered list — never redirect elsewhere
        if (! in_array($request->input('redirect_uri'), $client->redirect_uris, true)) {
            return response('redirect_uri does not match the registered value.', 400);
        }

        if ($request->input('response_type', 'code') !== 'code'
            || ! $request->filled('code_challenge')
            || $request->input('code_challenge_method') !== 'S256') {
            return response('PKCE (S256) is required.', 400);
        }

        return $client;
    }

    private function tokenError(string $error, ?string $description = null)
    {
        return response()->json(array_filter([
            'error'             => $error,
            'error_description' => $description,
        ]), 400);
    }
}
