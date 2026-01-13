<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

class WhatsAppMessageService
{
    protected string $apiVersion;

    protected $dailyLimit;

    protected $queueName;

    protected $queueTimeout;

    protected $logChannel;

    public function __construct()
    {
        $this->apiVersion   = get_setting('whatsapp.api_version');
        $this->dailyLimit   = get_setting('whatsapp.daily_limit');
        $this->queueName    = json_decode(get_setting('whatsapp.queue'), true)['name'];
        $this->queueTimeout = json_decode(get_setting('whatsapp.queue'), true)['timeout'];
        $this->logChannel   = json_decode(get_setting('whatsapp.logging'), true)['channel'];
    }

    public function sendTemplateMessage(string $to, array $params): array
    {
        try {
            // Check daily limit
            $dailyCount = Cache::get('whatsapp_daily_count_' . date('Y-m-d'), 0);
            if ($dailyCount >= $this->dailyLimit) {
                return [
                    'success' => false,
                    'error'   => 'Daily message limit exceeded',
                    'status'  => 'limit_exceeded',
                ];
            }

            $response = Http::withToken(get_setting('whatsapp.wm_access_token'))
                ->post("https://graph.facebook.com/{$this->apiVersion}/" . get_setting('whatsapp.wm_default_phone_number_id') . '/messages', [
                    'messaging_product' => 'whatsapp',
                    'to'                => $to,
                    'type'              => 'template',
                    'template'          => [
                        'name'     => $params['template_id'],
                        'language' => [
                            'code' => 'en',
                        ],
                        'components' => $this->buildTemplateComponents($params),
                    ],
                ]);

            if ($response->successful()) {
                // Increment daily count
                Cache::increment('whatsapp_daily_count_' . date('Y-m-d'));

                return [
                    'success'    => true,
                    'message_id' => $response->json('messages.0.id'),
                    'status'     => 'sent',
                ];
            }

            $this->logError('API Error', [
                'response' => $response->json(),
                'status'   => $response->status(),
                'to'       => $to,
            ]);

            return [
                'success' => false,
                'error'   => $response->json('error.message', 'Unknown error occurred'),
                'status'  => 'failed',
            ];
        } catch (\Throwable $e) {
            $this->logError('Service Error', [
                'error'       => $e->getMessage(),
                'to'          => $to,
                'template_id' => $params['template_id'],
            ], $e);

            return [
                'success' => false,
                'error'   => $e->getMessage(),
                'status'  => 'error',
            ];
        }
    }

    protected function buildTemplateComponents(array $params): array
    {
        $components = [];

        if (! empty($params['header'])) {
            $components[] = [
                'type'       => 'header',
                'parameters' => [
                    [
                        'type' => 'text',
                        'text' => $params['header'],
                    ],
                ],
            ];
        }

        if (! empty($params['body'])) {
            $components[] = [
                'type'       => 'body',
                'parameters' => [
                    [
                        'type' => 'text',
                        'text' => $params['body'],
                    ],
                ],
            ];
        }

        if (! empty($params['footer'])) {
            $components[] = [
                'type'       => 'footer',
                'parameters' => [
                    [
                        'type' => 'text',
                        'text' => $params['footer'],
                    ],
                ],
            ];
        }

        return $components;
    }

    protected function logError(string $type, array $context, ?\Throwable $exception = null): void
    {
        if (json_decode(get_setting('whatsapp.logging'), true)['detailed']) {
            whatsapp_log("WhatsApp {$type}", 'error', $context, $exception);
        }
    }

    public function validateNumber(string $number): bool
    {
        return Cache::remember("whatsapp_number_valid:{$number}", 3600, function () use ($number) {
            try {
                $response = Http::withToken(get_setting('whatsapp.wm_access_token'))
                    ->post("https://graph.facebook.com/{$this->apiVersion}/" . get_setting('whatsapp.wm_default_phone_number_id') . '/messages', [
                        'messaging_product' => 'whatsapp',
                        'to'                => $number,
                        'type'              => 'text',
                        'text'              => ['preview_url' => false, 'body' => '.'],
                    ]);

                return $response->successful();
            } catch (\Throwable $e) {
                $this->logError('Number Validation Error', [
                    'number' => $number,
                ], $e);

                return false;
            }
        });
    }
}
