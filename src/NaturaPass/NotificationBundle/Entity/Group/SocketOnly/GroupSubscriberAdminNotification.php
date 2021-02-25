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
 * Class GroupSubscriberAdminNotification
 * @package NaturaPass\NotificationBundle\Entity\Group\SocketOnly
 */
class GroupSubscriberAdminNotification extends AbstractNotification implements SocketPoolNotification {

    const TYPE = 'group.user_admin';

    private $group;
    private $subscriber;

    public function __construct(Group $group, GroupUser $subscriber) {
        parent::__construct(array(
            'route' => 'naturapass_group_show',
            'type' => 'group.user_admin',
            'persistable' => false,
            'socket' => array(
                'enabled' => true,
                'event_name' => 'api-group:user-admin'
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
    public function getPoolName() {
        return $this->group->getGrouptag();
    }

    /**
     * {@inheritdoc}
     */
    public function getSocketData() {
        return ApiRestController::getFormatGroupSubscriber($this->subscriber);
    }

    /**
     * {@inheritdoc}
     */
    public function getLinkData() {
        return array(
            'group' => $this->group->getId(),
            'grouptag' => $this->group->getGrouptag()
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getContentData() {
        return array(
            '%sender%' => $this->sender->getFullName()
        );
    }

}
