<?php

namespace App\Listeners;

class NewLeadCreatedListener
{
    /**
     * Create the event listener.
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     */
    public function handle(object $event): void
    {
        app_log(
            'New Lead Created',
            'info',
            null,
            [
                'lead_id'   => $event->lead->id,
                'lead_name' => $event->lead->name,
            ]
        );
    }
}
