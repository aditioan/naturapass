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
use NaturaPass\PublicationBundle\Entity\Publication;

/**
 * Class LoungePublicationNotification
 * @package NaturaPass\NotificationBundle\Entity\Lounge
 *
 * @ORM\Entity
 */
class LoungeNewPublicationNotification extends AbstractNotification
{
    const TYPE = 'lounge.publication.new';

    private $lounge;
    private $publication;

    public function __construct(Lounge $lounge, Publication $publication)
    {
        parent::__construct(array(
            'route' => 'naturapass_lounge_show',
        ));

        $this->lounge = $lounge;
        $this->publication = $publication;

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
            '%lounge%' => $this->lounge->getName(),
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
            'element' => 'lounge',
            'user' => $this->sender->getId(),
            'publication_id' => $this->publication->getId()
        ));
    }

}
