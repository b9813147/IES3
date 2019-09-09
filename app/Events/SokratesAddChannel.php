<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Queue\SerializesModels;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;

class SokratesAddChannel
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $memberId;

    public $teamModelId;

    public $channel;

    /**
     * Create a new event instance.
     *
     * @param integer $memberId
     * @param integer $teamModelId
     * @param string $channel
     *
     * @return void
     */
    public function __construct($memberId, $teamModelId, $channel = null)
    {
        $this->memberId = $memberId;
        $this->teamModelId = $teamModelId;
        $this->channel = $channel;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return \Illuminate\Broadcasting\Channel|array
     */
    public function broadcastOn()
    {
        return [];
    }
}
