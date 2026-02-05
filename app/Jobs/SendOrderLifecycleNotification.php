<?php

namespace App\Jobs;

use App\Models\Order;
use App\Events\OrderStatusUpdated;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SendOrderLifecycleNotification implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $order;

    public function __construct(Order $order)
    {
        $this->order = $order;
    }

    public function handle()
    {
        // Reuse the logic from the Event Listener by dispatching the event
        // This keeps logic centralized in the Listener while allowing direct Job dispatch
        event(new OrderStatusUpdated($this->order, $this->order->status, []));
    }
}
