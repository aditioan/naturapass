<?php

namespace NaturaPass\PublicationBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as ORMExtension;
use JMS\Serializer\Annotation as JMS;

/**
 * PublicationAction
 *
 * @ORM\Table(name="publication_has_action")
 * @ORM\Entity()
 */
class PublicationAction {

    const STATE_LIKE = 100;
    const STATE_UNLIKE = 101;

    /**
     * @ORM\Id
     * @ORM\ManyToOne(targetEntity="NaturaPass\PublicationBundle\Entity\Publication", inversedBy="actions")
     * @ORM\JoinColumn(nullable=false)
     */
    protected $publication;

    /**
     * @ORM\Id
     * @ORM\ManyToOne(targetEntity="NaturaPass\UserBundle\Entity\User")
     * @ORM\JoinColumn(nullable=false)
     * 
     * @JMS\Expose
     * @JMS\Groups({"PublicationUserAction"})
     */
    protected $user;

    /**
     * @var integer
     *
     * @ORM\Column(name="state", type="integer")
     */
    protected $state;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="updated", type="datetime")
     * @ORMExtension\Timestampable(on="update")
     */
    protected $updated;

    /**
     * Set publication
     *
     * @param \NaturaPass\PublicationBundle\Entity\Publication $publication
     * @return PublicationAction
     */
    public function setPublication(\NaturaPass\PublicationBundle\Entity\Publication $publication) {
        $this->publication = $publication;

        return $this;
    }

    /**
     * Get publication
     *
     * @return \NaturaPass\PublicationBundle\Entity\Publication
     */
    public function getPublication() {
        return $this->publication;
    }

    /**
     * Set user
     *
     * @param \NaturaPass\UserBundle\Entity\User $user
     * @return PublicationAction
     */
    public function setUser(\NaturaPass\UserBundle\Entity\User $user) {
        $this->user = $user;

        return $this;
    }

    /**
     * Get user
     *
     * @return \NaturaPass\UserBundle\Entity\User
     */
    public function getUser() {
        return $this->user;
    }

    /**
     * Set state
     *
     * @param integer $state
     * @return PublicationAction
     */
    public function setState($state) {
        $this->state = $state;

        return $this;
    }

    /**
     * Get state
     *
     * @return integer
     */
    public function getState() {
        return $this->state;
    }

    /**
     * Set updated
     *
     * @param \DateTime $updated
     * @return PublicationAction
     */
    public function setUpdated($updated) {
        $this->updated = $updated;

        return $this;
    }

    /**
     * Get updated
     *
     * @return \DateTime
     */
    public function getUpdated() {
        return $this->updated;
    }


}
