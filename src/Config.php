<?php
/**
 * Created by PhpStorm.
 * User: andre
 * Date: 2017-09-06
 * Time: 9:18 PM
 */

namespace FannyPack\Fcm\Xmpp;


use Illuminate\Contracts\Foundation\Application;

class Config
{
    /**
     * @var $apiKey
     */
    protected $apiKey;

    /**
     * @var $senderId
     */
    protected $senderId;

    /**
     * @var string
     */
    protected $host_ip = "74.125.133.188";

    /**
     * @var string
     */
    protected $host_domain = "fcm-xmpp.googleapis.com";

    /**
     * @var $port
     */
    protected $port;

    /**
     * @var float $timeout
     */
    protected $timeout;

    /**
     * @var array
     */
    protected $context;

    /**
     * @var Application
     */
    protected $app;

    /**
     * Config constructor.
     * @param Application $app
     */
    public function __construct(Application $app)
    {
        $this->app = $app;
        $this->setConfigs();
    }

    /**
     *  set server configurations from file
     */
    protected function setConfigs()
    {
        $apiKey = $this->app['config']['fcmxmpp.apiKey'];
        $senderId = $this->app['config']['fcmxmpp.senderId'];
        $port = $this->app['config']['fcmxmpp.port'];
        $context = $this->app["config"]["fcmxmpp.context"];
        $timeout = $this->app["config"]["fcmxmpp.timeout"];

        if (!$apiKey)
            throw new \InvalidArgumentException("FCM Server key not specified");

        if (!$senderId)
            throw new \InvalidArgumentException("FCM Sender Id not specified");

        if (!$port)
            throw new \InvalidArgumentException("FCM Xmpp Port not specified");

        $this->apiKey = $apiKey;
        $this->senderId = $senderId;
        $this->port = $port;
        $this->context = $context;

        if (!$timeout)
            $this->timeout = 60.0;
    }

    /**
     * @return mixed
     */
    public function getApiKey()
    {
        return $this->apiKey;
    }

    /**
     * @return mixed
     */
    public function getSenderId()
    {
        return $this->senderId;
    }

    /**
     * @return mixed
     */
    public function getPort()
    {
        return $this->port;
    }

    /**
     * @return float
     */
    public function getTimeout()
    {
        return $this->timeout;
    }

    /**
     * @param float $timeout
     */
    public function setTimeout($timeout)
    {
        $this->timeout = $timeout;
    }

    /**
     * @return array
     */
    public function getContext()
    {
        return $this->context;
    }

    /**
     * @param array $context
     */
    public function setContext($context)
    {
        $this->context = $context;
    }

    /**
     * @return string
     */
    public function getHostIp()
    {
        return $this->host_ip;
    }

    /**
     * @return string
     */
    public function getHostDomain()
    {
        return $this->host_domain;
    }
}