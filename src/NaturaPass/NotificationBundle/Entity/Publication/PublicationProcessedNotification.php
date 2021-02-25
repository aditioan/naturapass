<?php
/**
 * Created by PhpStorm.
 * User: vincentvalot
 * Date: 09/07/15
 * Time: 15:02
 */

namespace NaturaPass\NotificationBundle\Entity\Publication;

use Api\ApiBundle\Controller\v2\Serialization\PublicationSerialization;
use NaturaPass\NotificationBundle\Entity\AbstractNotification;
use NaturaPass\NotificationBundle\Entity\SocketSubEvents;
use NaturaPass\PublicationBundle\Entity\Publication;
use Doctrine\ORM\Mapping as ORM;

/**
 * Class PublicationProcessedNotification
 * @package NaturaPass\NotificationBundle\Entity\Publication
 *
 * @ORM\Entity
 */
class PublicationProcessedNotification extends AbstractNotification
{

    const TYPE = 'publication.processed.success';

    private $publication;

    public function __construct(Publication $publication)
    {
        parent::__construct(array(
            'route' => 'naturapass_publication_show',
            'sender' => 'server',
            'socket' => array(
                'enabled' => true,
                'event_name' => 'api-publication:processed'
            )
        ));

        $this->publication = $publication;

        $this->objectID = $this->publication->getId();
    }

    public function getSocketData()
    {
        return array_merge(parent::getSocketData(), array(
            'extra_data' => array(
                'publication' => PublicationSerialization::serializePublication($this->publication, $this->sender)
            )
        ));
    }

    /**
     * Returns the data for the link to be created
     *
     * @return array
     */
    public function getLinkData()
    {
        return array(
            'publication' => $this->publication->getId()
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
            '%content%' => (strlen($this->publication->getContent()) > 20) ? (substr($this->publication->getContent(), 0, 20) . "...") : $this->publication->getContent(),
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
            'element' => 'publication'
        ));
    }


}