<?php

/**
 * Created by PhpStorm.
 * User: vincentvalot
 * Date: 09/07/15
 * Time: 15:02
 */

namespace NaturaPass\NotificationBundle\Entity\Lounge\SocketOnly;

use Api\ApiBundle\Controller\v1\ApiRestController;
use Api\ApiBundle\Controller\v2\Serialization\PublicationSerialization;
use NaturaPass\LoungeBundle\Entity\Lounge;
use NaturaPass\LoungeBundle\Entity\LoungeUser;
use NaturaPass\NotificationBundle\Entity\AbstractNotification;
use Doctrine\ORM\Mapping as ORM;
use NaturaPass\NotificationBundle\Entity\SocketPoolNotification;
use NaturaPass\PublicationBundle\Entity\Publication;

/**
 * Class LoungeSubscriberAdminNotification
 * @package NaturaPass\NotificationBundle\Entity\Lounge\SocketOnly
 */
class LoungePublicationNotification extends AbstractNotification implements SocketPoolNotification
{

    const TYPE = 'lounge.map.new_publication';

    private $lounge;
    private $publication;

    public function __construct(Lounge $lounge, Publication $publication)
    {
        parent::__construct(array(
            'route' => 'naturapass_lounge_show',
            'type' => 'lounge.map.new_publication',
            'persistable' => false,
            'socket' => array(
                'enabled' => true,
                'event_name' => 'api-lounge:publication'
            ),
            'push' => array(
                'enabled' => false
            )
        ));

        $this->lounge = $lounge;
        $this->publication = $publication;

        $this->objectID = $this->lounge->getId();
    }

    /**
     * {@inheritdoc}
     */
    public function getPoolName()
    {
        return $this->lounge->getLoungetag();
    }

    /**
     * {@inheritdoc}
     */
    public function getSocketData()
    {
        return PublicationSerialization::serializePublication($this->publication, $this->sender);
    }

    /**
     * {@inheritdoc}
     */
    public function getLinkData()
    {
        return array(
            'lounge' => $this->lounge->getId(),
            'loungetag' => $this->lounge->getLoungetag()
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getContentData()
    {
        return array(
            '%sender%' => $this->sender->getFullName()
        );
    }
}
