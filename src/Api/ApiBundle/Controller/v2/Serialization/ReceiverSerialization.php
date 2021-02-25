<?php

/**
 * Created by PhpStorm.
 * User: vincentvalot
 * Date: 28/07/15
 * Time: 10:31
 */

namespace Api\ApiBundle\Controller\v2\Serialization;

use Admin\SentinelleBundle\Entity\Receiver;
use Api\ApiBundle\Controller\v2\Serialization\GroupSerialization;
use Admin\SentinelleBundle\Entity\ReceiverRight;

/**
 * Class ReceiverSerialization
 * @package Api\ApiBundle\Controller\v2\Serialization
 *
 * @method static serializeReceivers(array $receiver, $detail)
 * @method static serializeReceiverGroups(array $receiver)
 * @method static serializeReceiverSearchs(array $receiverright)
 */
class ReceiverSerialization extends Serialization
{

    /**
     * @param Receiver $receiver
     * @return array
     */
    public static function serializeReceiver(Receiver $receiver, $detail = true)
    {
        $array = array(
            'id' => $receiver->getId(),
            'name' => $receiver->getName(),
        );
        if ($detail) {
            $array['photo'] = $receiver->getPhoto();
        }
        return $array;
    }

    /**
     * @param Receiver $receiver
     */
    public static function serializeReceiverGroup(Receiver $receiver)
    {
        $arrayGroup = GroupSerialization::serializeGroupSearchs($receiver->getGroups());
        $array = array(
            'id' => $receiver->getId(),
            'name' => $receiver->getName(),
            'groups' => count($arrayGroup) ? $arrayGroup : ((object)array())
        );
        return $array;
    }

    /**
     * @param ReceiverRight $receiverright
     */
    public static function serializeReceiverRightSearch(ReceiverRight $receiverright)
    {
        $array = array(
            'id' => $receiverright->getReceiver()->getId(),
            'name' => $receiverright->getReceiver()->getName()
        );
        return $array;
    }

    /**
     * @param Receiver $receiver
     */
    public static function serializeReceiverSearch(Receiver $receiver)
    {
        $array = array(
            'id' => $receiver->getId(),
            'name' => $receiver->getName()
        );
        return $array;
    }

}
