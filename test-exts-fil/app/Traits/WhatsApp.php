<?php

namespace App\Traits;

use App\DTOs\WhatsAppMessage;
use App\Exceptions\WhatsAppException;
use App\Jobs\SendWhatsAppMessage;
use App\Models\WhatsappTemplate;
use App\Models\WmActivityLog;
use Endroid\QrCode\Color\Color;
use Endroid\QrCode\Encoding\Encoding;
use Endroid\QrCode\ErrorCorrectionLevel;
use Endroid\QrCode\Label\Label;
use Endroid\QrCode\Logo\Logo;
use Endroid\QrCode\QrCode;
use Endroid\QrCode\RoundBlockSizeMode;
use Endroid\QrCode\Writer\PngWriter;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Queue;
use Netflie\WhatsAppCloudApi\Message\ButtonReply\Button;
use Netflie\WhatsAppCloudApi\Message\ButtonReply\ButtonAction;
use Netflie\WhatsAppCloudApi\Message\CtaUrl\TitleHeader;
use Netflie\WhatsAppCloudApi\Message\Media\LinkID;
use Netflie\WhatsAppCloudApi\Message\Template\Component;
use Netflie\WhatsAppCloudApi\Response\ResponseException;
use Netflie\WhatsAppCloudApi\WhatsAppCloudApi;
use Storage;
use Throwable;

trait WhatsApp
{
    protected static string $facebookAPI = 'https://graph.facebook.com/';

    protected static array $extensionMap = [
        'image/jpeg'                                                                => 'jpg',
        'image/png'                                                                 => 'png',
        'audio/mp3'                                                                 => 'mp3',
        'video/mp4'                                                                 => 'mp4',
        'audio/aac'                                                                 => 'aac',
        'audio/amr'                                                                 => 'amr',
        'audio/ogg'                                                                 => 'ogg',
        'audio/mp4'                                                                 => 'mp4',
        'text/plain'                                                                => 'txt',
        'application/pdf'                                                           => 'pdf',
        'application/vnd.ms-powerpoint'                                             => 'ppt',
        'application/msword'                                                        => 'doc',
        'application/vnd.ms-excel'                                                  => 'xls',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document'   => 'docx',
        'application/vnd.openxmlformats-officedocument.presentationml.presentation' => 'pptx',
        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'         => 'xlsx',
        'video/3gp'                                                                 => '3gp',
        'image/webp'                                                                => 'webp',
    ];

    protected static function getApiVersion(): string
    {
        return get_setting('whatsapp.api_version', 'v21.0');
    }

    protected static function getBaseUrl(): string
    {
        return self::$facebookAPI . self::getApiVersion() . '/';
    }

    protected function handleApiError(Throwable $e, string $operation, array $context = []): array
    {
        $errorContext = array_merge([
            'operation'  => $operation,
            'trace'      => $e->getTraceAsString(),
            'account_id' => $this->getAccountID(),
            'phone_id'   => $this->getPhoneID(),
        ], $context);

        whatsapp_log("[WhatsApp {$operation} Error] " . $e->getMessage(), 'error', $errorContext, $e);

        return [
            'status'  => false,
            'message' => config('app.debug')
                ? $e->getMessage()
                : __('whatsapp.errors.' . $operation, ['default' => 'An error occurred during ' . $operation]),
        ];
    }

    private function getToken(): string
    {
        return get_setting('whatsapp.wm_access_token');
    }

    private function getAccountID(): string
    {
        return get_setting('whatsapp.wm_business_account_id');
    }

    private function getPhoneID(): string
    {
        return get_setting('whatsapp.wm_default_phone_number_id');
    }

    private function getFBAppID(): string
    {
        return get_setting('whatsapp.wm_fb_app_id');
    }

    private function getFBAppSecret(): string
    {
        return get_setting('whatsapp.wm_fb_app_secret');
    }

    /**
     * Load WhatsApp Cloud API configuration
     *
     * @param  string|null      $fromNumber Optional phone number to use as the sender
     * @return WhatsAppCloudApi Instance of the WhatsAppCloudApi class
     */
    public function loadConfig($fromNumber = null)
    {
        return new WhatsAppCloudApi([
            'from_phone_number_id' => (! empty($fromNumber)) ? $fromNumber : $this->getPhoneID(),
            'access_token'         => $this->getToken(),
        ]);
    }

    public function getPhoneNumbers(): array
    {
        try {

            $response = Http::get(self::getBaseUrl() . "{$this->getAccountID()}/phone_numbers", [
                'access_token' => $this->getToken(),
            ]);

            if ($response->failed()) {
                throw new WhatsAppException($response->json('error.message'));
            }

            return ['status' => true, 'data' => $response->json('data')];
        } catch (Throwable $e) {
            return $this->handleApiError($e, 'get_phone_numbers');
        }
    }

    public function loadTemplatesFromWhatsApp(): array
    {
        try {
            $response = Http::get(self::getBaseUrl() . "{$this->getAccountID()}/", [
                'fields'       => 'id,name,message_templates,phone_numbers',
                'access_token' => $this->getToken(),
            ]);

            if ($response->failed()) {
                throw new WhatsAppException($response->json('error.message'));
            }

            $messageTemplates = $response->json('message_templates.data');
            if (! $messageTemplates) {
                throw new WhatsAppException('Message templates not found.');
            }

            // Get existing template IDs from database to track what should be deleted
            $existingTemplateIds = WhatsappTemplate::pluck('template_id')->toArray();
            $apiTemplateIds      = [];

            foreach ($messageTemplates as $templateData) {
                $apiTemplateIds[] = $templateData['id'];
                $template         = [
                    'template_name' => $templateData['name'],
                    'language'      => $templateData['language'],
                    'status'        => $templateData['status'],
                    'category'      => $templateData['category'],
                    'id'            => $templateData['id'],
                ];

                $components        = [];
                $headerText        = $bodyText = $footerText = $buttonsData = [];
                $headerParamsCount = $bodyParamsCount = $footerParamsCount = 0;

                foreach ($templateData['components'] as $component) {
                    if (isset($component['type']) && $component['type'] === 'HEADER') {
                        $components['TYPE'] = $component['format'];

                        if (isset($component['text'])) {
                            $headerText           = $component['text'];
                            $headerParamsCount    = preg_match_all('/{{(.*?)}}/i', $headerText, $matches);
                            $components['HEADER'] = $component['text'];
                        }
                    }

                    if (isset($component['type']) && $component['type'] === 'BODY' && isset($component['text'])) {
                        $bodyText           = $component['text'];
                        $bodyParamsCount    = preg_match_all('/{{(.*?)}}/i', $bodyText, $matches);
                        $components['BODY'] = $component['text'];
                    }

                    if (isset($component['type']) && $component['type'] === 'FOOTER' && isset($component['text'])) {
                        $footerText           = $component['text'];
                        $footerParamsCount    = preg_match_all('/{{(.*?)}}/i', $footerText, $matches);
                        $components['FOOTER'] = $component['text'];
                    }

                    if (isset($component['type']) && $component['type'] === 'BUTTONS') {
                        $components['BUTTONS'] = isset($component['buttons']) ? json_encode($component['buttons']) : null;
                    }
                }

                $template['header_data_text']    = $components['HEADER']  ?? null;
                $template['header_data_format']  = $components['TYPE']    ?? null;
                $template['body_data']           = $components['BODY']    ?? null;
                $template['footer_data']         = $components['FOOTER']  ?? null;
                $template['buttons_data']        = $components['BUTTONS'] ?? null;
                $template['header_params_count'] = $headerParamsCount;
                $template['body_params_count']   = $bodyParamsCount;
                $template['footer_params_count'] = $footerParamsCount;

                WhatsappTemplate::updateOrCreate(
                    ['template_id' => $templateData['id']],
                    $template
                );
            }

            // Delete templates that exist in database but not in API response (no longer available)
            $templatesForDeletion = array_diff($existingTemplateIds, $apiTemplateIds);
            if (! empty($templatesForDeletion)) {
                $deletedCount = WhatsappTemplate::whereIn('template_id', $templatesForDeletion)->delete();
                whatsapp_log('Deleted templates during sync', 'info', [
                    'deleted_count' => $deletedCount,
                    'template_ids'  => $templatesForDeletion,
                ]);
            }

            return [
                'status' => true,
                'data'   => $messageTemplates,
                'synced' => [
                    'updated_or_created' => count($apiTemplateIds),
                    'deleted'            => count($templatesForDeletion),
                ],
                'message' => 'Templates synced successfully',
            ];
        } catch (Throwable $e) {
            return $this->handleApiError($e, 'load_templates');
        }
    }

    public function subscribeWebhook()
    {
        $accessToken = $this->getToken();
        $accountId   = $this->getAccountID();
        $url         = self::$facebookAPI . "/$accountId/subscribed_apps?access_token=" . $accessToken;

        try {
            $response = Http::post($url);

            $data = $response->json();

            if (isset($data['error'])) {
                return [
                    'status'  => false,
                    'message' => $data['error']['message'],
                ];
            }

            return [
                'status' => true,
                'data'   => $data,
            ];
        } catch (\Throwable $th) {
            whatsapp_log('Failed to subscribe webhook: ' . $th->getMessage(), 'error', [
                'url'        => $url,
                'account_id' => $accountId,
            ], $th);

            return [
                'status'  => false,
                'message' => 'Something went wrong: ' . $th->getMessage(),
            ];
        }
    }

    public function queueMessage(WhatsAppMessage $message): array
    {
        try {
            dispatch(new SendWhatsAppMessage($message))
                ->onQueue(json_decode(get_setting('whatsapp.queue'), true)['name']);

            return ['status' => true, 'message' => 'Message queued successfully'];
        } catch (Throwable $e) {
            return $this->handleApiError($e, 'queue_message', [
                'message' => $message->toArray(),
            ]);
        }
    }

    public function debugToken(): array
    {
        try {
            $accessToken = $this->getToken();
            $response    = Http::get(self::getBaseUrl() . 'debug_token', [
                'input_token'  => $accessToken,
                'access_token' => $accessToken,
            ]);

            if ($response->failed()) {
                throw new WhatsAppException($response->json('error.message'));
            }

            return ['status' => true, 'data' => $response->json('data')];
        } catch (Throwable $e) {
            return $this->handleApiError($e, 'debug_token');
        }
    }

    public function getProfile(): array
    {
        try {
            $response = Http::get(self::getBaseUrl() . $this->getPhoneID() . '/whatsapp_business_profile', [
                'fields'       => 'profile_picture_url',
                'access_token' => $this->getToken(),
            ]);

            if ($response->failed()) {
                throw new WhatsAppException($response->json('error.message'));
            }

            return ['status' => true, 'data' => $response->json('data')];
        } catch (Throwable $e) {
            return $this->handleApiError($e, 'get_profile');
        }
    }

    public function getHealthStatus(): array
    {
        try {
            $response = Http::get(self::getBaseUrl() . $this->getAccountID(), [
                'fields'       => 'health_status',
                'access_token' => $this->getToken(),
            ]);

            if ($response->failed()) {
                throw new WhatsAppException($response->json('error.message'));
            }

            return ['status' => true, 'data' => $response->json()];
        } catch (Throwable $e) {
            return $this->handleApiError($e, 'health_status');
        }
    }

    public function getMessageLimit(): array
    {
        $startTime = strtotime(date('Y-m-d 00:00:00'));
        $endTime   = strtotime(date('Y-m-d 23:59:59'));
        try {

            $response = Http::get(self::getBaseUrl() . $this->getAccountID(), [
                'fields'       => "id,name,analytics.start({$startTime}).end({$endTime}).granularity(DAY)",
                'access_token' => $this->getToken(),
            ]);

            if ($response->failed()) {
                throw new WhatsAppException($response->json('error.message'));
            }

            return ['status' => true, 'data' => $response->json()];
        } catch (Throwable $e) {
            return $this->handleApiError($e, 'health_status');
        }
    }

    public function generateUrlQR(string $url, ?string $logo = null): bool
    {
        try {
            $writer = new PngWriter;

            $qrCode = new QrCode(
                data: $url,
                encoding: new Encoding('UTF-8'),
                errorCorrectionLevel: ErrorCorrectionLevel::Low,
                size: 300,
                margin: 10,
                roundBlockSizeMode: RoundBlockSizeMode::Margin,
                foregroundColor: new Color(0, 0, 0),
                backgroundColor: new Color(255, 255, 255)
            );

            if ($logo) {
                $logo = new Logo(
                    path: public_path('img/whatsapp.png'),
                    resizeToWidth: 50,
                    punchoutBackground: true
                );
            }

            // Create generic label
            $label = new Label(
                text: '',
                textColor: new Color(255, 0, 0)
            );

            // Generate the QR code
            $result = $writer->write($qrCode, $logo, $label);

            create_storage_link();

            // Define the path to save the file
            $filePath = storage_path('app/public/images/qrcode.png');

            // Ensure the directory exists
            if (! file_exists(dirname($filePath))) {
                mkdir(dirname($filePath), 0755, true);
            }

            // Save the QR code to the file
            $result->saveToFile($filePath);

            return true;
        } catch (Throwable $e) {
            whatsapp_log('Error generating QR code: ' . $e->getMessage(), 'error', [
                'url'  => $url,
                'logo' => $logo,
            ], $e);

            return false;
        }
    }

    public function connectWebhook()
    {
        $appId     = $this->getFBAppID();
        $appSecret = $this->getFBAppSecret();

        try {
            $url = self::$facebookAPI . $appId . '/subscriptions?access_token=' . $appId . '|' . $appSecret;

            $response = Http::post($url, [
                'object'       => 'whatsapp_business_account',
                'fields'       => 'messages,message_template_quality_update,message_template_status_update,account_update',
                'callback_url' => route('whatsapp.webhook'),
                'verify_token' => get_setting('whatsapp.webhook_verify_token'),
            ]);

            $data = $response->json();

            if (isset($data['error'])) {
                return [
                    'status'  => false,
                    'message' => $data['error']['message'],
                ];
            }

            return [
                'status' => true,
                'data'   => $data,
            ];
        } catch (\Throwable $th) {
            whatsapp_log('Error connecting webhook: ' . $th->getMessage(), 'error', [], $th);

            return [
                'status'  => false,
                'message' => 'Something went wrong: ' . $th->getMessage(),
            ];
        }
    }

    public function disconnectWebhook()
    {
        $appId     = $this->getFBAppID();
        $appSecret = $this->getFBAppSecret();

        $url = self::$facebookAPI . $appId . '/subscriptions?access_token=' . $appId . '|' . $appSecret;

        try {
            $response = Http::delete($url, [], [
                'object' => 'whatsapp_business_account',
                'fields' => 'messages,message_template_quality_update,message_template_status_update,account_update',
            ]);

            $data = $response->json();

            if (isset($data['error'])) {
                return [
                    'status'  => false,
                    'message' => $data['error']['message'],
                ];
            }

            return [
                'status' => true,
                'data'   => $data,
            ];
        } catch (\Throwable $th) {
            whatsapp_log('Error disconnecting webhook: ' . $th->getMessage(), 'error', [], $th);

            return [
                'status'  => false,
                'message' => 'Something went wrong: ' . $th->getMessage(),
            ];
        }
    }

    public function sendTestMessages($number)
    {
        $whatsapp_cloud_api = $this->loadConfig();

        try {
            $result       = $whatsapp_cloud_api->sendTemplate($number, 'hello_world', 'en_US');
            $status       = true;
            $message      = t('whatsapp_message_sent_successfully');
            $data         = json_decode($result->body());
            $responseCode = $result->httpStatusCode();
        } catch (\Netflie\WhatsAppCloudApi\Response\ResponseException $th) {
            $status       = false;
            $message      = $th->responseData()['error']['message'] ?? $th->rawResponse() ?? json_decode($th->getMessage());
            $responseCode = $th->httpStatusCode();

            whatsapp_log('Error sending test message: ' . $message, 'error', [
                'number'        => $number,
                'response_code' => $responseCode,
            ], $th);
        }

        return ['status' => $status, 'message' => $message ?? ''];
    }

    public function checkServiceHealth(): array
    {
        try {
            $healthData = [
                'api_status'      => $this->getHealthStatus(),
                'queue_size'      => Queue::size(json_decode(get_setting('whatsapp.queue'), true)['name']),
                'daily_api_calls' => Cache::get('whatsapp_api_calls_' . now()->format('Y-m-d')),
                'token_status'    => $this->debugToken(),
                'profile_status'  => $this->getProfile(),
            ];

            whatsapp_log(
                'WhatsApp service health check',
                'info',
                $healthData
            );

            return ['status' => true, 'data' => $healthData];
        } catch (Throwable $e) {
            return $this->handleApiError($e, 'health_check');
        }
    }

    protected function getExtensionForType(string $mimeType): ?string
    {
        return self::$extensionMap[$mimeType] ?? null;
    }

    /**
     * Send a template message using the WhatsApp Cloud API
     *
     * @param  string      $to            Recipient phone number
     * @param  array       $template_data Data for the template message
     * @param  string      $type          Type of the message, default is 'campaign'
     * @param  string|null $fromNumber    Optional sender phone number
     * @return array       Response containing status, log data, and any response data or error message
     */
    public function sendTemplate($to, $template_data, $type = 'campaign', $fromNumber = null)
    {
        $rel_type    = $template_data['rel_type'];
        $header_data = [];
        if ($template_data['header_data_format'] == 'TEXT') {
            $header_data = parseText($rel_type, 'header', $template_data, 'array');
        }
        $body_data    = parseText($rel_type, 'body', $template_data, 'array');
        $buttons_data = parseText($rel_type, 'footer', $template_data, 'array');

        $component_header = $component_body = $component_buttons = [];
        $file_link        = asset('storage/' . $template_data['filename']);

        $component_header  = $this->buildHeaderComponent($template_data, $file_link, $header_data);
        $component_body    = $this->buildTextComponent($body_data);
        $component_buttons = $this->buildTextComponent($buttons_data);

        $whatsapp_cloud_api = $this->loadConfig($fromNumber);

        try {
            $components   = new Component($component_header, $component_body, $component_buttons);
            $result       = $whatsapp_cloud_api->sendTemplate($to, $template_data['template_name'], $template_data['language'], $components);
            $status       = true;
            $data         = json_decode($result->body());
            $responseCode = $result->httpStatusCode();
            $responseData = json_encode($result->decodedBody());
            $rawData      = json_encode($result->request()->body());
        } catch (\Netflie\WhatsAppCloudApi\Response\ResponseException $th) {
            $status       = false;
            $message      = $th->responseData()['error']['message'] ?? $th->rawResponse() ?? json_decode($th->getMessage());
            $responseCode = $th->httpStatusCode();
            $responseData = json_encode($message);
            $rawData      = json_encode([]);

            whatsapp_log('Error sending template: ' . $message, 'error', [
                'to'            => $to,
                'template_name' => $template_data['template_name'],
                'language'      => $template_data['language'],
                'response_code' => $responseCode,
                'response_data' => $responseData,
                'raw_data'      => $rawData,
            ], $th);
        }

        $log_data = [
            'response_code'       => $responseCode,
            'category'            => $type,
            'category_id'         => $template_data['campaign_id'] ?? $template_data['template_bot_id'],
            'rel_type'            => $rel_type,
            'rel_id'              => $template_data['rel_id'],
            'category_params'     => json_encode(['templateId' => $template_data['template_id'], 'message' => $message ?? '']),
            'response_data'       => $responseData,
            'raw_data'            => $rawData,
            'phone_number_id'     => get_setting('whatsapp.wm_default_phone_number_id'),
            'access_token'        => get_setting('whatsapp.wm_access_token'),
            'business_account_id' => get_setting('whatsapp.wm_business_account_id'),
        ];

        WmActivityLog::create($log_data);

        return ['status' => $status, 'log_data' => $log_data, 'data' => $data ?? [], 'message' => $message->error->message ?? ''];
    }

    /**
     * Send a message using the WhatsApp Cloud API
     *
     * @param  string      $to           Recipient phone number
     * @param  array       $message_data Data for the message
     * @param  string|null $fromNumber   Optional sender phone number
     * @return array       Response containing status, log data, and any response data or error message
     */
    public function sendMessage($to, $message_data, $fromNumber = null, $folder = 'bot_files')
    {
        $message_data       = parseMessageText($message_data);
        $whatsapp_cloud_api = $this->loadConfig($fromNumber);

        try {
            $rows = [];
            if (! empty($message_data['button1_id'])) {
                $rows[] = new Button($message_data['button1_id'], $message_data['button1']);
            }
            if (! empty($message_data['button2_id'])) {
                $rows[] = new Button($message_data['button2_id'], $message_data['button2']);
            }
            if (! empty($message_data['button3_id'])) {
                $rows[] = new Button($message_data['button3_id'], $message_data['button3']);
            }
            if (! empty($rows)) {
                $action = new ButtonAction($rows);
                $result = $whatsapp_cloud_api->sendButton(
                    $to,
                    $message_data['reply_text'],
                    $action,
                    $message_data['bot_header'],
                    $message_data['bot_footer']
                );
            } elseif (! empty($message_data['button_name']) && ! empty($message_data['button_url']) && filter_var($message_data['button_url'], \FILTER_VALIDATE_URL)) {
                $header = new TitleHeader($message_data['bot_header']);

                $result = $whatsapp_cloud_api->sendCtaUrl(
                    $to,
                    $message_data['button_name'],
                    $message_data['button_url'],
                    $header,
                    $message_data['reply_text'],
                    $message_data['bot_footer'],
                );
            } else {
                $message = $message_data['bot_header'] . "\n" . $message_data['reply_text'] . "\n" . $message_data['bot_footer'];
                if (! empty($message_data['filename'])) {
                    $url            = asset('storage/' . $message_data['filename']);
                    $link_id        = new LinkID($url);
                    $fileExtensions = get_meta_allowed_extension();
                    $extension      = strtolower(pathinfo($message_data['filename'], PATHINFO_EXTENSION));
                    $fileType       = array_key_first(array_filter($fileExtensions, fn ($data) => in_array('.' . $extension, explode(', ', $data['extension']))));
                    if ($fileType == 'image') {
                        $result = $whatsapp_cloud_api->sendImage($to, $link_id, $message);
                    } elseif ($fileType == 'video') {
                        $result = $whatsapp_cloud_api->sendVideo($to, $link_id, $message);
                    } elseif ($fileType == 'document') {
                        $result = $whatsapp_cloud_api->sendDocument($to, $link_id, $message_data['filename'], $message);
                    }
                } else {
                    $result = $whatsapp_cloud_api->sendTextMessage($to, $message, true);
                }
            }

            $status       = true;
            $data         = json_decode($result->body());
            $responseCode = $result->httpStatusCode();
            $responseData = $data;
            $rawData      = json_encode($result->request()->body());
        } catch (\Netflie\WhatsAppCloudApi\Response\ResponseException $th) {
            $status       = false;
            $message      = $th->responseData()['error']['message'] ?? $th->rawResponse() ?? $th->getMessage();
            $responseCode = $th->httpStatusCode();
            $responseData = $message;
            $rawData      = json_encode([]);
        }

        $log_data['response_code']   = $responseCode;
        $log_data['category']        = $folder == 'bot_files' ? 'message_bot' : '';
        $log_data['category_id']     = $message_data['id'];
        $log_data['rel_type']        = $message_data['rel_type'];
        $log_data['rel_id']          = ' - ';
        $log_data['category_params'] = json_encode(['message' => $message ?? '']);
        $log_data['response_data']   = ! empty($responseData) ? json_encode($responseData) : '';
        $log_data['raw_data']        = $rawData;

        $log_data['phone_number_id']     = get_setting('whatsmark.wm_default_phone_number_id');
        $log_data['access_token']        = get_setting('whatsmark.wm_access_token');
        $log_data['business_account_id'] = get_setting('whatsmark.wm_business_account_id');

        WmActivityLog::create($log_data);

        return ['status' => $status, 'log_data' => $log_data ?? [], 'data' => $data ?? [], 'message' => $message->error->message ?? ''];
    }

    /**
     * Send bulk campaign to WhatsApp recipients
     *
     * @param  string      $to           Recipient phone number
     * @param  array       $templateData Template configuration
     * @param  array       $campaign     Campaign data
     * @param  string|null $fromNumber   Sender phone number (optional)
     * @return array       Response data
     */
    public function sendBulkCampaign($to, $templateData, $campaign, $fromNumber = null)
    {
        try {
            // Parse template data for header, body, and buttons
            $headerData = [];
            if ($templateData['header_data_format'] == 'TEXT') {
                $headerData = parseCsvText('header', $templateData, $campaign);
            }

            $bodyData    = parseCsvText('body', $templateData, $campaign);
            $buttonsData = parseCsvText('footer', $templateData, $campaign);

            // Get file link if available
            $fileLink = ($templateData['filename']) ? asset('storage/' . $templateData['filelink']) : '';

            // Build components for WhatsApp message
            $componentHeader  = $this->buildHeaderComponent($templateData, $fileLink, $headerData);
            $componentBody    = $this->buildTextComponent($bodyData);
            $componentButtons = $this->buildTextComponent($buttonsData);

            // Load WhatsApp API configuration
            $whatsappCloudApi = $this->loadConfig($fromNumber);

            // Create components object and send template
            $components = new Component($componentHeader, $componentBody, $componentButtons);
            $result     = $whatsappCloudApi->sendTemplate(
                $to,
                $templateData['template_name'],
                $templateData['language'],
                $components
            );

            return [
                'status'       => true,
                'data'         => json_decode($result->body(), true),
                'responseCode' => $result->httpStatusCode(),
                'message'      => '',
                'phone'        => $to,
            ];
        } catch (ResponseException $e) {

            whatsapp_log('WhatsApp API Error: ' . $e->getMessage(), 'error', [
                'phone'         => $to,
                'template'      => $templateData['template_name'],
                'response_code' => $e->httpStatusCode(),
                'response_data' => $e->responseData() ?? [],
            ], $e);

            return [
                'status'       => false,
                'data'         => [],
                'responseCode' => $e->httpStatusCode(),
                'message'      => $e->responseData()['error']['message'] ?? $e->getMessage(),
                'phone'        => $to,
            ];
        } catch (\Exception $e) {

            whatsapp_log('WhatsApp Campaign Error: ' . $e->getMessage(), 'error', [
                'phone'         => $to,
                'template'      => $templateData['template_name'] ?? 'unknown',
                'response_code' => 500,
            ], $e);

            return [
                'status'       => false,
                'data'         => [],
                'responseCode' => 500,
                'message'      => $e->getMessage(),
                'phone'        => $to,
            ];
        }
    }

    /**
     * Retry sending a campaign message with exponential backoff
     *
     * @param  string      $to           Recipient phone number
     * @param  array       $templateData Template configuration
     * @param  array       $campaign     Campaign data
     * @param  string|null $fromNumber   Sender phone number (optional)
     * @param  int         $maxRetries   Maximum number of retry attempts
     * @return array       Response data
     */
    public function sendWithRetry($to, $templateData, $campaign, $fromNumber = null, $maxRetries = 3)
    {
        $attempt = 0;
        $result  = null;

        while ($attempt < $maxRetries) {
            $result = $this->sendBulkCampaign($to, $templateData, $campaign, $fromNumber);

            // If successful or not a retryable error, break the loop
            if ($result['status'] || ! $this->isRetryableError($result['responseCode'])) {
                break;
            }

            // Exponential backoff: wait longer between each retry
            $waitTime = pow(2, $attempt) * 1000000; // in microseconds (1s, 2s, 4s)
            usleep($waitTime);
            $attempt++;

            whatsapp_log("Retrying WhatsApp message to {$to} (attempt {$attempt})", 'info', [
                'phone'         => $to,
                'template'      => $templateData['template_name'] ?? 'unknown',
                'attempt'       => $attempt,
                'response_code' => $result['responseCode'] ?? null,
                'response_data' => $result['data']         ?? [],
            ]);
        }

        return $result;
    }

    /**
     * Check if an error is retryable
     *
     * @param  int  $statusCode HTTP status code
     * @return bool Whether the error is retryable
     */
    protected function isRetryableError($statusCode)
    {
        // Retry on rate limiting, server errors, and certain client errors
        return in_array($statusCode, [408, 429, 500, 502, 503, 504]);
    }

    /**
     * Handle batch processing for large campaigns
     *
     * @param  array $recipients   List of recipients
     * @param  array $templateData Template configuration
     * @param  int   $batchSize    Batch size (default: 50)
     * @return array Results for each recipient
     */
    public function processBatchCampaign($recipients, $templateData, $batchSize = 50)
    {
        $results = [];
        $batches = array_chunk($recipients, $batchSize);

        foreach ($batches as $batch) {
            foreach ($batch as $recipient) {
                $to        = $recipient['phone'];
                $result    = $this->sendBulkCampaign($to, $templateData, $recipient);
                $results[] = $result;
            }

            // Add a small delay between batches to avoid rate limiting
            if (count($batches) > 1) {
                usleep(500000);
            }
        }

        return $results;
    }

    private function buildHeaderComponent($templateData, $fileLink, $headerData)
    {
        return match ($templateData['header_data_format']) {
            'IMAGE'    => [['type' => 'image', 'image' => ['link' => $fileLink]]],
            'DOCUMENT' => [['type' => 'document', 'document' => ['link' => $fileLink, 'filename' => $templateData['filename']]]],
            'VIDEO'    => [['type' => 'video', 'video' => ['link' => $fileLink]]],
            default    => collect($headerData)->map(fn ($header) => ['type' => 'text', 'text' => $header])->toArray(),
        };
    }

    private function buildTextComponent($data)
    {
        return collect($data)->map(fn ($text) => ['type' => 'text', 'text' => $text])->toArray();
    }

    /**
     * Retrieve a URL for a media file using its media ID
     *
     * @param  string      $media_id    Media ID to retrieve the URL for
     * @param  string      $accessToken Access token for authentication
     * @return string|null Filename of the saved media file or null on failure
     */
    public function retrieveUrl($media_id)
    {
        $url         = self::$facebookAPI . $media_id;
        $accessToken = $this->getToken();

        $response = Http::withToken($accessToken)->get($url);

        if ($response->successful()) {
            $responseData = $response->json();

            if (isset($responseData['url'])) {
                $mediaUrl  = $responseData['url'];
                $mediaData = Http::withToken($accessToken)->get($mediaUrl);

                if ($mediaData->successful()) {
                    $imageContent = $mediaData->body();
                    $contentType  = $mediaData->header('Content-Type');

                    $extensionMap = self::$extensionMap;
                    $extension    = $extensionMap[$contentType] ?? 'unknown';
                    $filename     = 'media_' . uniqid() . '.' . $extension;
                    $storagePath  = 'whatsapp-attachments/' . $filename;

                    Storage::disk('public')->put($storagePath, $imageContent);

                    return $filename;
                }
            }
        }

        return null;
    }
}
