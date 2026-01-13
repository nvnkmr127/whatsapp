<?php

namespace App\Jobs;

use App\DTOs\WhatsAppMessage;
use App\Exceptions\WhatsApp\WhatsAppException;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Netflie\WhatsAppCloudApi\WhatsAppCloudApi;
use Throwable;

class SendWhatsAppMessage implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $maxExceptions = 3;

    public array $backoff = [60, 180, 360];

    public function __construct(
        private readonly WhatsAppMessage $message
    ) {
        $this->onQueue(json_decode(get_setting('whatsapp.queue'), true)['name']);
    }

    public function handle(): void
    {
        try {
            $whatsapp = new WhatsAppCloudApi([
                'from_phone_number_id' => get_setting('whatsapp.wm_default_phone_number_id'),
                'access_token'         => get_setting('whatsapp.wm_access_token'),
            ]);

            // Send the message without components
            $result = $whatsapp->sendTemplate(
                $this->message->to,
                $this->message->template,
                $this->message->language,
                null
            );

            // Log success
            whatsapp_log(
                t('whatsapp_message_sent_successfully'),
                'info',
                [
                    'message_id' => $result->messageId(),
                    'recipient'  => $this->message->to,
                    'template'   => $this->message->template,
                ]
            );
        } catch (Throwable $e) {
            whatsapp_log(
                t('whatsapp_message_failed'),
                'error',
                [
                    'error'     => $e->getMessage(),
                    'recipient' => $this->message->to,
                    'template'  => $this->message->template,
                    'attempt'   => $this->attempts(),
                ],
                $e
            );

            if ($this->attempts() >= $this->tries) {
                throw new WhatsAppException(
                    t('failed_to_send_whatsapp_message') . $this->tries . t('attempts'),
                    ['message' => $this->message->toArray()]
                );
            }

            $this->release(
                $this->backoff[$this->attempts() - 1] ?? end($this->backoff)
            );
        }
    }

    public function failed(Throwable $e): void
    {
        whatsapp_log(
            t('whatsapp_message_failed_permanently'),
            'error',
            [
                'error'     => $e->getMessage(),
                'recipient' => $this->message->to,
                'template'  => $this->message->template,
            ],
            $e
        );

    }
}
