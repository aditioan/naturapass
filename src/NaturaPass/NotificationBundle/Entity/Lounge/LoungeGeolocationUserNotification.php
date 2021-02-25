<?php
/**
 * Created by PhpStorm.
 * User: vincentvalot
 * Date: 09/07/15
 * Time: 15:02
 */

namespace NaturaPass\NotificationBundle\Entity\Lounge;

use NaturaPass\LoungeBundle\Entity\Lounge;
use NaturaPass\MainBundle\Entity\Geolocation;
use NaturaPass\NotificationBundle\Entity\AbstractNotification;
use Doctrine\ORM\Mapping as ORM;
use NaturaPass\NotificationBundle\Entity\SocketPoolNotification;

/**
 * Class LoungeGeolocationUserNotification
 * @package NaturaPass\NotificationBundle\Entity\Lounge
 */
class LoungeGeolocationUserNotification extends AbstractNotification implements SocketPoolNotification
{

    const TYPE = 'lounge.geolocation.user';

    private $lounge;

    public function __construct(Lounge $lounge)
    {
        parent::__construct(array(
            'route' => 'naturapass_lounge_show',
            'type' => 'lounge.geolocation.user',
            'persistable' => false,
            'socket' => array(
                'enabled' => true,
                'event_name' => 'api-lounge:subscriber-geolocation'
            ),
            'push' => array(
                'enabled' => true,
                'silent' => true
            )
        ));

        $this->lounge = $lounge;

        $this->objectID = $this->lounge->getId();
    }

    /**
     * @return integer
     */
    public function getObjectIDModel()
    {
        return $this->objectID;
    }

    /**
     * Return the pool name
     *
     * @return string
     */
    public function getPoolName()
    {
        return $this->lounge->getLoungetag();
    }

    /**
     * Returns the data for the link to be created
     *
     * @return array
     */
    public function getLinkData()
    {
        return array(
            'lounge' => $this->lounge->getId()
        );
    }

    /**
     * Returns the data for the content to be created
     *
     * @return array
     */
    public function getContentData()
    {
        return array(
            '%sender%' => $this->sender->getFullName()
        );
    }

    /**
     * Returns the data for the push data to be created
     *
     * @return array
     */
    public function getPushData()
    {
        $subscriber = $this->lounge->isSubscriber($this->sender);

        $data = array();
        if ($subscriber->getGeolocation()) {
            $data['user'] = array(
                'id' => $this->sender->getId(),
                'fullname' => $this->sender->getFullname(),
                'usertag' => $this->sender->getUsertag(),
                'profilepicture' => $this->sender->getProfilePicture() ? $this->sender->getProfilePicture()->getThumb() : '/img/interface/default-avatar.jpg'
            );

            $geolocation = $this->sender->getLastGeolocation();
            if ($geolocation instanceof Geolocation) {
                $data['geolocation'] = array(
                    'latitude' => $geolocation->getLatitude(),
                    'longitude' => $geolocation->getLongitude(),
                    'altitude' => $geolocation->getAltitude(),
                    'created' => $geolocation->getCreated()->format(\DateTime::ATOM)
                );
            }
        } else {
            $data['user'] = $this->sender->getId();
        }

        return array_merge(parent::getPushData(), $data, array(
            'element' => 'lounge',
            'active' => $this->lounge->getGeolocation()
        ));
    }


}