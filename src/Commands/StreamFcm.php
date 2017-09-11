<?php

namespace FannyPack\Fcm\Xmpp\Commands;

use FannyPack\Fcm\Xmpp\XmppClient;
use Illuminate\Console\Command;

class StreamFcm extends Command
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

    /**
     * @var XmppClient
     */
    protected $server;

    /**
     * Create a new command instance.
     * @param XmppClient $server
     */
    public function __construct(XmppClient $server)
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
        $this->line("<info>Streaming FCM XMPP Connection server</info>");
        $this->server->connect();
        return;
    }
}
