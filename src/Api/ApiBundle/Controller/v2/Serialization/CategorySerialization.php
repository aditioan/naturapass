<?php

/**
 * Created by PhpStorm.
 * User: vincentvalot
 * Date: 28/07/15
 * Time: 10:31
 */

namespace Api\ApiBundle\Controller\v2\Serialization;

use NaturaPass\PublicationBundle\Entity\Publication;
use NaturaPass\UserBundle\Entity\User;
use Admin\SentinelleBundle\Entity\Category;
use Admin\SentinelleBundle\Entity\CategoryMedia;
use Admin\SentinelleBundle\Entity\Zone;
use Admin\SentinelleBundle\Entity\Receiver;

/**
 * Class CategorySerialization
 * @package Api\ApiBundle\Controller\v2\Serialization
 *
 * @method static serializeCategorys(array $category, $labels = true, $forceVisible = false, User $connected)
 * @method static serializeCategoryCaches(array $category, $labels = true, $forceVisible = false)
 * @method static serializeCategoryPublications(array $category, $zone, $arrayOk, $array_receiver, $labels = true, $forceVisible = false, User $connected))
 */
class CategorySerialization extends Serialization
{

    /**
     * @param Category $category
     * @return array
     */
    public static function serializeCategoryTree($category)
    {
        $array = array();
        if ($category instanceof Category) {
            if (is_object($category->getParent())) {
                $array = CategorySerialization::serializeCategoryTree($category->getParent());
            }
            $array[] = $category->getName();
        }
        return $array;
    }

    /**
     * @param Category $category
     * @param boolean $labels
     * @param boolean $forceVisible
     * @param User $connected
     * @return array
     */
    public static function serializeCategory(Category $category, $labels = true, $forceVisible = false, User $connected)
    {
        if (($category->getVisible() || $forceVisible) && $category->userGroupAuthorised($connected)) {
            $arrayChild = CategorySerialization::serializeCategorys($category->getChildren(), $labels, $forceVisible, $connected);
//            $arrayReceiver = ReceiverSerialization::serializeReceiverRightSearchs($category->getReceiverrights());
            $arrayReceiver = null;
            $array = array(
                'id' => $category->getId(),
                'name' => $category->getName(),
                'search' => $category->getSearch(),
                'children' => count($arrayChild) ? $arrayChild : ((object)array()),
                'receivers' => count($arrayReceiver) ? $arrayReceiver : ((object)array())
            );
            if (!is_null($category->getCard())) {
                $array = array_merge($array, array('card' => CardSerialization::serializeCard($category->getCard(), $labels)));
            }
            return $array;
        }
    }

    /**
     * @param Category $category
     * @param boolean $labels
     * @param boolean $forceVisible
     * @return array
     */
    public static function serializeCategoryCache(Category $category, $labels = true, $forceVisible = false, $receiver = false)
    {
        $receiverRight = ($receiver instanceof Receiver) ? $category->hasReceiverright($receiver) : true;
        if (($category->getVisible() || $forceVisible) && $receiverRight) {
            $arrayChild = CategorySerialization::serializeCategoryCaches($category->getChildren(), $labels, $forceVisible, $receiver);
            $arrayGroup = GroupSerialization::serializeGroupSearchs($category->getGroups());
            $arrayReceiver = ($receiver instanceof Receiver) ? ReceiverSerialization::serializeReceiverSearch($receiver) : null;
            (count($arrayReceiver))?$arrayReceiver=array($arrayReceiver):"";
            $array = array(
                'id' => $category->getId(),
                'name' => $category->getName(),
                'search' => $category->getSearch(),
                'children' => count($arrayChild) ? $arrayChild : ((object)array()),
                'groups' => count($arrayGroup) ? $arrayGroup : ((object)array()),
                'receivers' => count($arrayReceiver) ? $arrayReceiver : ((object)array())
            );
            if (!is_null($category->getCard())) {
                $array = array_merge($array, array('card' => CardSerialization::serializeCard($category->getCard(), $labels)));
            }
            return $array;
        }
    }

    /**
     * @param Category $category
     * @param Zone $zone
     * @param array $arrayOk
     * @param array $array_receiver
     * @param boolean $labels
     * @param boolean $forceVisible
     * @param User $connected
     * @return array
     */
    public static function serializeCategoryPublication(Category $category, Zone $zone, $arrayOk = array(), $array_receiver = array(), $labels = true, $forceVisible = false, User $connected)
    {
        if (($category->getVisible() || $forceVisible) && array_key_exists($category->getId(), $arrayOk) && $category->userGroupAuthorised($connected)) {
            $arrayChild = CategorySerialization::serializeCategoryPublications($category->getChildren(), $zone, $arrayOk, $array_receiver, $labels, $forceVisible, $connected);
            $arrayGroup = GroupSerialization::serializeGroupSearchs($category->getGroups());
            $arrayReceiver = array();
            foreach ($arrayOk[$category->getId()] as $receiver_id) {
                if (isset($array_receiver[$receiver_id])) {
                    $arrayReceiver[] = $array_receiver[$receiver_id];
                }
            }
            $array = array(
                'id' => $category->getId(),
                'name' => $category->getName(),
                'search' => $category->getSearch(),
                'children' => count($arrayChild) ? $arrayChild : ((object)array()),
                'groups' => count($arrayGroup) ? $arrayGroup : ((object)array()),
                'receivers' => count($arrayReceiver) ? $arrayReceiver : ((object)array())
            );
            if (count($arrayChild) == 0) {
                if (is_object($zone)) {
                    $cardsZone = $category->getCardszone($zone);
                    if (!is_null($cardsZone)) {
                        foreach ($cardsZone as $cardCategoryZone) {
                            $array = array_merge($array, array('card' => CardSerialization::serializeCard($cardCategoryZone->getCard(), $labels)));
                        }
                    }
                }
            }
            return $array;
        }
    }

    /**
     * @param array $receivers
     * @param User $connected
     * @return String
     */
    public static function serializeCategoryToShow($receivers, User $connected)
    {
        $treeToDisplay = "default";
        foreach ($receivers as $receiver) {
            foreach ($connected->getAllGroups() as $group) {
                if ($receiver->hasGroup($group)) {
                    $treeToDisplay = "receiver_" . $receiver->getId();
                }
            }
        }
        return $treeToDisplay;
    }

}
