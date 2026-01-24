<?php

namespace App\Events\Base;

use App\Events\Contracts\DomainEventContract;
use App\Exceptions\InvalidDomainEventException;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

abstract class DomainEvent implements DomainEventContract
{
    use Dispatchable, SerializesModels;

    public $eventId;
    public $occurredAt;
    public $payload;
    public $metadata;

    /**
     * Instantiate and validate the event.
     * 
     * @param array $payload Key-value data specific to the domain event.
     * @param array $metadata Optional context (user_id, team_id, request_id).
     * @throws InvalidDomainEventException
     */
    public function __construct(array $payload, array $metadata = [])
    {
        $this->eventId = (string) Str::uuid();
        $this->occurredAt = now()->toIso8601String();
        $this->metadata = $this->enrichMetadata($metadata);

        // Validate payload against schema rules
        $this->validate($payload);

        $this->payload = $payload;
    }

    /**
     * Validate the payload.
     * 
     * @param array $payload
     * @throws InvalidDomainEventException
     */
    protected function validate(array $payload): void
    {
        $validator = Validator::make($payload, $this->rules());

        if ($validator->fails()) {
            $errors = $validator->errors()->toArray();
            $className = class_basename($this);
            throw new InvalidDomainEventException(
                "Event contract violation for [{$className}]: " . json_encode($errors),
                $errors
            );
        }
    }

    /**
     * Default version is 1.
     */
    public function version(): int
    {
        return 1;
    }

    /**
     * Default category is 'operational'. Override in subclasses.
     * Options: 'business', 'operational', 'debug'
     */
    public function category(): string
    {
        return 'operational';
    }

    /**
     * Check if this event qualifies as a Business Signal.
     */
    public function isSignal(): bool
    {
        return $this->category() === 'business';
    }

    /**
     * Enrich metadata with global context if available.
     */
    protected function enrichMetadata(array $metadata): array
    {
        // 1. Correlation Context
        $currentTraceId = \App\Services\TraceContext::getTraceId();

        if (!$currentTraceId && app()->bound('request')) {
            $currentTraceId = \App\Services\TraceContext::ensureTraceId();
        }

        $spanId = $this->eventId;

        $defaults = [
            'version' => $this->version(),
            'source' => $this->source(),
            'category' => $this->category(),
            'is_signal' => $this->isSignal(),
            'environment' => config('app.env'),
            'trace_id' => $currentTraceId ?? $this->eventId,
            'span_id' => $spanId,
            'parent_id' => \App\Services\TraceContext::getParentId(),
        ];

        // Attempt to grab current user/team context if not provided
        if (!isset($metadata['team_id']) && auth()->check() && request()->user()->current_team_id) {
            $metadata['team_id'] = request()->user()->current_team_id;
        }

        if (!isset($metadata['actor_id']) && auth()->check()) {
            $metadata['actor_id'] = request()->user()->id;
        }

        return array_merge($defaults, $metadata);
    }

    public function toArray(): array
    {
        return [
            'id' => $this->eventId,
            'occurred_at' => $this->occurredAt,
            'metadata' => $this->metadata,
            'payload' => $this->payload,
        ];
    }

    /**
     * Helper to access payload keys dynamically as properties.
     */
    public function __get($key)
    {
        if (array_key_exists($key, $this->payload)) {
            return $this->payload[$key];
        }
        return null;
    }
}
