<?php

namespace FannyPack\Fcm\Xmpp\Commands;

use FannyPack\Fcm\Xmpp\Config;
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
     * @var Config
     */
    protected $config;

    /**
     * Create a new command instance.
     *
     */
    public function __construct()
    {
        parent::__construct();

        $this->client = $this->laravel[Config::class];
        $this->config = $this->laravel[XmppClient::class];
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
