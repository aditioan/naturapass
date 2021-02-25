<?php

/**
 * Created by PhpStorm.
 * User: vincentvalot
 * Date: 09/07/15
 * Time: 15:02
 */

namespace NaturaPass\NotificationBundle\Entity\Group\SocketOnly;

use Api\ApiBundle\Controller\v2\Serialization\GroupSerialization;
use NaturaPass\GroupBundle\Entity\Group;
use NaturaPass\NotificationBundle\Entity\AbstractNotification;
use Doctrine\ORM\Mapping as ORM;
use NaturaPass\NotificationBundle\Entity\SocketPoolNotification;

/**
 * Class GroupChangeAllowNotification
 * @package NaturaPass\NotificationBundle\Entity\Group\SocketOnly
 */
class GroupChangeAllowNotification extends AbstractNotification
{

    const TYPE = 'group.change_allow';

    private $group;

    public function __construct(Group $group)
    {
        parent::__construct(array(
            'route' => 'naturapass_group_show',
            'type' => 'group.change_allow',
            'persistable' => false,
            'socket' => array(
                'enabled' => true,
                'event_name' => 'api-group:change-allow'
            ),
            'push' => array(
                'enabled' => false
            )
        ));

        $this->group = $group;

        $this->objectID = $this->group->getId();
    }

    /**
     * {@inheritdoc}
     */
    public function getSocketData()
    {
        return GroupSerialization::serializeGroupAllow($this->group);
    }

    /**
     * {@inheritdoc}
     */
    public function getLinkData()
    {
        return array(
            'group' => $this->group->getId(),
            'grouptag' => $this->group->getGrouptag()
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
