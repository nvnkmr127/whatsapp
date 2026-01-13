<?php

namespace App\Http\Controllers\WhatsApp;

use App\Http\Controllers\Controller;
use App\Models\CampaignDetail;
use App\Models\Chat;
use App\Models\ChatMessage;
use App\Models\Contact;
use App\Models\MessageBots;
use App\Models\TemplateBot;
use App\Services\PusherService;
use App\Traits\WhatsApp;
use Illuminate\Http\Request;
use Netflie\WhatsAppCloudApi\Message\Media\LinkID;
use Netflie\WhatsAppCloudApi\WhatsAppCloudApi;

class WhatsAppWebhookController extends Controller
{
    use WhatsApp;

    public $is_first_time = false;

    public $is_bot_stop = false;

    /**
     * Handle incoming WhatsApp webhook requests
     *
     * @return \Illuminate\Http\Response
     */
    public function __invoke(Request $request)
    {
        whatsapp_log(
            'Webhook Received',
            'debug',
            [
                'method'           => $request->method(),
                'full_url'         => $request->fullUrl(),
                'hub_mode'         => $request->input('hub_mode'),
                'hub_verify_token' => $request->input('hub_verify_token'),
            ]
        );

        // WhatsApp Webhook Verification
        if (isset($_GET['hub_mode']) && isset($_GET['hub_challenge']) && isset($_GET['hub_verify_token'])) {
            // Retrieve verify token from settings
            $verifyToken = get_setting('whatsapp.webhook_verify_token');

            whatsapp_log(
                'Webhook Verification Attempt',
                'debug',
                [
                    'received_token' => $_GET['hub_verify_token'],
                    'expected_token' => $verifyToken,
                    'hub_mode'       => $_GET['hub_mode'],
                    'hub_challenge'  => $_GET['hub_challenge'],
                ]
            );

            // Verify the webhook
            if ($_GET['hub_verify_token'] == $verifyToken && $_GET['hub_mode'] == 'subscribe') {
                whatsapp_log(
                    'Webhook Verification Successful',
                    'info'
                );

                // Directly output the challenge with proper headers
                header('Content-Type: text/plain');
                echo $_GET['hub_challenge'];
                exit;
            } else {
                whatsapp_log(
                    'Webhook Verification Failed',
                    'warning',
                    [
                        'received_token' => $_GET['hub_verify_token'],
                        'hub_mode'       => $_GET['hub_mode'],
                    ]
                );

                // Send 403 Forbidden with a clear error message
                header('HTTP/1.1 403 Forbidden');
                header('Content-Type: text/plain');
                echo 'Verification failed: Invalid token or mode';
                exit;
            }
        }

        // Process webhook payload for messages and statuses
        $this->processWebhookPayload();
    }

    /**
     * Process incoming webhook payload
     */
    protected function processWebhookPayload()
    {
        $feedData = file_get_contents('php://input');

        whatsapp_log(
            'Webhook Payload Received',
            'info',
            [
                'payload' => $feedData,
            ]
        );

        if (! empty($feedData)) {

            $payload = json_decode($feedData, true);

            // Special ping message handling
            if (isset($payload['message']) && $payload['message'] === 'ctl_whatsbot_ping' && isset($payload['identifier'])) {
                whatsapp_log(
                    'Whatsmark Ping Received',
                    'debug'
                );
                echo json_encode(['status' => true, 'message' => 'Webhook verified']);

                return;
            }

            // Check for message ID to prevent duplicate processing
            $message_id = $payload['entry'][0]['changes'][0]['value']['messages'][0]['id'] ?? '';
            if (! empty($message_id)) {
                // Check if message already processed (similar to original code)
                $found = $this->checkMessageProcessed($message_id);
                if ($found) {
                    whatsapp_log(
                        'Duplicate Message Detected',
                        'warning',
                        [
                            'message_id' => $message_id,
                        ]
                    );

                    return;
                }
            }

            // Process the payload
            $this->processPayloadData($payload);

            // Forward webhook data if enabled
            $this->forwardWebhookData($feedData, $payload);
        }
    }

    /**
     * Check if message has already been processed
     */
    protected function checkMessageProcessed(string $messageId): bool
    {
        // Implement logic to check if message is already in database
        // Similar to original CI code: check in 'wtc_interaction_messages' table
        return \DB::table('chat_messages')
            ->where('message_id', $messageId)
            ->exists();
    }

    /**
     * Process payload data
     */
    protected function processPayloadData(array $payload)
    {
        whatsapp_log(
            'Processing Payload Data',
            'info',
            [
                'payload_entries' => count($payload['entry']),
            ]
        );

        // Extract entry and changes
        $entry   = array_shift($payload['entry']);
        $changes = array_shift($entry['changes']);
        $value   = $changes['value'];

        // Process messages or statuses
        if (isset($value['messages'])) {
            $this->processIncomingMessages($value);
            $this->processBotSending($value);
        } elseif (isset($value['statuses'])) {
            $this->processMessageStatuses($value['statuses']);
        }
    }

    private function processBotSending(array $message_data)
    {
        if (! empty($message_data['messages'])) {
            $message     = reset($message_data['messages']);
            $trigger_msg = isset($message['button']['text']) ? $message['button']['text'] : $message['text']['body'] ?? '';
            if (! empty($message['interactive']) && $message['interactive']['type'] == 'button_reply') {
                $trigger_msg = $message['interactive']['button_reply']['id'];
            }
            if (! empty($trigger_msg)) {
                $contact  = reset($message_data['contacts']);
                $metadata = $message_data['metadata'];
            }

            try {
                $contact_number = $message['from'];
                $contact_data   = $this->getContactData($contact_number, $contact['profile']['name']);

                $query_trigger_msg = $trigger_msg;
                $reply_type        = null;
                if ($this->is_first_time) {
                    $query_trigger_msg = '';
                    $reply_type        = 3;
                }

                $current_interaction = Chat::where(['type' => $contact_data->type, 'type_id' => $contact_data->id, 'wa_no' => $message_data['metadata']['display_phone_number']])->first();

                if ($current_interaction->is_bots_stoped == 1 && (time() > strtotime($current_interaction->bot_stoped_time) + ((int) get_setting('whats-mark.restart_bots_after') * 3600))) {
                    Chat::where('id', $current_interaction->id)->update(['bot_stoped_time' => null, 'is_bots_stoped' => '0']);
                    $this->is_bot_stop = false;
                } elseif ($current_interaction->is_bots_stoped == 1) {
                    $this->is_bot_stop = true;
                }

                if (collect(get_setting('whats-mark.stop_bots_keyword'))->first(fn ($keyword) => str_contains($trigger_msg, $keyword))) {
                    Chat::where('id', $current_interaction->id)->update(['bot_stoped_time' => date('Y-m-d H:i:s'), 'is_bots_stoped' => '1']);
                    $this->is_bot_stop = true;
                }

                if (! $this->is_bot_stop) {
                    // Fetch template and message bots based on interaction
                    $template_bots = TemplateBot::getTemplateBotsByRelType($contact_data->type ?? '', $query_trigger_msg, $reply_type);
                    $message_bots  = MessageBots::getMessageBotsbyRelType($contact_data->type ?? '', $query_trigger_msg, $reply_type);

                    if (empty($template_bots) && empty($message_bots)) {
                        $template_bots = TemplateBot::getTemplateBotsByRelType($contact_data->type ?? '', $query_trigger_msg, 4);
                        $message_bots  = MessageBots::getMessageBotsbyRelType($contact_data->type ?? '', $query_trigger_msg, 4);
                    }

                    $add_messages = function ($item) {
                        $item['header_message'] = $item['header_data_text'];
                        $item['body_message']   = $item['body_data'];
                        $item['footer_message'] = $item['footer_data'];

                        return $item;
                    };

                    $template_bots = array_map($add_messages, $template_bots);

                    // Iterate over template bots
                    foreach ($template_bots as $template) {
                        $template['rel_id'] = $contact_data->id;
                        if (! empty($contact_data->userid)) {
                            $template['userid'] = $contact_data->userid;
                        }

                        // Send template on exact match, contains, or first time
                        if ((1 == $template['reply_type'] && in_array(strtolower($trigger_msg), array_map('trim', array_map('strtolower', explode(',', $template['trigger']))))) || (2 == $template['reply_type'] && ! empty(array_filter(explode(',', strtolower($template['trigger'])), fn ($word) => preg_match('/\b' . preg_quote(trim($word), '/') . '\b/', strtolower($trigger_msg))))) || (3 == $template['reply_type'] && $this->is_first_time) || 4 == $template['reply_type']) {
                            $response    = $this->sendTemplate($contact_number, $template, 'template_bot', $metadata['phone_number_id']);
                            $chatId      = $this->createOrUpdateInteraction($contact_number, $message_data['metadata']['display_phone_number'], $message_data['metadata']['phone_number_id'], $contact_data->firstname . ' ' . $contact_data->lastname, '', '', false);
                            $chatMessage = $this->storeBotMessages($template, $chatId, $contact_data, 'template_bot', $response);
                        }
                    }

                    // Iterate over message bots
                    foreach ($message_bots as $message) {
                        $message['rel_id'] = $contact_data->id;
                        if (! empty($contact_data->userid)) {
                            $message['userid'] = $contact_data->userid;
                        }
                        if ((1 == $message['reply_type'] && in_array(strtolower($trigger_msg), array_map('trim', array_map('strtolower', explode(',', $message['trigger']))))) || (2 == $message['reply_type'] && ! empty(array_filter(explode(',', strtolower($message['trigger'])), fn ($word) => preg_match('/\b' . preg_quote(trim($word), '/') . '\b/', strtolower($trigger_msg))))) || (3 == $message['reply_type'] && $this->is_first_time) || 4 == $message['reply_type']) {
                            $response    = $this->sendMessage($contact_number, $message, $metadata['phone_number_id']);
                            $chatId      = $this->createOrUpdateInteraction($contact_number, $message_data['metadata']['display_phone_number'], $message_data['metadata']['phone_number_id'], $contact_data->firstname . ' ' . $contact_data->lastname, '', '', false);
                            $chatMessage = $this->storeBotMessages($message, $chatId, $contact_data, '', $response);
                        }
                    }
                }
            } catch (\Throwable $th) {
                file_put_contents(base_path() . '/errors.json', json_encode([$th->getMessage()]));
            }
        }
    }

    /**
     * Process incoming messages
     */
    protected function processIncomingMessages(array $value)
    {
        $messageEntry   = array_shift($value['messages']);
        $contact        = array_shift($value['contacts']) ?? '';
        $name           = $contact['profile']['name']     ?? '';
        $from           = $messageEntry['from'];
        $metadata       = $value['metadata'];
        $wa_no          = $metadata['display_phone_number'];
        $wa_no_id       = $metadata['phone_number_id'];
        $messageType    = $messageEntry['type'];
        $message_id     = $messageEntry['id'];
        $ref_message_id = isset($messageEntry['context']) ? $messageEntry['context']['id'] : '';

        // Determine if this is a first-time interaction
        $this->is_first_time = $this->isFirstTimeInteraction($from);

        // Extract message content based on type
        $message = $this->extractMessageContent($messageEntry, $messageType);
        if ($messageType == 'image' || $messageType == 'audio' || $messageType == 'document' || $messageType == 'video') {
            $media_id   = $messageEntry[$messageType]['id'];
            $attachment = $this->retrieveUrl($media_id);
        }

        whatsapp_log(
            'Processing Incoming Message',
            'info',
            [
                'from'          => $from,
                'name'          => $name,
                'message_type'  => $messageType,
                'is_first_time' => $this->is_first_time,
            ]
        );

        // Create or update interaction
        $interaction_id = $this->createOrUpdateInteraction(
            $from,
            $wa_no,
            $wa_no_id,
            $name,
            $message,
            $messageType
        );

        // Store interaction message
        $message_id = $this->storeInteractionMessage(
            $interaction_id,
            $from,
            $message_id,
            $message,
            $messageType,
            $ref_message_id,
            $metadata,
            $attachment ?? ''
        );

        if (! empty(get_setting('pusher.app_key')) && ! empty(get_setting('pusher.app_secret')) && ! empty(get_setting('pusher.app_id')) && ! empty(get_setting('pusher.cluster'))) {
            $pusherService = new PusherService;
            $pusherService->trigger('whatsmark-chat-channel', 'whatsmark-chat-event', [
                'chat' => ChatController::newChatMessage($interaction_id, $message_id),
            ]);
        }
    }

    /**
     * Check if this is a first-time interaction
     */
    protected function isFirstTimeInteraction(string $from): bool
    {
        return ! (bool) Chat::where('receiver_id', $from)->count();
    }

    /**
     * Extract message content based on type
     */
    protected function extractMessageContent(array $messageEntry, string $messageType): string
    {
        switch ($messageType) {
            case 'text':
                return $messageEntry['text']['body'] ?? '';
            case 'interactive':
                return $messageEntry['interactive']['button_reply']['title'] ?? '';
            case 'button':
                return $messageEntry['button']['text'] ?? '';
            case 'reaction':
                return json_decode('"' . ($messageEntry['reaction']['emoji'] ?? '') . '"', false, 512, JSON_UNESCAPED_UNICODE);
            case 'image':
            case 'audio':
            case 'document':
            case 'video':
                return $messageType;
            default:
                return 'Unknown message type';
        }
    }

    /**
     * Create or update interaction
     */
    protected function createOrUpdateInteraction(
        string $from,
        string $wa_no,
        string $wa_no_id,
        string $name,
        string $message,
        string $messageType,
        bool $enableTime = true
    ): int {
        // Retrieve contact data (similar to original implementation)
        $contact_data = $this->getContactData($from, $name);

        // Check if a record with the same receiver_id exists
        $existingChat = Chat::where('receiver_id', $from)->first();

        if ($existingChat) {

            Chat::where('id', $existingChat->id)->update([
                'wa_no'         => $wa_no,
                'wa_no_id'      => $wa_no_id,
                'name'          => $name,
                'last_message'  => $message,
                'last_msg_time' => now(),
                'type'          => $contact_data->type ?? 'guest',
                'type_id'       => $contact_data->id   ?? '',
                'updated_at'    => now(),
            ] + ($enableTime ? ['time_sent' => now()] : []));

            return $existingChat->id;
        } else {

            return Chat::insertGetId([
                'receiver_id'   => $from,
                'wa_no'         => $wa_no,
                'wa_no_id'      => $wa_no_id,
                'name'          => $name,
                'last_message'  => $message,
                'agent'         => json_encode(['assign_id' => $contact_data->assigned_id ?? 0, 'agents_id' => '']),
                'time_sent'     => now(),
                'last_msg_time' => now(),
                'type'          => $contact_data->type ?? 'guest',
                'type_id'       => $contact_data->id   ?? '',
                'created_at'    => now(),
                'updated_at'    => now(),
            ]);
        }
    }

    /**
     * Store interaction message
     */
    protected function storeInteractionMessage(
        int $interaction_id,
        string $from,
        string $message_id,
        string $message,
        string $messageType,
        string $ref_message_id,
        array $metadata,
        string $url = ''
    ) {
        return ChatMessage::insertGetId([
            'interaction_id' => $interaction_id,
            'sender_id'      => $from,
            'message_id'     => $message_id,
            'message'        => $message,
            'type'           => $messageType,
            'staff_id'       => null,
            'status'         => 'sent',
            'time_sent'      => now(),
            'ref_message_id' => $ref_message_id,
            'created_at'     => now(),
            'updated_at'     => now(),
            'url'            => $url,
        ]);
    }

    /**
     * Process message statuses
     */
    protected function processMessageStatuses(array $statuses)
    {
        foreach ($statuses as $status) {
            $id           = $status['id'];
            $status_value = $status['status'];

            $status_message = null;
            $errors         = $status['errors'] ?? [];

            $error_data = array_column($errors, 'error_data');
            $details    = array_column($error_data, 'details');

            $status_message = reset($details) ?: null;

            // Update chat message
            CampaignDetail::where('whatsapp_id', $id)->update(['message_status' => $status_value, 'response_message' => $status_message]);
            $message = ChatMessage::where('message_id', $id)->first();

            if ($message) {
                $message->update([
                    'status'         => $status_value,
                    'status_message' => $status_message,
                    'updated_at'     => now(),
                ]);

                if (! empty(get_setting('pusher.app_key')) && ! empty(get_setting('pusher.app_secret')) && ! empty(get_setting('pusher.app_id')) && ! empty(get_setting('pusher.cluster'))) {
                    $pusherService = new PusherService;
                    $pusherService->trigger('whatsmark-chat-channel', 'whatsmark-chat-event', [
                        'chat' => ChatController::newChatMessage($message->interaction_id, $message->id),
                    ]);
                }
            }
        }
    }

    /**
     * Forward webhook data if enabled
     */
    protected function forwardWebhookData(string $feedData, array $payload)
    {
        if (get_setting('whats-mark.enable_webhook_resend') && filter_var(get_setting('whats-mark.whatsapp_data_resend_to'), FILTER_VALIDATE_URL)) {
            try {
                $forwardMethod = get_setting('whats-mark.webhook_resend_method', 'POST');
                $response      = \Http::send($forwardMethod, get_setting('whats-mark.whatsapp_data_resend_to'), [
                    'body' => $forwardMethod === 'POST' ? $feedData : $payload,
                ]);

                whatsapp_log(
                    'Webhook resend data',
                    'info',
                    [
                        'status' => $response->status(),
                        'data'   => $response->body(),
                    ]
                );
            } catch (\Exception $e) {

                whatsapp_log(
                    'Webhook Forward Error',
                    'error',
                    [
                        'message' => $e->getMessage(),
                    ],
                    $e
                );
            }
        }
    }

    /**
     * Get contact data (placeholder method)
     */
    protected function getContactData(string $from, string $name): object
    {
        $contact = Contact::whereRaw('phone = ? OR phone = ?', ['+' . $from, $from])->first();
        if ($contact) {
            return $contact;
        }
        if (get_setting('whats-mark.auto_lead_enabled')) {
            $name    = explode(' ', $name);
            $contact = Contact::create([
                'firstname'   => $name[0],
                'lastname'    => count($name) > 1 ? implode(' ', array_slice($name, 1)) : '',
                'type'        => 'lead',
                'phone'       => $from[0] === '+' ? $from : '+' . $from,
                'assigned_id' => get_setting('whats-mark.lead_assigned_to'),
                'status_id'   => get_setting('whats-mark.lead_status'),
                'source_id'   => get_setting('whats-mark.lead_source'),
                'addedfrom'   => '0',
            ]);

            return $contact;
        }

        return (object) [];
    }

    public function storeBotMessages($data, $interactionId, $relData, $type, $response)
    {
        $data['sending_count'] = (int) $data['sending_count'] + 1;

        if ($type == 'template_bot') {
            $header = parseText($data['rel_type'], 'header', $data);
            $body   = parseText($data['rel_type'], 'body', $data);
            $footer = parseText($data['rel_type'], 'footer', $data);

            $buttonHtml = '';
            if (! empty(json_decode($data['buttons_data']))) {
                $buttons    = json_decode($data['buttons_data']);
                $buttonHtml = "<div class='flex flex-col mt-2 space-y-2'>";
                foreach ($buttons as $button) {
                    $buttonHtml .= "<button class='bg-gray-100 text-green-500 px-3 py-2 rounded-lg flex items-center justify-center text-xs space-x-2 w-full
                        dark:bg-gray-800 dark:text-green-400'>" . e($button->text) . '</button>';
                }
                $buttonHtml .= '</div>';
            }

            $headerData     = '';
            $fileExtensions = get_meta_allowed_extension();
            $extension      = strtolower(pathinfo($data['filename'], PATHINFO_EXTENSION));
            $fileType       = array_key_first(array_filter($fileExtensions, fn ($data) => in_array('.' . $extension, explode(', ', $data['extension']))));
            if ($data['header_data_format'] === 'IMAGE' && $fileType == 'image') {
                $headerData = "<a href='" . asset('storage/templates/' . $data['filename']) . "' data-lightbox='image-group'>
                <img src='" . asset('storage/' . $data['filename']) . "' class='rounded-lg w-full mb-2'>
            </a>";
            } elseif ($data['header_data_format'] === 'TEXT' || $data['header_data_format'] === '') {
                $headerData = "<span class='font-bold mb-3'>" . nl2br(decodeWhatsAppSigns(e($header ?? ''))) . '</span>';
            } elseif ($data['header_data_format'] === 'DOCUMENT') {
                $headerData = "<a href='" . asset('storage/' . $data['filename']) . "' target='_blank' class='btn btn-secondary w-full'>" . t('document') . '</a>';
            } elseif ($data['header_data_format'] === 'VIDEO') {
                $headerData = "<video src='" . asset('storage/' . $data['filename']) . "' controls class='rounded-lg w-full'></video>";
            }

            TemplateBot::where('id', $data['id'])->update(['sending_count' => $data['sending_count'] + 1]);

            $chat_message = [
                'interaction_id' => $interactionId,
                'sender_id'      => get_setting('whatsapp.wm_default_phone_number'),
                'url'            => null,
                'message'        => "
                $headerData
                <p>" . nl2br(decodeWhatsAppSigns(e($body))) . "</p>
                <span class='text-gray-500 text-sm'>" . nl2br(decodeWhatsAppSigns(e($footer ?? ''))) . "</span>
                $buttonHtml
            ",
                'status'     => 'sent',
                'time_sent'  => now()->toDateTimeString(),
                'message_id' => $response['data']->messages[0]->id ?? null,
                'staff_id'   => 0,
                'type'       => 'text',
            ];

            $message_id = ChatMessage::insertGetId($chat_message);

            if (! empty(get_setting('pusher.app_key')) && ! empty(get_setting('pusher.app_secret')) && ! empty(get_setting('pusher.app_id')) && ! empty(get_setting('pusher.cluster'))) {
                $pusherService = new PusherService;
                $pusherService->trigger('whatsmark-chat-channel', 'whatsmark-chat-event', [
                    'chat' => ChatController::newChatMessage($interactionId, $message_id),
                ]);
            }

            return $message_id;
        }

        $type   = $type === 'flow' ? 'flow' : 'bot_files';
        $data   = parseMessageText($data);
        $header = $data['bot_header'] ?? '';
        $body   = $data['reply_text'] ?? '';
        $footer = $data['bot_footer'] ?? '';

        $headerImage       = '';
        $allowedExtensions = get_meta_allowed_extension();

        $buttonHtml = "<div class='flex flex-col mt-2 space-y-2'>";
        $option     = false;

        if (! empty($data['button1_id'])) {
            $buttonHtml .= "<button class='bg-gray-100 text-green-500 px-3 py-2 rounded-lg flex items-center justify-center text-xs space-x-2 w-full
               dark:bg-gray-800 dark:text-green-400'>" . e($data['button1']) . '</button>';
            $option = true;
        }
        if (! empty($data['button2_id'])) {
            $buttonHtml .= "<button class='bg-gray-100 text-green-500 px-3 py-2 rounded-lg flex items-center justify-center text-xs space-x-2 w-full
               dark:bg-gray-800 dark:text-green-400'>" . e($data['button2']) . '</button>';
            $option = true;
        }
        if (! empty($data['button3_id'])) {
            $buttonHtml .= "<button class='bg-gray-100 text-green-500 px-3 py-2 rounded-lg flex items-center justify-center text-xs space-x-2 w-full
               dark:bg-gray-800 dark:text-green-400'>" . e($data['button3']) . '</button>';
            $option = true;
        }
        if (! $option && ! empty($data['button_name']) && ! empty($data['button_url']) && filter_var($data['button_url'], FILTER_VALIDATE_URL)) {
            $buttonHtml .= "<a href='" . e($data['button_url']) . "' class='bg-gray-100 text-green-500 px-3 py-2 rounded-lg flex items-center justify-center text-xs space-x-2 w-full
               dark:bg-gray-800 dark:text-green-400 mt-2'> <svg class='w-4 h-4 text-green-500' aria-hidden='true' xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 24 24'> <path stroke='currentColor' stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M18 14v4.833A1.166 1.166 0 0 1 16.833 20H5.167A1.167 1.167 0 0 1 4 18.833V7.167A1.166 1.166 0 0 1 5.167 6h4.618m4.447-2H20v5.768m-7.889 2.121 7.778-7.778'/> </svg><span class='whitespace-nowrap'>" . e($data['button_name']) . '</a>';
            $option = true;
        }

        $extension = strtolower(pathinfo($data['filename'], PATHINFO_EXTENSION));
        $fileType  = array_key_first(array_filter($allowedExtensions, fn ($data) => in_array('.' . $extension, explode(', ', $data['extension']))));
        if (! $option && ! empty($data['filename']) && $fileType == 'image') {
            $headerImage = "<a href='" . asset('storage/' . $data['filename']) . "' data-lightbox='image-group'>
            <img src='" . asset('storage/' . $data['filename']) . "' class='rounded-lg w-full mb-2'>
        </a>";
        }
        if (! $option && ! empty($data['filename']) && $fileType == 'document') {
            $headerImage = "<a href='" . asset('storage/' . $data['filename']) . "' target='_blank' class='bg-gray-100 text-green-500 px-3 py-2 rounded-lg flex items-center justify-center text-xs space-x-2 w-full
               dark:bg-gray-800 dark:text-green-400'>" . t('document') . '</a>';
        }
        if (! $option && ! empty($data['filename']) && $fileType == 'video') {
            $headerImage = "<video src='" . asset('storage/' . $data['filename']) . "' controls class='rounded-lg w-full'></video>";
        }
        if (! $option && ! empty($data['filename']) && $fileType == 'audio') {
            $headerImage = "<audio controls class='w-64'><source src='" . asset('storage/' . $data['filename']) . "' type='audio/mpeg'></audio>";
        }
        $buttonHtml .= '</div>';

        MessageBots::where('id', $data['id'])->update(['sending_count' => $data['sending_count'] + 1]);

        $buttondata = $buttonHtml == "<div class='flex flex-col mt-2 space-y-2'></div>" ? '' : $buttonHtml;

        $chat_message = [
            'interaction_id' => $interactionId,
            'sender_id'      => get_setting('whatsapp.wm_default_phone_number'),
            'url'            => null,
            'message'        => $headerImage . "
            <span class='font-bold mb-3'>" . nl2br(e($header ?? '')) . '</span>
            <p>' . nl2br(decodeWhatsAppSigns(e($body))) . "</p>
            <span class='text-gray-500 text-sm'>" . nl2br(e($footer ?? '')) . "</span>
            $buttondata
        ",
            'status'     => 'sent',
            'time_sent'  => now()->toDateTimeString(),
            'message_id' => $response['data']->messages[0]->id ?? null,
            'staff_id'   => 0,
            'type'       => 'text',
        ];

        $message_id = ChatMessage::insertGetId($chat_message);

        if (! empty(get_setting('pusher.app_key')) && ! empty(get_setting('pusher.app_secret')) && ! empty(get_setting('pusher.app_id')) && ! empty(get_setting('pusher.cluster'))) {
            $pusherService = new PusherService;
            $pusherService->trigger('whatsmark-chat-channel', 'whatsmark-chat-event', [
                'chat' => ChatController::newChatMessage($interactionId, $message_id),
            ]);
        }

        return $message_id;
    }

    /**
     * Send a message via WhatsApp
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function send_message(Request $request)
    {
        try {
            // Get request data
            $id      = $request->input('id', '');
            $type    = $request->input('type');
            $type_id = $request->input('type_id');

            // Find existing chat/interaction
            $query = Chat::query();
            if (! empty($type_id)) {
                $query->where('type', $type)
                    ->where('type_id', $type_id);
            }
            $existing_interaction = $query->where('id', $id)->first();

            if (! $existing_interaction) {
                return response()->json(['error' => 'Interaction not found'], 404);
            }

            $to      = $existing_interaction->receiver_id;
            $message = strip_tags($request->input('message', ''));

            // Parse message text for contacts or leads
            $user_id = null;
            if ($type == 'customer' || $type == 'lead') {
                // Get user ID from contact (implement based on your database structure)
                $contact = Contact::find($type_id);
                $user_id = $contact->user_id ?? null;
            }

            $message_data = parseMessageText([
                'rel_type'   => $type,
                'rel_id'     => $type_id,
                'reply_text' => $message,
                'userid'     => $user_id,
            ]);

            $message = $message_data['reply_text'] ?? $message;

            $ref_message_id = $request->input('ref_message_id');

            $message_data = [];

            // Add text message if provided
            if (! empty($message)) {
                $message_data[] = [
                    'type' => 'text',
                    'text' => [
                        'preview_url' => true,
                        'body'        => $message,
                    ],
                ];
            }

            // Handle file attachments
            $attachments = [
                'audio'    => $request->file('audio'),
                'image'    => $request->file('image'),
                'video'    => $request->file('video'),
                'document' => $request->file('document'),
            ];

            foreach ($attachments as $type => $file) {
                if (! empty($file)) {
                    $file_url = $this->handle_attachment_upload($file);

                    $message_data[] = [
                        'type' => $type,
                        $type  => [
                            'url' => url('storage/whatsapp-attachments/' . $file_url),
                        ],
                    ];
                }
            }

            // Initialize WhatsApp Cloud API client
            $whatsapp_cloud_api = new WhatsAppCloudApi([
                'from_phone_number_id' => $existing_interaction->wa_no_id,
                'access_token'         => get_setting('whatsapp.wm_access_token'),
            ]);

            $messageId = null;

            // Send each message component
            foreach ($message_data as $data) {
                try {
                    switch ($data['type']) {
                        case 'text':
                            $response = $whatsapp_cloud_api->sendTextMessage($to, $data['text']['body']);
                            break;
                        case 'audio':
                            $response = $whatsapp_cloud_api->sendAudio($to, new LinkID($data['audio']['url']));
                            break;
                        case 'image':
                            $response = $whatsapp_cloud_api->sendImage($to, new LinkID($data['image']['url']));
                            break;
                        case 'video':
                            $response = $whatsapp_cloud_api->sendVideo($to, new LinkID($data['video']['url']));
                            break;
                        case 'document':
                            $fileName = basename($data['document']['url']);
                            $response = $whatsapp_cloud_api->sendDocument($to, new LinkID($data['document']['url']), $fileName, '');
                            break;
                        default:
                            continue 2;
                    }

                    // Decode the response JSON
                    $response_data = $response->decodedBody();

                    // Store the message ID if available
                    if (isset($response_data['messages'][0]['id'])) {
                        $messageId = $response_data['messages'][0]['id'];
                    }
                } catch (\Exception $e) {
                    whatsapp_log('Failed to send WhatsApp message', 'error', ['error' => $e->getMessage(), 'data' => $e], $e);

                    return response()->json(['success' => false, 'message' => 'Failed to send message: ' . $e->getMessage()]);
                }
            }

            // Create or update chat entry
            $interaction_id = $this->createOrUpdateInteraction($to, $existing_interaction->wa_no, $existing_interaction->wa_no_id, $existing_interaction->name, $message ?? ($message_data[0]['type'] ?? ''), '', false);

            foreach ($message_data as $data) {
                $message_id = ChatMessage::insertGetId([
                    'interaction_id' => $interaction_id,
                    'sender_id'      => $existing_interaction->wa_no,
                    'message'        => $message,
                    'message_id'     => $messageId,
                    'type'           => $data['type'] ?? '',
                    'staff_id'       => auth()->id(),
                    'url'            => isset($data[$data['type']]['url']) ? basename($data[$data['type']]['url']) : null,
                    'status'         => 'sent',
                    'time_sent'      => now(),
                    'ref_message_id' => $ref_message_id ?? '',
                    'created_at'     => now(),
                    'updated_at'     => now(),
                    'is_read'        => 1,
                ]);

                // Broadcast message via Pusher if enabled
                if (! empty(get_setting('pusher.app_key')) && ! empty(get_setting('pusher.app_secret')) && ! empty(get_setting('pusher.app_id')) && ! empty(get_setting('pusher.cluster'))) {
                    $pusherService = new PusherService;
                    $pusherService->trigger('whatsmark-chat-channel', 'whatsmark-chat-event', [
                        'chat' => ChatController::newChatMessage($interaction_id, $message_id),
                    ]);
                }
            }

            return response()->json(['success' => true]);
        } catch (\Exception $e) {
            whatsapp_log(
                'Failed to send WhatsApp message',
                'error',
                [
                    'error' => $e->getMessage(),
                    'data'  => $e,
                ],
                $e
            );

            return response()->json(['success' => false, 'message' => 'Failed to send message: ' . $e->getMessage()]);
        }
    }

    /**
     * Handle file attachment uploads
     *
     * @param  \Illuminate\Http\UploadedFile $file
     * @return string                        The stored file name
     */
    protected function handle_attachment_upload($file)
    {
        if (empty($file)) {
            return null;
        }

        $fileName = time() . '_' . $file->getClientOriginalName();
        $file->storeAs('whatsapp-attachments', $fileName, 'public');

        return $fileName;
    }
}
