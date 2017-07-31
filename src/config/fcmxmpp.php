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
    'host' => env('FCM_XMPP_HOST', ''),
    'port' => env('FCM_XMPP_PORT', '')
];