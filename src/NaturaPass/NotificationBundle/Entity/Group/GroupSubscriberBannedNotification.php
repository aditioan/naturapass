<?php
/**
 * Created by PhpStorm.
 * User: vincentvalot
 * Date: 09/07/15
 * Time: 15:02
 */

namespace NaturaPass\NotificationBundle\Entity\Group;

use NaturaPass\GroupBundle\Entity\Group;
use NaturaPass\NotificationBundle\Entity\AbstractNotification;
use Doctrine\ORM\Mapping as ORM;

/**
 * Class GroupSubscriberBannedNotification
 * @package NaturaPass\NotificationBundle\Entity\Group
 *
 * @ORM\Entity
 */
class GroupSubscriberBannedNotification extends AbstractNotification
{

    const TYPE = 'group.subscriber.banned';
//    const PERIOD = true;

    private $group;

    public function __construct(Group $group)
    {
        parent::__construct(array(
            'route' => 'naturapass_group_homepage',
        ));

        $this->group = $group;

        $this->objectID = $this->group->getId();
    }

    /**
     * @return integer
     */
    public function getObjectIDModel()
    {
        return $this->objectID;
    }

    /**
     * Returns the data for the link to be created
     *
     * @return array
     */
    public function getLinkData()
    {
        return array();
    }

    /**
     * Returns the data for the content to be created
     *
     * @return array
     */
    public function getContentData()
    {
        return array(
            '%group%' => $this->group->getName()
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
            'element' => 'group'
        ));
    }


}