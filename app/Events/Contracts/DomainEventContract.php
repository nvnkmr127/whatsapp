<?php

namespace App\Events\Contracts;

interface DomainEventContract
{
    /**
     * Get data validation rules.
     */
    public function rules(): array;

    /**
     * Get the source module name.
     */
    public function source(): string;

    /**
     * Get the schema version.
     */
    public function version(): int;

    /**
     * Get the event category (business, operational, debug).
     */
    public function category(): string;

    /**
     * Check if this event qualifies as a Business Signal.
     */
    public function isSignal(): bool;

    /**
     * Serialize payload for transport.
     */
    public function toArray(): array;
}
