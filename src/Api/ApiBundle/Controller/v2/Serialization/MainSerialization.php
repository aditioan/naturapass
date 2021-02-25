<?php

/**
 * Created by PhpStorm.
 * User: vincentvalot
 * Date: 28/07/15
 * Time: 10:57
 */

namespace Api\ApiBundle\Controller\v2\Serialization;

use Admin\NewsBundle\Entity\BaseMediaNews;
use NaturaPass\MainBundle\Entity\AbstractGeolocation;
use NaturaPass\MainBundle\Entity\Sharing;
use NaturaPass\MediaBundle\Entity\BaseMedia;
use NaturaPass\UserBundle\Entity\User;
use NaturaPass\UserBundle\Entity\UserAddress;

class MainSerialization extends Serialization
{

    /**
     * @param BaseMedia $media
     * @return array
     */
    public static function serializeMedia(BaseMedia $media, $full = false)
    {
        $width = $height = 0;
        if (file_exists($_SERVER["DOCUMENT_ROOT"] . $media->getResize())) {
            list($width, $height) = getimagesize($_SERVER["DOCUMENT_ROOT"] . $media->getResize());
        }
        $array = array(
            'id' => $media->getId(),
            'type' => $media->getType(),
            'path' => $media->getResize(),
            'size' => array("width" => $width, "height" => $height)
        );
        if ($full) {
            $array = array_merge($array, array(
                'name' => $media->getName(),
                'state' => $media->getState(),
                'created' => $media->getCreated()->format(\DateTime::ATOM),
                'updated' => $media->getUpdated()->format(\DateTime::ATOM)
            ));
        }
        return $array;
    }

    /**
     * @param BaseMediaNews $media
     * @return array
     */
    public static function serializeNewsMedia(BaseMediaNews $media)
    {
        return array(
            'id' => $media->getId(),
            'name' => $media->getName(),
            'type' => $media->getType(),
            'state' => $media->getState(),
            'path' => $media->getResize(),
            'created' => $media->getCreated()->format(\DateTime::ATOM),
            'updated' => $media->getUpdated()->format(\DateTime::ATOM)
        );
    }

    /**
     * @param UserAddress $address
     * @return array
     */
    public static function serializeAddress(UserAddress $address)
    {
        return array_merge(
            self::serializeGeolocation($address), array('title' => $address->getTitle(), 'favorite' => $address->isFavorite())
        );
    }

    /**
     * @param AbstractGeolocation $geolocation
     * @return array
     */
    public static function serializeGeolocation(AbstractGeolocation $geolocation)
    {
        return array(
            'id' => $geolocation->getId(),
            'latitude' => $geolocation->getLatitude(),
            'longitude' => $geolocation->getLongitude(),
            'altitude' => $geolocation->getAltitude(),
            'address' => $geolocation->getAddress(),
            'created' => $geolocation->getCreated()->format(\DateTime::ATOM),
        );
    }

    /**
     * @param Sharing $sharing
     * @param User $connected
     * @return array
     */
    public static function serializeSharing(Sharing $sharing, User $connected)
    {
        return array(
            'share' => $sharing->getShare(),
            'withouts' => UserSerialization::serializeUsers($sharing->getWithouts(), $connected)
        );
    }

}
