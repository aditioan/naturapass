<?php
/**
 * Created by PhpStorm.
 * User: vincentvalot
 * Date: 09/07/15
 * Time: 15:46
 */

namespace NaturaPass\NotificationBundle\Entity;

interface NotificationInterface {

    /**
     * Returns the data for the link to be created
     *
     * @return array
     */
    public function getLinkData();

    /**
     * Returns the data for the title to be created
     *
     * @return array
     */
    public function getContentData();

    /**
     * Returns the data for the push data to be created
     *
     * @return array
     */
    public function getPushData();

    /**
     * Returns the data for the socket data to be created
     *
     * @return array
     */
    public function getSocketData();
}