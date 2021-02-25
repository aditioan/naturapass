<?php
/**
 * User: vietlh
 */

namespace NaturaPass\NotificationBundle\Entity\Publication;

use NaturaPass\NotificationBundle\Entity\AbstractNotification;
use NaturaPass\PublicationBundle\Entity\Publication;
use Doctrine\ORM\Mapping as ORM;

/**
 * Class PublicationCommentLikedNotification
 * @package NaturaPass\NotificationBundle\Entity\Publication
 *
 * @ORM\Entity
 */
class PublicationCommentLikedNotification extends AbstractNotification {

    const TYPE = 'publication.comment.liked';

    private $publication;

    public function __construct(Publication $publication) {
        parent::__construct(array(
            'route' => 'naturapass_publication_show',
            'multiple' => true
        ));

        $this->publication = $publication;
        $this->objectID = $this->publication->getId();
    }

    /**
     * Returns the data for the link to be created
     *
     * @return array
     */
    public function getLinkData() {
        return array(
            'publication' => $this->publication->getId()
        );
    }

    /**
     * Returns the data for the content to be created
     *
     * @return array
     */
    public function getContentData() {
        return array(
            '%sender%' => $this->sender->getFullName()
        );
    }

    /**
     * Returns the data for the push data to be created
     *
     * @return array
     */
    public function getPushData() {
        return array_merge(parent::getPushData(), array(
            'element' => 'publication'
        ));
    }


}