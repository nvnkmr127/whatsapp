<?php

namespace App\Events;

use App\Models\Team;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class UsageThresholdReached
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $team;
    public $metric;
    public $level;
    public $percent;
    public $message;

    /**
     * Create a new event instance.
     */
    public function __construct(Team $team, string $metric, string $level, float $percent, string $message)
    {
        $this->team = $team;
        $this->metric = $metric;
        $this->level = $level;
        $this->percent = $percent;
        $this->message = $message;
    }
}
