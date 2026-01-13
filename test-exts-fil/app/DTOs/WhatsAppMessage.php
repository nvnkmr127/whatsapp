<?php

namespace App\DTOs;

class WhatsAppMessage
{
    public function __construct(
        public readonly string $to,
        public readonly string $template,
        public readonly array $parameters = [],
        public readonly string $language = 'en_US',
        public readonly ?array $media = null
    ) {
        $this->validate();
    }

    public static function fromArray(array $data): self
    {
        return new self(
            to: $data['to'],
            template: $data['template'],
            parameters: $data['parameters'] ?? [],
            language: $data['language']     ?? 'en_US',
            media: $data['media']           ?? null
        );
    }

    public function toArray(): array
    {
        return [
            'to'         => $this->to,
            'template'   => $this->template,
            'parameters' => $this->parameters,
            'language'   => $this->language,
            'media'      => $this->media,
        ];
    }

    private function validate(): void
    {
        if (empty($this->to)) {
            throw new \InvalidArgumentException(t('recipient_phone_number_required'));
        }

        if (empty($this->template)) {
            throw new \InvalidArgumentException(t('message_template_required'));
        }

        if (! preg_match('/^\d{10,15}$/', $this->to)) {
            throw new \InvalidArgumentException(t('invalid_phone_number_format'));
        }
    }
}
