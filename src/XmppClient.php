<?php
/**
 * Created by PhpStorm.
 * User: andre
 * Date: 2017-07-29
 * Time: 2:12 PM
 */

namespace FannyPack\Fcm\Xmpp;

use Exception;
use Illuminate\Support\Facades\Log;
use React\EventLoop\Factory;
use React\Socket\TcpConnector;
use React\Dns\Resolver\Factory as DnsResolverFactory;
use React\Socket\DnsConnector;
use React\Socket\SecureConnector;
use React\Socket\TimeoutConnector;
use React\Socket\ConnectionInterface;
use Illuminate\Contracts\Foundation\Application;


class XmppClient
{
    /**
     * @var Application
     */
    protected $app;

    /**
     * @var \React\EventLoop\ExtEventLoop|\React\EventLoop\LibEventLoop|\React\EventLoop\LibEvLoop|\React\EventLoop\StreamSelectLoop
     */
    protected $loop;

    /**
     * @var $tcpConnector
     */
    protected $tcpConnector;

    /**
     * @var $dnsConnector
     */
    protected $dnsConnector;

    /**
     * @var $secureConnector
     */
    protected $secureConnector;

    /**
     * @var $timeoutConnector
     */
    protected $timeoutConnector;

    /**
     * @var XmppConnectionPool
     */
    protected $pool;

    /**
     * @var XmppConfig
     */
    protected $config;

    /**
     * Server constructor.
     * @param Application $app
     */
    public function __construct(Application $app)
    {
        $this->app = $app;
        $this->pool = $this->app[XmppConnectionPool::class];
        $this->config = $this->app[XmppConfig::class];
        $this->loop = Factory::create();
        $this->configureTcpIpConnector();
        $this->configureDnsConnector();
        $this->configureTlsConnector();
        $this->configureTimeoutConnector();
    }

    /**
     * setup a TCP connector
     */
    protected function configureTcpIpConnector()
    {
        $this->tcpConnector = new TcpConnector($this->loop);
    }

    /**
     * setup a DNS connector
     */
    protected function configureDnsConnector()
    {
        $dnsResolverFactory = new DnsResolverFactory();
        $dns = $dnsResolverFactory->createCached('8.8.8.8', $this->loop);
        $this->dnsConnector = new DnsConnector($this->tcpConnector, $dns);
    }

    /**
     * setup a SSL/TLS connector
     */
    protected function configureTlsConnector()
    {
        $this->secureConnector = new SecureConnector($this->dnsConnector, $this->loop, $this->config->getContext());
    }

    /**
     * setup a Timeout connector
     */
    protected function configureTimeoutConnector()
    {
        $this->timeoutConnector = new TimeoutConnector($this->secureConnector, $this->config->getTimeout(), $this->loop);
    }

    /**
     * initiate server connection
     */
    public function connect()
    {
        $this->timeoutConnector->connect($this->config->getHostIp() . ':' . $this->config->getPort())->then(
            function (ConnectionInterface $connection) {
                $this->pool->add($connection);
            },
            function (Exception $error) {
                Log::info($error);
                $this->reConnect();
            }
        );

        $this->loop->run();
        $this->reConnect();
    }

    /**
     * initiate server reconnection
     */
    private function reConnect()
    {
        $this->connect();
    }
}