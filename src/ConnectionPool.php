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

class ConnectionPool
{
    /**
     * @var Application
     */
    protected $app;

    /**
     * @var ConnectionStorage
     */
    protected $storage;

    /**
     * @var Parser
     */
    protected $parser;

    /**
     * ConnectionPool constructor.
     * @param Application $app
     */
    public function __construct(Application $app)
    {
        $this->app = $app;
        $this->storage = $this->app[ConnectionStorage::class];
        $this->parser = $this->app[Parser::class];
    }

    /**
     * add a connection to storage
     *
     * @param ConnectionInterface $connection
     */
    public function add(ConnectionInterface $connection)
    {
        $connection->write('<stream:stream to="' . Config::HOST_DOMAIN . '" version="1.0" xmlns="jabber:client" xmlns:stream="http://etherx.jabber.org/streams">');

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
            $this->parser->parseData($data, $connection);
        });

        $connection->on('close', function() use ($connection){
            $this->storage->detach($connection);
        });
    }
}