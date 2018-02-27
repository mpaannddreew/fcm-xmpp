<?php

namespace FannyPack\Fcm\Xmpp\Commands;

use FannyPack\Fcm\Xmpp\XmppConfig;
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
    protected $client;

    /**
     * @var XmppConfig
     */
    protected $config;

    /**
     * Create a new command instance.
     * @param XmppClient $client
     * @param XmppConfig $config
     */
    public function __construct(XmppClient $client, XmppConfig $config)
    {
        parent::__construct();

        $this->client = $client;
        $this->config = $config;
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $this->line("<info>Streaming FCM XMPP Connection server:</info> <tls://{$this->config->getHostIp()}:{$this->config->getPort()}>");
        $this->client->connect();
        return;
    }
}
