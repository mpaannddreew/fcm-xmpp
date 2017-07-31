<?php
/**
 * Created by PhpStorm.
 * User: andre
 * Date: 2017-07-29
 * Time: 2:12 PM
 */

namespace FannyPack\FcmXmpp\Xmpp;

use DOMDocument;
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


class XmppServer
{
    /**
     * @var Application
     */
    protected $app;

    /**
     * @var $apiKey
     */
    protected $apiKey;

    /**
     * @var $senderId
     */
    protected $senderId;

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
     * @var float $timeout
     */
    protected $timeout = 60.0;

    /**
     * @var $host
     */
    protected $host;

    CONST HOST = "fcm-xmpp.googleapis.com";

    /**
     * @var $port
     */
    protected $port;

    /**
     * @var $connection
     */
    protected $connection;

    /**
     * XmppServer constructor.
     * @param Application $app
     */
    public function __construct(Application $app)
    {
        $this->app = $app;
        $this->setConfigs();
        $this->loop = Factory::create();
        $this->configureTcpIpConnector();
        $this->configureDnsConnector();
        $this->configureTlsConnector();
        $this->configureTimeoutConnector();
    }

    /**
     *  set server configurations from file
     */
    protected function setConfigs()
    {
        $key = $this->app['config']['fcmxmpp.apiKey'];
        $sender_id = $this->app['config']['fcmxmpp.senderId'];
        $host = $this->app['config']['fcmxmpp.host'];
        $port = $this->app['config']['fcmxmpp.port'];
        
        if (!$key)
            throw new \InvalidArgumentException("FCM Server key not specified");
        
        if (!$sender_id)
            throw new \InvalidArgumentException("FCM Sender Id not specified");

        if (!$host)
            throw new \InvalidArgumentException("FCM Xmpp Host not specified");

        if (!$port)
            throw new \InvalidArgumentException("FCM Xmpp Port not specified");

        $this->senderId =  $sender_id;
        $this->apiKey =  $key;
        $this->host =  $host;
        $this->port =  $port;
    }
    

    protected function configureTcpIpConnector()
    {
        $this->tcpConnector = new TcpConnector($this->loop);
    }

    protected function configureDnsConnector()
    {
        $dnsResolverFactory = new DnsResolverFactory();
        $dns = $dnsResolverFactory->createCached('8.8.8.8', $this->loop);
        $this->dnsConnector = new DnsConnector($this->tcpConnector, $dns);
    }

    protected function configureTlsConnector()
    {
        $context = ['verify_peer' => false, 'verify_peer_name' => false];
        $this->secureConnector = new SecureConnector($this->dnsConnector, $this->loop, $context);
    }

    protected function configureTimeoutConnector()
    {
        $this->timeoutConnector = new TimeoutConnector($this->secureConnector, $this->timeout, $this->loop);
    }

    public function connect()
    {
        $this->timeoutConnector->connect($this->host . ':' . $this->port)->then(
            function (ConnectionInterface $connection) {
                $this->connection = $connection;
                $this->stream($connection);
            },
            function (Exception $error) {
                Log::info($error->getMessage());
                $this->connect();
            }
        );

        $this->loop->run();
    }

    private function stream(ConnectionInterface $connection)
    {
        $data = '<stream:stream to="' . self::HOST . '" version="1.0" xmlns="jabber:client" xmlns:stream="http://etherx.jabber.org/streams">';
        Log::info("--- writing ---");
        Log::info($data);
        $connection->write($data);
        $connection->on('data', function ($chunk){
            Log::info("--- reading ---");
            Log::info($chunk);
            $this->parseData($chunk);
        });
    }
    
    public function connection()
    {
        return $this->connection;
    }

    private function parseData($chunk)
    {
        $xml = new DOMDocument();
        $xml->recover = true;
        $xml->loadXML($chunk, LIBXML_NOWARNING | LIBXML_NOERROR);

        foreach ($xml->childNodes as $node){
            if ($node->localName == 'stream:features') {
                foreach ($node->childNodes as $_node)
                {
                    if ($_node->localName == 'mechanisms')
                    {
                        $data = '<auth mechanism="PLAIN" xmlns="urn:ietf:params:xml:ns:xmpp-sasl">' . base64_encode(chr(0) . $this->senderId . '@gcm.googleapis.com' . chr(0) . $this->apiKey) . '</auth>';
                        $this->write($data);
                    }
                    elseif ($_node->localName == 'bind')
                    {
                        $data = '<iq type="set"><bind xmlns="urn:ietf:params:xml:ns:xmpp-bind"></bind></iq>';
                        $this->write($data);
                    }
                    elseif ($_node->localName == 'session')
                    {
                        $data = '<iq type="set"><bind xmlns="urn:ietf:params:xml:ns:xmpp-bind"></bind></iq>';
                        $this->write($data);
                    }
                }
            } elseif ($node->localName == 'success')
            {
                $data = '<stream:stream to="' . self::HOST . '" version="1.0" xmlns="jabber:client" xmlns:stream="http://etherx.jabber.org/streams">';
                $this->write($data);
            }
            elseif ($node->localName == 'failure')
                $this->connect();
            elseif ($node->localName == 'iq' && $node->getAttribute('type') == 'result')
                $this->write("");
            elseif ($node->localName == 'message') {

            }
        }
    }

    protected function write($data){
        Log::info("--- writing ---");
        Log::info($data);
        $this->connection->write($data);
    }
    
    public function send($data)
    {
        $this->write($data);
    }
}