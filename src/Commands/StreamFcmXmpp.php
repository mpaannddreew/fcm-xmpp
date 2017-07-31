<?php

namespace FannyPack\FcmXmpp\Commands;

use FannyPack\FcmXmpp\Xmpp\XmppServer;
use Illuminate\Console\Command;

class StreamFcmXmpp extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'fcm:stream';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Stream the Firebase Cloud Messaging (FCM) server through XMPP protocol for incoming messages';

    protected $server;

    /**
     * Create a new command instance.
     *
     * @param XmppServer $server
     */
    public function __construct(XmppServer $server)
    {
        parent::__construct();

        $this->server = $server;
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $this->info("Connecting to Firebase Cloud Messaging (FCM) server");
        $this->server->connect();
        $this->info("Connected to Firebase Cloud Messaging (FCM) server");
    }
}
