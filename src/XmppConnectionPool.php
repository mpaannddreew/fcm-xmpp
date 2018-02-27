<?php
/**
 * Created by PhpStorm.
 * User: andre
 * Date: 2017-09-06
 * Time: 4:26 PM
 */

namespace FannyPack\Fcm\Xmpp;


use Illuminate\Contracts\Foundation\Application;
use Illuminate\Support\Facades\Log;
use React\Socket\ConnectionInterface;

class XmppConnectionPool
{
    /**
     * @var Application
     */
    protected $app;

    /**
     * @var XmppConnectionStorage
     */
    protected $storage;

    /**
     * @var XmppParser
     */
    protected $parser;

    /**
     * @var XmppConfig
     */
    protected $config;

    /**
     * ConnectionPool constructor.
     * @param Application $app
     */
    public function __construct(Application $app)
    {
        $this->app = $app;
        $this->storage = $this->app[XmppConnectionStorage::class];
        $this->parser = $this->app[XmppParser::class];
        $this->config = $this->app[XmppConfig::class];
    }

    /**
     * add a connection to storage
     *
     * @param ConnectionInterface $connection
     */
    public function add(ConnectionInterface $connection)
    {
        $connection->write('<stream:stream to="' . $this->config->getHostDomain() . '" version="1.0" xmlns="jabber:client" xmlns:stream="http://etherx.jabber.org/streams">');

        $this->initEvents($connection);
        $this->storage->attach($connection, ['position' => $this->storage->count() + 1]);
    }

    /**
     * register events on current connection before storage
     *
     * @param ConnectionInterface $connection
     */
    protected function initEvents(ConnectionInterface $connection)
    {
        $connection->on('data', function ($data) use ($connection) {
            Log::info("---reading---");
            Log::info($data);
            $this->parser->parseData($data, $connection);
        });

        $connection->on('close', function() use ($connection){
            Log::info("---closing---");
            $this->storage->detach($connection);
        });
    }
}