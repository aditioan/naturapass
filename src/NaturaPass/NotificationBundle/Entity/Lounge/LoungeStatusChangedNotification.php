<?php
/**
 * Created by PhpStorm.
 * User: vincentvalot
 * Date: 09/07/15
 * Time: 15:02
 */

namespace NaturaPass\NotificationBundle\Entity\Lounge;

use NaturaPass\LoungeBundle\Entity\Lounge;
use NaturaPass\LoungeBundle\Entity\LoungeUser;
use NaturaPass\NotificationBundle\Entity\AbstractNotification;
use Doctrine\ORM\Mapping as ORM;

/**
 * Class LoungeStatusChangedNotification
 * @package NaturaPass\NotificationBundle\Entity\Lounge
 *
 * @ORM\Entity
 */
class LoungeStatusChangedNotification extends AbstractNotification
{

    const TYPE = 'lounge.status.changed';

    private $lounge;
    private $loungeuser;
    private $status;

    public function __construct(LoungeUser $loungeuser, $status)
    {
        parent::__construct(array(
            'route' => 'naturapass_lounge_show',
        ));

        $this->lounge = $loungeuser->getLounge();
        $this->loungeuser = $loungeuser;
        $this->status = $status;

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
            '%sender%' => $this->loungeuser->getUser()->getFullName(),
            '%lounge%' => $this->lounge->getName(),
            '%status%' => strtoupper($this->status)
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