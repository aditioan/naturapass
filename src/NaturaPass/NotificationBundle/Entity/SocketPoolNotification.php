<?php
/**
 * Created by PhpStorm.
 * User: vincentvalot
 * Date: 13/07/15
 * Time: 16:56
 */

namespace NaturaPass\NotificationBundle\Entity;


interface SocketPoolNotification {

    /**
     * Return the pool name
     *
     * @return string
     */
    public function getPoolName();
}