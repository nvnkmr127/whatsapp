<?php

namespace App\Events\Contracts;

interface DomainEventContract
{
    /**
     * Get data validation rules.
     * @return array
     */
    public function rules(): array;

    /**
     * Get the source module name.
     * @return string
     */
    public function source(): string;

    /**
     * Get the schema version.
     * @return int
     */
    public function version(): int;

    /**
     * Get the event category (business, operational, debug).
     * @return string
     */
    public function category(): string;

    /**
     * Check if this event qualifies as a Business Signal.
     * @return bool
     */
    public function isSignal(): bool;

    /**
     * Serialize payload for transport.
     * @return array
     */
    public function toArray(): array;
}
