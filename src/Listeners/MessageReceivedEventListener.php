<?php

namespace App\Listeners\FannyPack\Fcm\Xmpp\Listeners;

use App\Events\FannyPack\Fcm\Xmpp\Events\MessageReceivedEvent;
use FannyPack\Fcm\Xmpp\ConnectionStorage;
use FannyPack\Utils\Fcm\Messages\FcmMessage;
use FannyPack\Utils\Fcm\Messages\Payload;
use FannyPack\Utils\Fcm\Packet;
use Illuminate\Contracts\Queue\ShouldQueue;

class MessageReceivedEventListener implements ShouldQueue
{
    /**
     * @var ConnectionStorage
     */
    protected $storage;

    /**
     * Create the event listener.
     * @param ConnectionStorage $storage
     */
    public function __construct(ConnectionStorage $storage)
    {
        $this->storage = $storage;
    }

    /**
     * Handle the event.
     *
     * @param  MessageReceivedEvent  $event
     * @return void
     */
    public function handle(MessageReceivedEvent $event)
    {
        $mData = $event->data;
        $payload = (new Payload())->setData((array)$mData->data)->setTitle("Testing")->setMessage("This is a test message");
        $packet = (new Packet())->setPipeline(Packet::XMPP_PIPELINE)->setPayload($payload)->setTo($mData->from);
        $fcm_message = new FcmMessage($packet);
        // todo send (string)$fcm_message
    }
}
