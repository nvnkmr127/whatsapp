<?php

namespace App\Events;

use App\Models\Campaign;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class CampaignProgressUpdated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $campaignId;
    public $status;
    public $metrics;

    public function __construct(Campaign $campaign)
    {
        $this->campaignId = $campaign->id;
        $this->status = $campaign->status;

        // In a real app, you'd calculate these from the DB or Redis
        $this->metrics = [
            'sent' => $campaign->sent_count,
            'delivered' => $campaign->messages()->where('status', 'delivered')->count(),
            'read' => $campaign->messages()->where('status', 'read')->count(),
            'failed' => $campaign->messages()->where('status', 'failed')->count(),
            'total' => $campaign->lastSnapshot ? $campaign->lastSnapshot->audience_count : 0,
        ];
    }

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel("campaign.{$this->campaignId}.progress"),
        ];
    }

    public function broadcastAs(): string
    {
        return 'progress.updated';
    }
}
