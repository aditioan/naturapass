<?php
/**
 * Created by PhpStorm.
 * User: vincentvalot
 * Date: 09/07/15
 * Time: 15:02
 */

namespace NaturaPass\NotificationBundle\Entity\Lounge\SocketOnly;

use NaturaPass\LoungeBundle\Entity\Lounge;
use NaturaPass\LoungeBundle\Entity\LoungeNotMember;
use NaturaPass\NotificationBundle\Entity\AbstractNotification;
use Doctrine\ORM\Mapping as ORM;
use NaturaPass\NotificationBundle\Entity\SocketPoolNotification;

/**
 * Class LoungeNotUserAddNotification
 * @package NaturaPass\NotificationBundle\Entity\Lounge\SocketOnly
 */
class LoungeNotUserRemoveNotification extends AbstractNotification implements SocketPoolNotification
{

    const TYPE = 'lounge.user_removenotmember';

    private $lounge;
    private $subscriber;

    public function __construct(Lounge $lounge, LoungeNotMember $subscriber)
    {
        parent::__construct(array(
            'route' => 'naturapass_lounge_show',
            'persistable' => false,
            'socket' => array(
                'enabled' => true,
                'event_name' => 'api-lounge:removenotmember'
            ),
            'push' => array(
                'enabled' => false
            )
        ));

        $this->lounge = $lounge;
        $this->subscriber = $subscriber;

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
        return array('id' => $this->subscriber->getId());
    }

    /**
     * {@inheritdoc}
     */
    public function getLinkData()
    {
        return array(
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
