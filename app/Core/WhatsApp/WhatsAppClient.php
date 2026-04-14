<?php

namespace App\Core\WhatsApp;

use App\Models\Team;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WhatsAppClient
{
    protected ?Team $team = null;

    protected string $baseUrl;
    protected ?string $appId = null;
    protected ?string $appSecret = null;

    protected ?string $token = null;

    protected ?string $phoneId = null;

    protected bool $skipAppSecretProof = false;

    protected CredentialResolver $resolver;

    public function __construct(CredentialResolver $resolver)
    {
        $this->resolver = $resolver;
        $this->baseUrl = config('whatsapp.base_url', 'https://graph.facebook.com').'/'.config('whatsapp.api_version', 'v21.0');
    }

    /**
     * Set the context for a specific team.
     */
    public function forTeam(Team $team): self
    {
        $this->team = $team;
        $creds = $this->resolver->resolve($team);

        $this->token = (string) ($creds['token'] ?? '');
        $this->phoneId = $creds['phone_number_id'] ?? null;
        $this->appId = $creds['app_id'] ?? null;
        $this->appSecret = $creds['app_secret'] ?? null;

        return $this;
    }

    public function setSkipAppSecretProof(bool $skip): self
    {
        $this->skipAppSecretProof = $skip;

        return $this;
    }

    /**
     * Get the base URL configured for the WhatsApp API.
     */
    public function getBaseUrl(): string
    {
        return $this->baseUrl;
    }

    /**
     * Send a request to the WhatsApp API using a relative endpoint.
     */
    public function sendRequest(string $endpoint, array $data = [], string $method = 'post'): array
    {
        if (! $this->phoneId) {
            return ['success' => false, 'error' => 'Phone ID not configured', 'status_code' => 400];
        }

        $url = "{$this->baseUrl}/{$this->phoneId}/{$endpoint}";

        return $this->sendRequestFullUrl($url, $method, $data, $endpoint);
    }

    public function sendRequestFullUrl(string $url, string $method, array $data = [], string $endpoint = 'unknown'): array
    {
        // Sandbox Mode Interceptor
        if ($this->team && $this->team->is_sandbox_mode) {
            Log::info("WhatsApp API [SANDBOX] Intercepted [$method] to $endpoint", ['data' => $data]);

            return [
                'success' => true,
                'is_sandbox' => true,
                'data' => [
                    'messaging_product' => 'whatsapp',
                    'contacts' => [['input' => $data['to'] ?? 'unknown', 'wa_id' => $data['to'] ?? 'unknown']],
                    'messages' => [['id' => 'sandbox_'.bin2hex(random_bytes(16))]],
                ],
                'duration_ms' => 5.0,
            ];
        }

        $appSecret = $this->appSecret ?: config('whatsapp.app_secret');
        $isSystemToken = $this->token && str_starts_with($this->token, 'EAAB');

        $queryParams = [];
        if (! $isSystemToken && $appSecret && $this->token && ! $this->skipAppSecretProof) {
            $queryParams['appsecret_proof'] = hash_hmac('sha256', $this->token, $appSecret);
        }

        $client = Http::withToken($this->token)
            ->withHeaders(['Content-Type' => 'application/json'])
            ->withQueryParameters($queryParams)
            ->timeout(20)
            ->retry(2, 500, function ($exception) {
                if ($exception instanceof \Illuminate\Http\Client\ConnectionException) {
                    return true;
                }
                if ($exception instanceof \Illuminate\Http\Client\RequestException) {
                    return $exception->response->status() >= 500;
                }

                return false;
            }, throw: false);

        $method = strtolower($method);
        Log::debug("WhatsApp API Sending [$method] to $url", ['data' => $this->maskSensitiveData($data)]);

        $startTime = microtime(true);

        try {
            $response = match ($method) {
                'post' => $client->post($url, $data),
                'get' => $client->get($url, $data),
                'delete' => $client->delete($url, $data),
                default => null,
            };

            // [RESILIENCE] If it failed due to invalid appsecret_proof, retry without it
            if ($response && $response->failed()) {
                $errorData = $response->json();
                if (($errorData['error']['code'] ?? 0) == 100 && str_contains($errorData['error']['message'] ?? '', 'Invalid appsecret_proof')) {
                    Log::warning("WhatsApp API: Invalid appsecret_proof, retrying WITHOUT proof for $endpoint");
                    
                    $retryClient = Http::withToken($this->token)
                        ->withHeaders(['Content-Type' => 'application/json'])
                        ->timeout(20);
                        
                    $response = match ($method) {
                        'post' => $retryClient->post($url, $data),
                        'get' => $retryClient->get($url, $data),
                        'delete' => $retryClient->delete($url, $data),
                        default => null,
                    };
                }
            }

            $duration = round((microtime(true) - $startTime) * 1000, 2);

            if (! $response) {
                return ['success' => false, 'error' => 'Unsupported method: '.$method, 'duration_ms' => $duration];
            }

            if ($response->failed()) {
                $failedResult = $this->handleFailedResponse($response, $url, $data, $endpoint);
                $failedResult['duration_ms'] = $duration;

                return $failedResult;
            }

            Log::info("WhatsApp API Success [$method] to $endpoint", [
                'duration_ms' => $duration,
                'status' => $response->status(),
            ]);

            if ($this->team) {
                \App\Services\WhatsAppEventBridge::logInteraction($this->team, $endpoint, 'success', $this->maskSensitiveData($data), [
                    'url' => $url,
                    'duration_ms' => $duration,
                ]);
            }

            return ['success' => true, 'data' => $response->json(), 'duration_ms' => $duration];
        } catch (\Exception $e) {
            $duration = round((microtime(true) - $startTime) * 1000, 2);
            Log::error('WhatsApp API Exception', [
                'message' => $e->getMessage(),
                'url' => $url,
                'method' => $method,
                'duration_ms' => $duration,
            ]);

            return ['success' => false, 'error' => $e->getMessage(), 'status_code' => 500, 'duration_ms' => $duration];
        }
    }

    /**
     * Handle a failed API response.
     */
    protected function handleFailedResponse($response, string $url, array $data, string $endpoint): array
    {
        $responseJson = $response->json();
        $errorCode = $responseJson['error']['code'] ?? null;

        // Custom handling for specific Meta error codes
        if ($errorCode === 141000) {
            Log::info('WhatsApp: Calling not supported for this number (#141000)', ['url' => $url]);

            return [
                'success' => false,
                'calling_not_supported' => true,
                'error' => $responseJson,
                'status_code' => $response->status(),
                'message' => $responseJson['error']['message'] ?? 'Calling not supported',
            ];
        }

        if ($response->status() === 401 && $this->team) {
            $this->team->update(['whatsapp_setup_state' => \App\Enums\IntegrationState::SUSPENDED]);
            \App\Services\WhatsAppEventBridge::logInteraction($this->team, $endpoint, 'critical', $this->maskSensitiveData($data), ['error' => $responseJson]);
        } elseif ($this->team) {
            \App\Services\WhatsAppEventBridge::logInteraction($this->team, $endpoint, 'failed', $this->maskSensitiveData($data), ['error' => $responseJson]);
        }

        Log::error('WhatsApp API Error', [
            'status' => $response->status(),
            'error' => $responseJson,
            'payload' => $this->maskSensitiveData($data),
            'url' => $url,
        ]);

        return [
            'success' => false,
            'error' => $responseJson,
            'status_code' => $response->status(),
            'message' => $responseJson['error']['message'] ?? 'Unknown API Error',
        ];
    }

    /**
     * Mask sensitive fields in a payload.
     */
    protected function maskSensitiveData(array $data): array
    {
        $sensitiveKeys = [
            'to',
            'phone',
            'phone_number',
            'email',
            'name',
            'body',
            'text',
            'caption',
            'content',
            'otp',
            'code',
            'token',
            'password',
            'secret',
            'url',
            'link',
        ];

        array_walk_recursive($data, function (&$value, $key) use ($sensitiveKeys) {
            if (in_array(strtolower($key), $sensitiveKeys) && is_string($value)) {
                if ($key === 'to' || $key === 'phone_number' || $key === 'phone') {
                    $value = substr($value, 0, 4).'****'.substr($value, -2);
                } else {
                    $value = '********';
                }
            }
        });

        return $data;
    }
}
