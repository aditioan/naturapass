<?php

/**
 * Created by PhpStorm.
 * User: vincentvalot
 * Date: 09/07/15
 * Time: 15:02
 */

namespace NaturaPass\NotificationBundle\Entity\Group\SocketOnly;

use Api\ApiBundle\Controller\v1\ApiRestController;
use NaturaPass\GroupBundle\Entity\Group;
use NaturaPass\GroupBundle\Entity\GroupUser;
use NaturaPass\NotificationBundle\Entity\AbstractNotification;
use Doctrine\ORM\Mapping as ORM;
use NaturaPass\NotificationBundle\Entity\SocketPoolNotification;

/**
 * Class GroupSubscriberRemoveNotification
 * @package NaturaPass\NotificationBundle\Entity\Group\SocketOnly
 */
class GroupSubscriberRemoveNotification extends AbstractNotification implements SocketPoolNotification
{

    const TYPE = 'group.subscriber.remove';

    private $group;
    private $subscriber;

    public function __construct(Group $group, GroupUser $subscriber)
    {
        parent::__construct(array(
            'route' => 'naturapass_group_show',
            'type' => 'group.subscriber.remove',
            'persistable' => false,
            'socket' => array(
                'enabled' => true,
                'event_name' => 'api-group:removemember'
            ),
            'push' => array(
                'enabled' => false
            )
        ));

        $this->group = $group;
        $this->subscriber = $subscriber;

        $this->objectID = $this->group->getId();
    }

    /**
     * {@inheritdoc}
     */
    public function getPoolName()
    {
        return $this->group->getGrouptag();
    }

    /**
     * {@inheritdoc}
     */
    public function getSocketData()
    {
        return array('id' => $this->subscriber->getUser()->getId());
    }

    /**
     * {@inheritdoc}
     */
    public function getLinkData()
    {
        return array(
            'grouptag' => $this->group->getGrouptag()
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
