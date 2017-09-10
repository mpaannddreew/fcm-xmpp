<?php

namespace FannyPack\Fcm\Xmpp\Listeners;

use FannyPack\Fcm\Xmpp\Events\ConnectionDrainageEvent;
use FannyPack\Fcm\Xmpp\FcmXmppClient;

class ConnectionDrainageEventListener
{
    protected $client;

    /**
     * Create the event listener.
     * @param FcmXmppClient $client
     */
    public function __construct(FcmXmppClient $client)
    {
        $this->client = $client;
    }

    /**
     * Handle the event.
     *
     * @param ConnectionDrainageEvent $event
     * @return void
     */
    public function handle(ConnectionDrainageEvent $event)
    {
        $this->client->connect();
    }
}
