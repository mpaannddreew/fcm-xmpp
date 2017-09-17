<?php
/**
 * Created by PhpStorm.
 * User: andre
 * Date: 2017-09-02
 * Time: 11:44 AM
 */

namespace FannyPack\Fcm\Xmpp;


use DOMElement;
use FannyPack\Utils\Fcm\Events\ConnectionDrainageEvent;
use FannyPack\Utils\Fcm\Events\MessageAcknowledgementEvent;
use FannyPack\Utils\Fcm\Events\MessageReceivedEvent;
use FannyPack\Utils\Fcm\Events\RegistrationErrorEvent;
use FannyPack\Utils\Fcm\Events\RegistrationExpiryEvent;
use FannyPack\Utils\Fcm\Messages\AckMessage;
use FannyPack\Utils\Fcm\Messages\Payload;
use FannyPack\Utils\Fcm\Packet;
use DOMDocument;
use Illuminate\Events\Dispatcher;
use Illuminate\Support\Facades\Log;
use React\Socket\ConnectionInterface;

class Parser
{
    /**
     * @var DOMDocument
     */
    protected $document;

    /**
     * @var Config
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
     * Parser constructor.
     * @param DOMDocument $document
     * @param Config $config
     * @param Dispatcher $events
     */
    public function __construct(DOMDocument $document, Config $config, Dispatcher $events)
    {
        $this->document = $document;
        $this->config = $config;
        $this->events = $events;
    }

    /**
     * parse chuck received from connection
     * @param $data
     * @param ConnectionInterface $connection
     */
    public function parseData($data, ConnectionInterface $connection)
    {
        $this->connection = $connection;
        $this->document->loadXML($data, LIBXML_NOWARNING | LIBXML_NOERROR);

        foreach ($this->document->childNodes as $node){
            if ($node->localName == 'stream:features') {
                $this->parseStreamFeatures($node);
            }
            elseif ($node->localName == 'success')
            {
                $this->parseSuccess();
            }
            elseif ($node->localName == 'failure') {} // todo failure response
            elseif ($node->localName == 'iq' && $node->getAttribute('type') == 'result'){
                Log::info("connected");
                $jid = $node->firstChild->firstChild->textContent;
                Log::info($jid);
            }
            elseif ($node->localName == 'message') {
                $this->parseMessage($node);
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
        $this->write('<stream:stream to="' . Config::HOST_DOMAIN . '" version="1.0" xmlns="jabber:client" xmlns:stream="http://etherx.jabber.org/streams">');
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
                    Log::info("===error===");
                    Log::error($_node->textContent); // todo handle error response
                }
            }
        }elseif ($node->firstChild->localName == 'gcm' && ($json = $node->firstChild->textContent) && ($data = json_decode($json)) && @$data->message_type && @$data->message_id) {
            if ($data->message_type == 'ack') {
                // message acknowledgement received
                $this->events->fire(new MessageAcknowledgementEvent($data->message_id));
            } elseif ($data->message_type == 'nack') {
                Log::info("===nack===");
                Log::info("$data->error ($data->error_description) $data->from");
                if ($data->error == 'BAD_REGISTRATION' || $data->error == 'DEVICE_UNREGISTERED')
                {
                    // unregistered/uninstalled app
                    $this->events->fire(new RegistrationErrorEvent($data->from));
                }
                else
                {}// todo @call_user_func($this->onFail, $data->message_id, $data->error, $data->error_description);
            }

            if ($data->message_type == 'control' && $data->control_type == 'CONNECTION_DRAINING') {
                // connection server connection drainage
                $this->events->fire(new ConnectionDrainageEvent($data));
            }

            if (@$data->registration_id) {
                // registration expired for token
                $this->events->fire(new RegistrationExpiryEvent($data->from, $data->registration_id));
            }
        } elseif (($json = $node->firstChild->textContent) && ($mData = json_decode($json)) && ($client_token = $mData->from) && ($client_message = $mData->data)) {
            // ack for receipt before processing message
            $this->sendAck($mData);

            // message received
            $this->events->fire(new MessageReceivedEvent($mData));
        }
    }

    protected function sendAck($mData)
    {
        $packet = (new Packet())->setPipeline(Packet::XMPP_PIPELINE)->setPayload(new Payload())->setTo($mData->from);
        $ack_message = new AckMessage($packet, $mData->message_id);
        $this->write((string)$ack_message);
    }
}