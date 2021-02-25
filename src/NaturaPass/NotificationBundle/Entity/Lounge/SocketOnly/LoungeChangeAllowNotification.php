<?php

/**
 * Created by PhpStorm.
 * User: vincentvalot
 * Date: 09/07/15
 * Time: 15:02
 */

namespace NaturaPass\NotificationBundle\Entity\Lounge\SocketOnly;

use Api\ApiBundle\Controller\v2\Serialization\HuntSerialization;
use NaturaPass\LoungeBundle\Entity\Lounge;
use NaturaPass\NotificationBundle\Entity\AbstractNotification;
use Doctrine\ORM\Mapping as ORM;
use NaturaPass\NotificationBundle\Entity\SocketPoolNotification;

/**
 * Class LoungeChangeAllowNotification
 * @package NaturaPass\NotificationBundle\Entity\Lounge\SocketOnly
 */
class LoungeChangeAllowNotification extends AbstractNotification
{

    const TYPE = 'lounge.change_allow';

    private $lounge;

    public function __construct(Lounge $lounge)
    {
        parent::__construct(array(
            'route' => 'naturapass_lounge_show',
            'type' => 'lounge.change_allow',
            'persistable' => false,
            'socket' => array(
                'enabled' => true,
                'event_name' => 'api-lounge:change-allow'
            ),
            'push' => array(
                'enabled' => false
            )
        ));

        $this->lounge = $lounge;

        $this->objectID = $this->lounge->getId();
    }

    /**
     * {@inheritdoc}
     */
    public function getSocketData()
    {
        return HuntSerialization::serializeHuntAllow($this->lounge);
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
        return array();
    }

}
