<?php
/**
 * Created by PhpStorm.
 * User: andre
 * Date: 2017-09-02
 * Time: 11:44 AM
 */

namespace FannyPack\Fcm\Xmpp;


use DOMElement;
use FannyPack\Utils\Fcm\Events\AbstractError;
use FannyPack\Utils\Fcm\Events\ConnectionDrainage;
use FannyPack\Utils\Fcm\Events\DeviceMessageRateExceeded;
use FannyPack\Utils\Fcm\Events\InvalidJson;
use FannyPack\Utils\Fcm\Events\MessageAcknowledged;
use FannyPack\Utils\Fcm\Events\MessageReceiptReceived;
use FannyPack\Utils\Fcm\Events\MessageReceived;
use FannyPack\Utils\Fcm\Events\NewConnectionEstablished;
use FannyPack\Utils\Fcm\Events\InvalidDeviceRegistration;
use FannyPack\Utils\Fcm\Events\RegistrationExpired;
use FannyPack\Utils\Fcm\Messages\AckMessage;
use DOMDocument;
use FannyPack\Utils\Fcm\XmppPacket;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Events\Dispatcher;
use Illuminate\Support\Facades\Log;
use React\Socket\ConnectionInterface;

class XmppParser
{
    /**
     * @var DOMDocument
     */
    protected $document;

    /**
     * @var XmppConfig
     */
    protected $config;

    /**
     * @var ConnectionInterface
     */
    protected $connection;

    /**
     * @var Dispatcher
     */
    protected $events;

    /**
     * @var XmppClient
     */
    protected $client;

    /**
     * @var Application
     */
    protected $app;

    /**
     * @var int
     */
    protected $options = LIBXML_NOWARNING | LIBXML_NOERROR;

    /**
     * Parser constructor.
     * @param DOMDocument $document
     * @param Application $app
     */
    public function __construct(DOMDocument $document, Application $app)
    {
        $this->document = $document;
        $this->app = $app;
        $this->config = $this->app[XmppConfig::class];
        $this->events = $this->app['events'];
    }

    /**
     * parse chuck received from connection
     * @param $data
     * @param ConnectionInterface $connection
     *
     */
    public function parseData($data, ConnectionInterface $connection)
    {
        $this->client = $this->app[XmppClient::class];
        $this->connection = $connection;
        $this->document->loadXML($data, $this->options);

        foreach ($this->document->childNodes as $node){
            switch ($node->localName)
            {
                case 'stream:features':
                    $this->parseStreamFeatures($node);
                    break;
                case 'success':
                    $this->parseSuccess();
                    break;
                case 'failure':
                    $this->client->connect();
                    break;
                case 'iq':
                    if ($node->getAttribute('type') == 'result')
                    {
                        // new connection successfully established
                        $jid = $node->firstChild->firstChild->textContent;
                        $this->events->fire(new NewConnectionEstablished($jid));
                    }
                    break;
                case 'message':
                    $this->parseMessage($node);
                    break;
                default:
                    break;
            }
        }
    }

    /**
     * write back to connection
     *
     * @param $data
     */
    private function write($data)
    {
        $this->connection->write($data);
    }

    /**
     * @param DOMElement $node
     */
    private function parseStreamFeatures(DOMElement $node)
    {
        foreach ($node->childNodes as $_node)
        {
            switch ($_node->localName){
                case 'mechanisms':
                    $this->write('<auth mechanism="PLAIN" xmlns="urn:ietf:params:xml:ns:xmpp-sasl">' . base64_encode(chr(0) . $this->config->getSenderId() . '@gcm.googleapis.com' . chr(0) . $this->config->getApiKey()) . '</auth>');
                    break;
                case 'bind':
                    $this->write('<iq type="set"><bind xmlns="urn:ietf:params:xml:ns:xmpp-bind"></bind></iq>');
                    break;
                case 'session':
                    $this->write('<iq type="set"><bind xmlns="urn:ietf:params:xml:ns:xmpp-bind"></bind></iq>');
                    break;
                default:
                    continue;
            }
        }
    }

    /**
     * respond to a success stanza during authentication
     */
    private function parseSuccess()
    {
        $this->write('<stream:stream to="' . $this->config->getHostDomain() . '" version="1.0" xmlns="jabber:client" xmlns:stream="http://etherx.jabber.org/streams">');
    }

    /**
     * parse incoming Fcm Message
     *
     * @param DOMElement $node
     */
    private function parseMessage(DOMElement $node)
    {
        if ($node->getAttribute('type') == 'error') {
            foreach ($node->childNodes as $_node)
            {
                if ($_node->localName == 'error')
                {
                    // todo handle error response
                    Log::info("===error===");
                    Log::error($_node->textContent);
                }
            }
        }elseif ($node->firstChild->localName == 'gcm' && ($json = $node->firstChild->textContent) && ($data = json_decode($json)) && @$data->message_type && @$data->message_id) {

            switch ($data->message_type)
            {
                case 'ack':
                    // message acknowledgement received
                    $this->events->fire(new MessageAcknowledged($data->message_id));
                    break;
                case 'nack':
                    switch (strtolower($data->error))
                    {
                        case 'bad_registration':
                            // unregistered/uninstalled app
                            $this->events->fire(new InvalidDeviceRegistration($data->from));
                            break;
                        case 'device_unregistered':
                            // unregistered/uninstalled app
                            $this->events->fire(new InvalidDeviceRegistration($data->from));
                            break;
                        case 'device_message_rate_exceeded':
                            // device rate exceeded
                            $this->events->fire(new DeviceMessageRateExceeded($data->from, null, $data->message_id));
                            break;
                        case 'invalid_json':
                            // invalid json
                            $this->events->fire(new InvalidJson($data->from, $data->description));
                            break;
                        default:
                            // unknown error
                            $this->events->fire(new AbstractError($data->error, $data->from, $data->description));
                            break;
                    }
                    break;
                case 'control':
                    if ($data->control_type == 'CONNECTION_DRAINING')
                    {
                        // connection server connection drainage
                        $this->events->fire(new ConnectionDrainage());
                    }
                    break;
                case 'receipt':
                    // ack for receipt of message receipt before processing receipt
                    $this->sendAck($data->message_id, $data->data->device_registration_id);

                    // message receipt
                    $this->events->fire(new MessageReceiptReceived((array)$data));
                    break;
                default:
                    break;
            }

            if (@$data->registration_id) {
                // registration expired for token
                $this->events->fire(new RegistrationExpired($data->from, $data->registration_id));
            }

        } elseif (($json = $node->firstChild->textContent) && ($mData = json_decode($json)) && ($client_token = $mData->from) && ($client_message = $mData->data)) {
            // ack for receipt before processing message
            $this->sendAck($mData->message_id, $mData->from);

            // message received
            $this->events->fire(new MessageReceived((array)$mData));
        }
    }

    /**
     * send ack message after every message receipt
     *
     * @param $message_id
     * @param $registration_id
     */
    protected function sendAck($message_id, $registration_id)
    {
        $packet = (new XmppPacket())->setTo($registration_id);

        $ack_message = new AckMessage($packet, $message_id);
        $this->write((string)$ack_message);
    }
}