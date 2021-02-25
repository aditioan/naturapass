<?php

/**
 * Created by PhpStorm.
 * User: vincentvalot
 * Date: 28/07/15
 * Time: 10:31
 */

namespace Api\ApiBundle\Controller\v2\Serialization;

use Admin\SentinelleBundle\Entity\Card;
use Admin\SentinelleBundle\Entity\CardLabel;
use Admin\SentinelleBundle\Entity\Receiver;
use Admin\SentinelleBundle\Entity\ReceiverRight;
use Mapping\Fixture\Yaml\Category;
use NaturaPass\PublicationBundle\Entity\Favorite;
use NaturaPass\PublicationBundle\Entity\FavoriteAttachment;
use NaturaPass\PublicationBundle\Entity\PublicationColor;
use NaturaPass\UserBundle\Entity\User;

/**
 * Class FavoriteSerialization
 * @package Api\ApiBundle\Controller\v2\Serialization
 *
 * @method static serializeFavorites(array $favorites, User $connected)
 * @method static serializeFavoriteLesss(array $favorites)
 * @method static serializeFavoriteAttachments(array $attachements)
 */
class FavoriteSerialization extends Serialization
{

    /**
     * Serialize a favorite
     *
     * @param Favorite $favorite
     * @param User $connected
     * @return array
     */
    public static function serializeFavorite(Favorite $favorite, User $connected)
    {
        $tree = CategorySerialization::serializeCategoryTree($favorite->getCategory());
        if ($favorite->getSpecific() == 1 && !is_null($favorite->getAnimal())) {
            $tree[] = $favorite->getAnimal()->getName_fr();
        }

        return array(
            'id' => $favorite->getId(),
            'owner' => UserSerialization::serializeUser($favorite->getOwner(), $connected),
            'name' => $favorite->getName(),
            'legend' => $favorite->getLegend(),
            'color' => $favorite->getPublicationcolor() instanceof PublicationColor ? PublicationSerialization::serializePublicationColor($favorite->getPublicationcolor()) : false,
            'sharing' => (!is_null($favorite->getSharing())) ? MainSerialization::serializeSharing($favorite->getSharing(), $connected) : null,
            'groups' => GroupSerialization::serializeGroups($favorite->getGroups(), $connected),
            'shareusers' => UserSerialization::serializeUsers($favorite->getShareusers(), $connected),
            'hunts' => HuntSerialization::serializeHunts($favorite->getHunts(), $connected),
//            'category_name' => (!is_null($favorite->getCategory())) ? $favorite->getCategory()->getName() : "",
            'category_id' => (!is_null($favorite->getCategory())) ? $favorite->getCategory()->getId() : $favorite->getCategory(),
            'card_id' => (!is_null($favorite->getCard())) ? $favorite->getCard()->getId() : $favorite->getCard(),
            'category_tree' => join('/', $tree),
            'specific' => $favorite->getSpecific(),
            'animal' => (!is_null($favorite->getAnimal())) ? $favorite->getAnimal()->getId() : $favorite->getAnimal(),
            'attachments' => FavoriteSerialization::serializeFavoriteCardAttachmentArray($favorite),
        );
    }

    /**
     * Serialize a favorite
     *
     * @param Favorite $favorite
     * @return array
     */
    public static function serializeFavoriteLess(Favorite $favorite)
    {

        return array(
            'id' => $favorite->getId(),
            'name' => $favorite->getName(),
        );
    }


    /**
     * @param FavoriteAttachment $attachment
     * @return array
     */
    public static function serializeFavoriteAttachment(FavoriteAttachment $attachment)
    {
        return array(
            'label' => $attachment->getLabel()->getName(),
            'value' => $attachment->getValue(),
        );
    }

    /**
     * @param FavoriteAttachment $attachment
     * @return array
     */
    public static function serializeFavoriteCardAttachment(Favorite $favorite)
    {
        $labels = array();
        if (!is_null($favorite->getCard())) {
            foreach ($favorite->getCard()->getLabelsVisible() as $label) {
                $favValue = null;
                foreach ($favorite->getAttachments() as $attachement) {
                    if ($attachement->getLabel()->getId() == $label->getId()) {
                        $favValue = $attachement->getValue();
                    }
                }
                $labels[] = array(
                    'label' => $label->getName(),
//                    'value' => (!is_null($favValue) ? $favValue : (($label->getType() == CardLabel::TYPE_INT || $label->getType() == CardLabel::TYPE_FLOAT) ? "0" : "")),
                    'value' => (!is_null($favValue) ? $favValue : (($label->getType() == CardLabel::TYPE_INT || $label->getType() == CardLabel::TYPE_FLOAT) ? "0" : "")),
                );
            }
        }
        return $labels;
    }

    /**
     * @param Attachment $attachment
     * @return array
     */
    public static function serializeFavoriteCardAttachmentArray(Favorite $favorite)
    {
        $aArray = array();
        if (!is_null($favorite->getCard())) {
            foreach ($favorite->getCard()->getLabelsVisible() as $label) {
                foreach ($favorite->getAttachments() as $attachment) {
                    if ($attachment->getLabel()->getId() == $label->getId()) {
                        $contentObject = null;
                        $allowContent = $attachment->getLabel()->allowContentType();
                        if ($allowContent) {
                            foreach ($attachment->getLabel()->getContents() as $content) {
                                if ($content->getId() == intval($attachment->getValue())) {
                                    $contentObject = $content;
                                }
                            }
                        }
                        if (!is_null($contentObject)) {
                            $value = $contentObject->getName();
                        } else {
                            $value = $attachment->getValue();
                        }
                        if ($allowContent && (!isset($aArray[$attachment->getLabel()->getName()]) || !is_array($aArray[$attachment->getLabel()->getName()]))) {
                            $aArray[$attachment->getLabel()->getName()] = array();
                        }
                        if ($allowContent) {
                            $aArray[$attachment->getLabel()->getName()][] = $value;
                        } else {
                            $aArray[$attachment->getLabel()->getName()] = $value;
                        }
                    }
                }
                if (!isset($aArray[$label->getName()])) {
                    $aArray[$label->getName()] = (($label->getType() == CardLabel::TYPE_INT || $label->getType() == CardLabel::TYPE_FLOAT) ? "0" : "");
                }
            }
        }
        $aReturn = array();
        foreach ($aArray as $name => $object) {
            if (is_array($object)) {
                $aReturn[] = array("label" => $name, "values" => $object);
            } else {
                $aReturn[] = array("label" => $name, "value" => $object);
            }
        }
        return $aReturn;
    }
}

