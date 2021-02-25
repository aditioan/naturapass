<?php
/**
 * Created by PhpStorm.
 * User: vincentvalot
 * Date: 09/07/15
 * Time: 15:02
 */

namespace NaturaPass\NotificationBundle\Entity\Lounge\SocketOnly;

use Api\ApiBundle\Controller\v1\ApiRestController;
use NaturaPass\LoungeBundle\Entity\Lounge;
use NaturaPass\LoungeBundle\Entity\LoungeNotMember;
use NaturaPass\NotificationBundle\Entity\AbstractNotification;
use Doctrine\ORM\Mapping as ORM;
use NaturaPass\NotificationBundle\Entity\SocketPoolNotification;

/**
 * Class LoungeNotUserParticipationNotification
 * @package NaturaPass\NotificationBundle\Entity\Lounge\SocketOnly
 */
class LoungeNotUserParticipationNotification extends AbstractNotification implements SocketPoolNotification
{

    const TYPE = 'lounge.user_participation';

    private $lounge;
    private $subscriber;

    public function __construct(Lounge $lounge, LoungeNotMember $subscriber)
    {
        parent::__construct(array(
            'route' => 'naturapass_lounge_show',
            'persistable' => false,
            'socket' => array(
                'enabled' => true,
                'event_name' => 'api-lounge:participationnotmember'
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
        return ApiRestController::getFormatLoungeSubscriberNotMember($this->subscriber);
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
