<?php
/**
 * Created by PhpStorm.
 * User: andre
 * Date: 2017-07-29
 * Time: 6:13 PM
 */

return [
    'apiKey' => env('FCM_SERVER_KEY', ''),
    'senderId' => env('FCM_SENDER_ID', ''),
    'port' => env('FCM_XMPP_PORT', ''),
    'timeout' => env('FCM_CONNECTION_TIMEOUT', 60.0),
    'context' => [
        'verify_peer' => env('VERIFY_PEER', false),
        'verify_peer_name' => env('VERIFY_PEER_NAME', false)
    ]
];