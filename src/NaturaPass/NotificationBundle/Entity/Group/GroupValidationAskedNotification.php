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
 * Class GroupValidationAskedNotification
 * @package NaturaPass\NotificationBundle\Entity\Group
 *
 * @ORM\Entity
 */
class GroupValidationAskedNotification extends AbstractNotification
{

    const TYPE = 'group.join.valid-asked';

    private $group;

    public function __construct(Group $group)
    {
        parent::__construct(array(
            'route' => 'naturapass_group_show',
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
            'element' => 'group',
            'type' => 'group.join.asked'
        ));
    }


}