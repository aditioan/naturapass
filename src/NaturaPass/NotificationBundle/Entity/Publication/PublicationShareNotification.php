<?php
/**
 * Created by PhpStorm.
 * User: vietlh
 * Date: 11/23/16
 * Time: 17:02
 */

namespace NaturaPass\NotificationBundle\Entity\Publication;

use NaturaPass\NotificationBundle\Entity\AbstractNotification;
use NaturaPass\PublicationBundle\Entity\Publication;
use Doctrine\ORM\Mapping as ORM;

/**
 * Class PublicationShareNotification
 * @package NaturaPass\NotificationBundle\Entity\Publication
 *
 * @ORM\Entity
 */
class PublicationShareNotification extends AbstractNotification {

    const TYPE = 'publication.shared';

    private $publication;

    public function __construct(Publication $publication) {
        parent::__construct(array(
            'route' => 'naturapass_publication_show',
            'multiple' => true
        ));
	$this->content = 'a partagé une publication avec vous';
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
            '%sender%' => $this->sender->getFullName(),
            '%content%' => $this->sender->getFullName().' a partagé une publication avec vous',
	    '%text%' => ' a partagé une publication avec vous',
        );
    }

    /**
     * Returns the data for the push data to be created
     *
     * @return array
     */
    public function getPushData() {
        return array_merge(parent::getPushData(), array(
            'element' => 'publication',
            'content' => $this->sender->getFullName().' a partagé une publication avec vous',
        ));
    }


}
