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
    /**
     * return latest connection in storage
     *
     * @return null|object
     */
    public function getViableConnection()
    {
        foreach ($this as $connection){
            if ($this->getInfo()['position'] == $this->count())
            {
                return $connection;
            }
        }
        return null;
    }
}