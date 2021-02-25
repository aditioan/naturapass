<?php
/**
 * Created by PhpStorm.
 * User: vincentvalot
 * Date: 09/07/15
 * Time: 15:02
 */

namespace NaturaPass\NotificationBundle\Entity\Lounge;

use NaturaPass\LoungeBundle\Entity\Lounge;
use NaturaPass\NotificationBundle\Entity\AbstractNotification;
use Doctrine\ORM\Mapping as ORM;

/**
 * Class LoungeJoinInvitedNotification
 * @package NaturaPass\NotificationBundle\Entity\Lounge
 *
 * @ORM\Entity
 */
class LoungeJoinInvitedNotification extends AbstractNotification
{

    const TYPE = 'lounge.join.invited';

    private $lounge;

    public function __construct(Lounge $lounge)
    {
        parent::__construct(array(
            'route' => 'naturapass_lounge_invited',
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
     * Returns the data for the link to be created
     *
     * @return array
     */
    public function getLinkData()
    {
        return array(
            'loungetag' => $this->lounge->getLoungetag()
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
            '%lounge%' => $this->lounge->getName()
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
            'element' => 'lounge'
        ));
    }


}