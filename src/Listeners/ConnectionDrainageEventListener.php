<?php

namespace FannyPack\Fcm\Xmpp\Listeners;

use FannyPack\Fcm\Xmpp\Events\ConnectionDrainageEvent;
use FannyPack\Fcm\Xmpp\XmppClient;

class ConnectionDrainageEventListener
{
    /**
     * @var XmppClient
     */
    protected $client;

    /**
     * Create the event listener.
     * @param XmppClient $client
     */
    public function __construct(XmppClient $client)
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
