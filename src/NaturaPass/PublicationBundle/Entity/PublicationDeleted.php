<?php

namespace NaturaPass\PublicationBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use JMS\Serializer\Annotation as JMS;
use Gedmo\Mapping\Annotation as ORMExtension;
use Doctrine\Common\Collections\Criteria;
use NaturaPass\GroupBundle\Entity\Group;
use NaturaPass\MainBundle\Entity\Geolocation;
use NaturaPass\MainBundle\Entity\Sharing;
use NaturaPass\UserBundle\Entity\User;

/**
 * Publication
 *
 * @ORM\Table(name="publication_deleted")
 * @ORM\Entity()
 * @ORM\HasLifecycleCallbacks
 * @JMS\ExclusionPolicy("all")
 */
class PublicationDeleted {

    /**
     * @var integer
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     *
     * @JMS\Expose
     * @JMS\Groups({"PublicationLess"})
     */
    protected $id;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="deleted", type="datetime")
     * @ORMExtension\Timestampable(on="create")
     *
     * @JMS\Expose
     * @JMS\Groups({"PublicationLess"})
     */
    protected $deleted;

    /**
     * @var Geolocation
     *
     * @ORM\OneToOne(targetEntity="NaturaPass\MainBundle\Entity\Geolocation", cascade={"persist", "remove"}, orphanRemoval=true)
     * @ORM\JoinColumn(nullable=true)
     *
     * @JMS\Expose
     * @JMS\Groups({"PublicationDetail"})
     */
    protected $geolocation;

    /**
     * Constructor
     */
    public function __construct() {

    }

    /**
     * Get id
     *
     * @return integer
     */
    public function getId() {
        return $this->id;
    }

    /**
     * Set id
     *
     * @param integer $id
     * @return  PublicationDeleted
     */
    public function setId($id) {
        $this->id = $id;

        return $this;
    }

    /**
     * Set deleted
     *
     * @param \DateTime $deleted
     * @return PublicationDeleted
     */
    public function setDeleted($deleted) {
        $this->deleted = $deleted;

        return $this;
    }

    /**
     * Get deleted
     *
     * @return \DateTime
     */
    public function getDeleted() {
        return $this->deleted;
    }

    /**
     * Set geolocation
     *
     * @param null|Geolocation $geolocation
     * @return PublicationDeleted
     */
    public function setGeolocation($geolocation) {
        $this->geolocation = $geolocation;

        return $this;
    }

    /**
     * Get geolocation
     *
     * @return null|Geolocation
     */
    public function getGeolocation() {
        return $this->geolocation;
    }

}
