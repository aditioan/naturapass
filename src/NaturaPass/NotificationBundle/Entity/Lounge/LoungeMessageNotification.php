<?php
/**
 * Created by PhpStorm.
 * User: vincentvalot
 * Date: 09/07/15
 * Time: 15:02
 */

namespace NaturaPass\NotificationBundle\Entity\Lounge;

use Api\ApiBundle\Controller\v1\ApiRestController;
use NaturaPass\LoungeBundle\Entity\Lounge;
use NaturaPass\LoungeBundle\Entity\LoungeMessage;
use NaturaPass\LoungeBundle\Entity\LoungeUser;
use NaturaPass\NotificationBundle\Entity\AbstractNotification;
use Doctrine\ORM\Mapping as ORM;
use NaturaPass\NotificationBundle\Entity\SocketPoolNotification;

/**
 * Class LoungeMessageNotification
 * @package NaturaPass\NotificationBundle\Entity\Lounge
 *
 * @ORM\Entity
 */
class LoungeMessageNotification extends AbstractNotification implements SocketPoolNotification
{

    const TYPE = 'lounge.chat.new_message';

    private $lounge;

    private $message;

    public function __construct(Lounge $lounge, LoungeMessage $message)
    {
        parent::__construct(array(
            'route' => 'naturapass_lounge_show',
            'socket' => array(
                'enabled' => true,
                'event_name' => 'api-lounge:message'
            )
        ));

        $this->lounge = $lounge;
        $this->message = $message;

        $this->objectID = $this->lounge->getId();
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
        return $this->lounge->getLoungetag();
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
            '%lounge%' => $this->lounge->getName(),
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
        $toSend = array_merge(parent::getPushData(), array(
            'element' => 'lounge',
            'content' => $this->message->getContent(),
            'type' => 'chat',
            'user' => $this->sender->getId()
        ));
        if ($this->lounge->isLiveActive($this->sender)) {
            $toSend = array_merge($toSend, array("live" => $this->lounge->getId()));
        }
        return $toSend;
    }

    /**
     * Returns the data for the socket data to be created
     *
     * @return array
     */
    public function getSocketData()
    {
        $toSend = ApiRestController::getFormatLoungeMessage($this->message);
        if ($this->lounge->isLiveActive($this->sender)) {
            $toSend = array_merge($toSend, array("live" => $this->lounge->getId()));
        }
        return $toSend;
    }


}