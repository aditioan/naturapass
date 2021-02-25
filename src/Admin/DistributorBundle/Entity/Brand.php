<?php

namespace Admin\DistributorBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation as JMS;
use Gedmo\Mapping\Annotation as ORMExtension;

/**
 * News
 *
 * @ORM\Table(name="brand")
 * @ORM\Entity
 */
class Brand {

    const PARTNER_ON = 1;
    const PARTNER_OFF = 0;

    /**
     * @var integer
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     *
     * @JMS\Expose
     * @JMS\Groups({"BrandDetail", "BrandLess"})
     */
    private $id;

    /**
     * @var string
     *
     * @ORM\Column(name="name", type="string", length=255)
     *
     * @JMS\Expose
     * @JMS\Groups({"BrandDetail", "BrandLess"})
     */
    protected $name;

    /**
     * @var integer
     *
     * @ORM\Column(name="partner", type="integer")
     *
     * @JMS\Expose
     * @JMS\Groups({"BrandDetail", "BrandLess"})
     */
    protected $partner = self::PARTNER_ON;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="created", type="datetime")
     * @ORMExtension\Timestampable(on="create")
     *
     * @JMS\Expose
     * @JMS\Groups({"BrandDetail", "BrandLess"})
     */
    protected $created;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="updated", type="datetime")
     * @ORMExtension\Timestampable(on="update")
     *
     * @JMS\Expose
     * @JMS\Groups({"BrandDetail", "BrandLess"})
     */
    protected $updated;

    /**
     * @var BrandMedia
     *
     * @ORM\OneToOne(targetEntity="Admin\DistributorBundle\Entity\BrandMedia", cascade={"persist", "remove"}, orphanRemoval=true)
     * @ORM\JoinColumn(nullable=true)
     *
     * @JMS\Expose
     * @JMS\Groups({"BrandPhoto"})
     */
    protected $logo;

    /**
     * @var \Doctrine\Common\Collections\ArrayCollection|\Admin\DistributorBundle\Entity\Distributor[]
     *
     * @ORM\ManyToMany(targetEntity="Admin\DistributorBundle\Entity\Distributor", mappedBy="brands")
     *
     * @JMS\Expose
     * @JMS\Groups({"GroupSubscribers"})
     */
    protected $distributors;

    /**
     * Constructor
     */
    public function __construct() {
        $this->distributors = new ArrayCollection();
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
     * Set updated
     *
     * @param \DateTime $updated
     * @return News
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
     * Set created
     *
     * @param \DateTime $created
     * @return News
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
     * Get Name
     *
     * @return string
     */
    function getName() {
        return $this->name;
    }

    /**
     * Set Name
     *
     * @param string $name
     * @return Brand
     */
    function setName($name) {
        $this->name = $name;
    }

    /**
     * Add Distributor
     *
     * @param \Admin\DistributorBundle\Entity\Distributor $distributor
     * @return Brand
     */
    public function addDistributor(Distributor $distributor) {
        $this->distributors[] = $distributor;

        return $this;
    }

    /**
     * Remove Distributor
     *
     * @param \Admin\DistributorBundle\Entity\Distributor $distributor
     */
    public function removeDistributor(Distributor $distributor) {
        $this->distributors->removeElement($distributor);
    }

    /**
     * Get Distributors
     *
     * @return \Doctrine\Common\Collections\ArrayCollection
     */
    public function getDistributors() {

        return $this->distributors;
    }

    public function __toString() {
        return $this->name;
    }

    /**
     * Set logo
     *
     * @param \Admin\DistributorBundle\Entity\BrandMedia $logo
     * @return Brand
     */
    public function setLogo(BrandMedia $logo = null) {
        $this->logo = $logo;

        return $this;
    }

    /**
     * Get logo
     *
     * @return \Admin\DistributorBundle\Entity\BrandMedia
     */
    public function getLogo() {
        return $this->logo;
    }

    /**
     * Get partner
     *
     * @return integer
     */
    function getPartner() {
        return $this->partner;
    }

    /**
     * Set partner
     *
     * @param integer $partner
     * @return Brand
     */
    function setPartner($partner) {
        $this->partner = $partner;

        return $this;
    }

}
