<?php

namespace FannyPack\Fcm\Xmpp\Events;

class ConnectionDrainageEvent
{
    public $data;

    /**
     * Create a new event instance.
     *
     * @param $data
     */
    public function __construct($data)
    {
        $this->data = $data;
    }
}
