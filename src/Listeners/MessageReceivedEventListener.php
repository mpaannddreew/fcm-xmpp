<?php

namespace FannyPack\Fcm\Xmpp\Listeners;

use FannyPack\Fcm\Xmpp\Events\MessageReceivedEvent;
use FannyPack\Fcm\Http\HttpClient;
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
     * @var HttpClient
     */
    protected $http;

    /**
     * Create the event listener.
     * @param ConnectionStorage $storage
     * @param HttpClient $http
     */
    public function __construct(ConnectionStorage $storage, HttpClient $http)
    {
        $this->storage = $storage;
        $this->http = $http;
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
        $payload = (new Payload())->setData((array)$mData->data)->setTitle($mData->data->my_action)->setMessage($mData->data->my_message);
        $connection = $this->storage->getViableConnection();
        $pipeline = $connection ? Packet::XMPP_PIPELINE : Packet::HTTP_PIPELINE;
        $packet = (new Packet())->setPipeline($pipeline)->setPayload($payload)->setTo($mData->from);

        switch ($pipeline){
            case Packet::XMPP_PIPELINE:
                $fcm_message = new FcmMessage($packet);
                $connection->write((string)$fcm_message);
                break;
            default:
                $packet->setPipeline(Packet::HTTP_PIPELINE);
                $this->http->sendMessage($packet);
                break;
        }
    }
}
