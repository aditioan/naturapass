<?php

namespace Admin\SentinelleBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation as JMS;
use Gedmo\Mapping\Annotation as ORMExtension;

/**
 * Zone
 *
 * @ORM\Table(name="`zone`")
 * @ORM\Entity()
 *
 * @JMS\ExclusionPolicy("all")
 */
class Zone {

    /**
     * @var integer
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     *
     * @JMS\Expose
     * @JMS\Groups({"ZoneDetail", "ZoneLess", "ZoneID"})
     */
    protected $id;

    /**
     * @var string
     *
     * @ORM\Column(name="name", type="string", length=255)
     *
     * @JMS\Expose
     * @JMS\Groups({"ZoneDetail", "ZoneLess"})
     */
    protected $name;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="created", type="datetime")
     * @ORMExtension\Timestampable(on="create")
     *
     * @JMS\Expose
     * @JMS\Groups({"ZoneDetail"})
     */
    protected $created;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="updated", type="datetime")
     * @ORMExtension\Timestampable(on="update")
     *
     * @JMS\Expose
     * @JMS\Groups({"ZoneDetail"})
     */
    protected $updated;

    /**
     * @ORM\OneToMany(targetEntity="Admin\SentinelleBundle\Entity\CardCategoryZone", mappedBy="zone", cascade={"persist", "remove"}, fetch="EXTRA_LAZY", orphanRemoval=true)
     * @ORM\JoinColumn(nullable=true)
     */
    protected $cards;

    /**
     * @ORM\OneToMany(targetEntity="Admin\SentinelleBundle\Entity\Locality", mappedBy="zone")
     * @ORM\JoinColumn(nullable=true, onDelete="SET NULL")
     *
     */
    protected $localities;

    /**
     * Get id
     *
     * @return integer
     */
    public function getId() {
        return $this->id;
    }

    /**
     * Set name
     *
     * @param string $name
     * @return Zone
     */
    public function setName($name) {
        $this->name = $name;

        return $this;
    }

    /**
     * Get name
     *
     * @return string
     */
    public function getName() {
        return $this->name;
    }

    /**
     * Constructor
     */
    public function __construct() {
        $this->cards = new ArrayCollection();
        $this->localities = new ArrayCollection();
    }

    /**
     * Set created
     *
     * @param \DateTime $created
     * @return Zone
     */
    public function setCreated($created) {
        $this->created = $created;

        return $this;
    }

    /**
     * Get created
     *
     * @return \DateTime
     */
    public function getCreated() {
        return $this->created;
    }

    /**
     * Set updated
     *
     * @param \DateTime $updated
     * @return Zone
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

    /**
     * Get CardCategoryZone
     *
     * @return \Admin\SentinelleBundle\Entity\CardCategoryZone[]
     */
    public function getCards() {
        return $this->cards;
    }

    /**
     * Add CardCategoryZone
     *
     * @param \Admin\SentinelleBundle\Entity\CardCategoryZone $card
     * @return Zone
     */
    public function addCard(CardCategoryZone $card) {
        $this->cards[] = $card;

        return $this;
    }

    /**
     * Remove subscribers
     *
     * @param \Admin\SentinelleBundle\Entity\CardCategoryZone $card
     */
    public function removeCard(CardCategoryZone $card) {
        $this->cards->removeElement($card);
    }

    /**
     * Get Locality
     *
     * @param array
     * @param boolean
     *
     * @return ArrayCollection
     */
    public function getLocalities() {
        return $this->localities;
    }

    /**
     * Add Locality
     *
     * @param \Admin\SentinelleBundle\Entity\Locality $locality
     * @return Zone
     */
    public function addLocality(Locality $locality) {
        $this->localities[] = $locality;

        return $this;
    }

    /**
     * Remove Locality
     *
     * @param \Admin\SentinelleBundle\Entity\Locality $locality
     */
    public function removeLocality(Locality $locality) {
        $this->localities->removeElement($locality);
    }

    public function __toString() {
        return $this->name;
    }

}
