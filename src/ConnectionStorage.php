<?php
/**
 * Created by PhpStorm.
 * User: andre
 * Date: 2017-09-10
 * Time: 1:04 PM
 */

namespace FannyPack\Fcm\Xmpp;


use SplObjectStorage;

class ConnectionStorage extends SplObjectStorage
{
    public function getViableConnection()
    {
        // todo retrieve the newest connection from storage
    }
}