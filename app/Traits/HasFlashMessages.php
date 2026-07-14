<?php

namespace App\Traits;

trait HasFlashMessages
{
    /**
     * Dispatch a notify event for the toast component.
     * types: success, error, warning, info
     */
    public function notify($message, $type = 'success')
    {
        $this->dispatch('notify', message: $message, type: $type);
    }

    /**
     * Dispatch a success notification.
     */
    public function success($message)
    {
        $this->notify($message, 'success');
    }

    /**
     * Dispatch an error notification.
     */
    public function error($message)
    {
        $this->notify($message, 'error');
    }

    /**
     * Dispatch a warning notification.
     */
    public function warning($message)
    {
        $this->notify($message, 'warning');
    }

    /**
     * Dispatch an info notification.
     */
    public function info($message)
    {
        $this->notify($message, 'info');
    }

    /**
     * Flash a message and redirect (standard Laravel behavior).
     */
    public function flash($message, $type = 'success')
    {
        session()->flash($type, $message);
    }
}
