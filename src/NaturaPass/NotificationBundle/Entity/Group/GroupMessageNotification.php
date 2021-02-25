<?php

/**
 * Created by PhpStorm.
 * User: vincentvalot
 * Date: 09/07/15
 * Time: 15:02
 */

namespace NaturaPass\NotificationBundle\Entity\Group;

use Api\ApiBundle\Controller\v1\ApiRestController;
use NaturaPass\GroupBundle\Entity\Group;
use NaturaPass\GroupBundle\Entity\GroupMessage;
use NaturaPass\GroupBundle\Entity\GroupUser;
use NaturaPass\NotificationBundle\Entity\AbstractNotification;
use Doctrine\ORM\Mapping as ORM;
use NaturaPass\NotificationBundle\Entity\SocketPoolNotification;

/**
 * Class GroupMessageNotification
 * @package NaturaPass\NotificationBundle\Entity\Group
 *
 * @ORM\Entity
 */
class GroupMessageNotification extends AbstractNotification implements SocketPoolNotification
{

    const TYPE = 'group.chat.new_message';

    private $group;
    private $message;

    public function __construct(Group $group, GroupMessage $message)
    {
        parent::__construct(array(
            'route' => 'naturapass_group_show',
            'socket' => array(
                'enabled' => true,
                'event_name' => 'api-group:message'
            )
        ));

        $this->group = $group;
        $this->message = $message;

        $this->objectID = $this->group->getId();
        $this->visible = false;
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
        return $this->group->getGrouptag();
    }

    /**
     * Returns the data for the link to be created
     *
     * @return array
     */
    public function getLinkData()
    {
        return array(
            'grouptag' => $this->group->getGrouptag()
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
            '%sender%' => $this->sender->getFullName(),
            '%group%' => $this->group->getName(),
            '%text%' => $this->message->getContent()
        );
    }

    /**
     * Returns the data for the push data to be created
     *
     * @return array
     */
    public function getPushData()
    {
        return array_merge(parent::getPushData(), array(
            'element' => 'group',
            'content' => $this->message->getContent(),
            'type' => 'group.chat.new_message',
            'message' => $this->group->getName(),
            'user' => $this->sender->getId()
        ));
    }

    /**
     * Returns the data for the socket data to be created
     *
     * @return array
     */
    public function getSocketData()
    {
        return ApiRestController::getFormatGroupMessage($this->message);
    }

}
