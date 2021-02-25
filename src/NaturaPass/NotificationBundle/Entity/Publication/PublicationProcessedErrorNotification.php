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
use Doctrine\ORM\Mapping as ORM;

/**
 * Class PublicationProcessedErrorNotification
 * @package NaturaPass\NotificationBundle\Entity\Publication
 *
 * @ORM\Entity
 */
class PublicationProcessedErrorNotification extends AbstractNotification {

    const TYPE = 'publication.processed.error';

    public function __construct() {
        parent::__construct(array(
            'route' => 'naturapass_main_homepage',
            'sender' => 'server',
        ));
    }

    /**
     * Returns the data for the link to be created
     *
     * @return array
     */
    public function getLinkData() {
        return array(
        );
    }

    /**
     * Returns the data for the content to be created
     *
     * @return array
     */
    public function getContentData() {
//        return array(
//            '%content%' => (strlen($this->publication->getContent()) > 20) ? (substr($this->publication->getContent(), 0, 20) . "...") : $this->publication->getContent()
//        );
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