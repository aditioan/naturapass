<?php
/**
 * Created by PhpStorm.
 * User: vincentvalot
 * Date: 09/07/15
 * Time: 15:02
 */

namespace NaturaPass\NotificationBundle\Entity\Publication;

use NaturaPass\NotificationBundle\Entity\AbstractNotification;
use NaturaPass\PublicationBundle\Entity\Publication;
use NaturaPass\PublicationBundle\Entity\PublicationComment;
use Doctrine\ORM\Mapping as ORM;

/**
 * Class PublicationCommentedNotification
 * @package NaturaPass\NotificationBundle\Entity\Publication
 *
 * @ORM\Entity
 */
class PublicationCommentedNotification extends AbstractNotification
{

    const TYPE = 'publication.commented';

    private $publication;

    private $comment;

    public function __construct(Publication $publication, PublicationComment $comment)
    {
        parent::__construct(array(
            'route' => 'naturapass_publication_show',
            'multiple' => true,
            'socket' => array(
                'enabled' => true,
                'event_name' => 'api-notification:incoming'
            ),
        ));

        $this->publication = $publication;
        $this->comment = $comment;

        $this->objectID = $this->publication->getId();
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
            '%sender%' => $this->sender->getFullName()
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