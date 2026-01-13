<?php

namespace App\Jobs;

use App\Models\Campaign;
use App\Models\CampaignDetail;
use App\Models\Chat;
use App\Models\ChatMessage;
use App\Models\WhatsappTemplate;
use App\Traits\WhatsApp;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Throwable;

class SendCampaignMessageJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels, WhatsApp;

    /**
     * Number of times the job may be attempted
     */
    public int $tries = 3;

    /**
     * The number of seconds to wait before retrying the job
     */
    public array $backoff = [180, 300, 600];

    /**
     * The maximum number of seconds the job should be allowed to run
     */
    public int $timeout;

    /**
     * Create a new job instance.
     */
    public function __construct(
        protected CampaignDetail $detail
    ) {
        $this->onQueue(json_decode(get_setting('whatsapp.queue'), true)['name']);
        $this->timeout = json_decode(get_setting('whatsapp.queue'), true)['timeout'] ?? 60;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        // Check if campaign is paused - if so, release the job back to queue
        if ($this->detail->campaign->pause_campaign) {
            $this->release(json_decode(get_setting('whatsapp.queue'), true)['retry_after'] ?? 180);

            return;
        }

        try {
            // Get the associated campaign
            $campaign = Campaign::findOrFail($this->detail->campaign_id);

            $template = WhatsappTemplate::where('template_id', $campaign->template_id)->firstOrFail()->toArray();

            // Format parameters for template
            $template['header_message'] = $template['header_data_text'] ?? null;
            $template['body_message']   = $template['body_data']        ?? null;
            $template['footer_message'] = $template['footer_data']      ?? null;

            // Prepare data for sending
            $contact = \App\Models\Contact::where('id', $this->detail->rel_id)->first();
            if (! $contact) {
                throw new \Exception('Contact not found: ' . $this->detail->rel_id);
            }

            // Build message parameters
            $rel_data = array_merge(
                [
                    'rel_type' => $this->detail->rel_type,
                    'rel_id'   => $contact->id,
                ],
                $template,
                [
                    'campaign_id'        => $campaign->id,
                    'header_data_format' => $template['header_data_format'] ?? null,
                    'filename'           => $campaign->filename             ?? null,
                    'header_params'      => $campaign->header_params,
                    'body_params'        => $campaign->body_params,
                    'footer_params'      => $campaign->footer_params,
                ]
            );

            // Use the WhatsApp trait to send the template
            $response = $this->sendTemplate($contact->phone, $rel_data);

            // Update the detail record with the response
            if (! empty($response['status'])) {
                // Process the successful message sending
                $this->processSuccessfulMessage($response, $rel_data, $contact->toArray());

                // Update campaign detail
                $this->detail->update([
                    'status'           => 2,
                    'message_status'   => 'sent',
                    'whatsapp_id'      => $response['data']->messages[0]->id ?? null,
                    'response_message' => null,
                ]);
            } else {
                // Update detail with error
                $this->detail->update([
                    'status'           => 0,
                    'message_status'   => 'failed',
                    'response_message' => $response['message'] ?? 'Unknown error occurred',
                ]);

                if (json_decode(get_setting('whatsapp.queue'), true)['retry_after'] ?? 180) {
                    $this->release(json_decode(get_setting('whatsapp.queue'), true)['retry_after'] ?? 180);

                    return;
                }
            }
        } catch (Throwable $e) {
            $this->handleFailure($e);

            // Check if we should retry
            if ($this->attempts() < $this->tries) {
                $this->release($this->backoff[$this->attempts() - 1]);
            } else {
                $this->fail($e);
            }
        }
    }

    /**
     * Process successful message sending and create chat record
     */
    protected function processSuccessfulMessage(array $response, array $rel_data, $contact): void
    {
        // Parse template parts
        $header = parseText($rel_data['rel_type'], 'header', $rel_data);
        $body   = parseText($rel_data['rel_type'], 'body', $rel_data);
        $footer = parseText($rel_data['rel_type'], 'footer', $rel_data);

        // Create buttons HTML if any
        $buttonHtml = '';
        if (! empty($rel_data['buttons_data']) && is_string($rel_data['buttons_data'])) {
            $buttons = json_decode($rel_data['buttons_data']);
            if (is_array($buttons) || is_object($buttons)) {
                $buttonHtml = "<div class='flex flex-col mt-2 space-y-2'>";
                foreach ($buttons as $button) {
                    $buttonHtml .= "<button class='bg-gray-100 text-green-500 px-3 py-2 rounded-lg flex items-center justify-center text-xs space-x-2 w-full
                        dark:bg-gray-800 dark:text-green-400'>" . e($button->text) . '</button>';
                }
                $buttonHtml .= '</div>';
            }
        }

        // Create header data HTML
        $headerData     = '';
        $fileExtensions = get_meta_allowed_extension();
        if (! empty($rel_data['filename'])) {
            $extension = strtolower(pathinfo($rel_data['filename'], PATHINFO_EXTENSION));
            $fileType  = array_key_first(array_filter($fileExtensions, fn ($data) => in_array('.' . $extension, explode(', ', $data['extension']))));

            if ($rel_data['header_data_format'] == 'IMAGE' && $fileType == 'image') {
                $headerData = "<a href='" . asset('storage/' . $rel_data['filename']) . "' class=''>
                    <img src='" . asset('storage/' . $rel_data['filename']) . "' class='img-responsive rounded-lg object-cover'>
                    </a>";
            } elseif ($rel_data['header_data_format'] == 'DOCUMENT') {
                $headerData = "<a href='" . asset('storage/' . $rel_data['filename']) . "' target='_blank' class='btn btn-secondary w-full'>" . t('document') . '</a>';
            } elseif ($rel_data['header_data_format'] == 'VIDEO') {
                $headerData = "<video src='" . asset('storage/' . $rel_data['filename']) . "' controls class='rounded-lg w-full'></video>";
            }
        }

        if (empty($headerData) && ($rel_data['header_data_format'] == 'TEXT' || empty($rel_data['header_data_format'])) && ! empty($header)) {
            $headerData = "<span class='font-bold mb-3'>" . nl2br(decodeWhatsAppSigns(e($header))) . '</span>';
        }

        // Find or create chat
        $phone = $contact['phone'];
        if (strpos($phone, '+') === 0) {
            $phone = substr($phone, 1);
        }

        $chat_id = Chat::where('receiver_id', $phone)->value('id');
        if (empty($chat_id)) {
            $chat_id = Chat::insertGetId([
                'receiver_id'  => $phone,
                'wa_no'        => get_setting('whatsapp.wm_default_phone_number'),
                'wa_no_id'     => get_setting('whatsapp.wm_default_phone_number_id'),
                'name'         => $contact['firstname'] . ' ' . $contact['lastname'],
                'last_message' => $body ?? '',
                'time_sent'    => now(),
                'type'         => $contact['type'] ?? 'guest',
                'type_id'      => $contact['id']   ?? '',
                'created_at'   => now(),
                'updated_at'   => now(),
            ]);
        }

        // Create chat message
        ChatMessage::create([
            'interaction_id' => $chat_id,
            'sender_id'      => get_setting('whatsapp.wm_default_phone_number'),
            'url'            => null,
            'message'        => "
                $headerData
                <p>" . nl2br(decodeWhatsAppSigns(e($body ?? ''))) . "</p>
                <span class='text-gray-500 text-sm'>" . nl2br(decodeWhatsAppSigns(e($footer ?? ''))) . "</span>
                $buttonHtml
            ",
            'status'     => 'sent',
            'time_sent'  => now()->toDateTimeString(),
            'message_id' => $response['data']->messages[0]->id ?? null,
            'staff_id'   => 0,
            'type'       => 'text',
        ]);

        // Update Chat with last message and time
        Chat::where('id', $chat_id)->update([
            'last_message'  => $body ?? '',
            'last_msg_time' => now(),
        ]);
    }

    /**
     * Handle job failure
     */
    protected function handleFailure(Throwable $e): void
    {
        $this->detail->update([
            'status'           => 0,
            'message_status'   => 'failed',
            'response_message' => $e->getMessage(),
        ]);

        if (json_decode(get_setting('whatsapp.logging'), true)['detailed']) {
            whatsapp_log(
                'Campaign message failed',
                'error',
                [
                    'campaign_id' => $this->detail->campaign_id,
                    'detail_id'   => $this->detail->id,
                    'error'       => $e->getMessage(),
                    'trace'       => $e->getTraceAsString(),
                ],
                $e
            );
        }
    }
}
