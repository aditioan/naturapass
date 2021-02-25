<?php

namespace NaturaPass\MainBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation as JMS;

/**
 * Geolocation
 *
 * @ORM\Table()
 * @ORM\Entity
 * @JMS\ExclusionPolicy("all")
 */
class Geolocation extends AbstractGeolocation {

    /**
     * @var integer
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     *
     * @JMS\Expose
     * @JMS\Groups({"GeolocationDetail"})
     */
    protected $id;

    /**
     * @var boolean
     *
     * @ORM\Column(name="address_updated", type="boolean", nullable=true)
     *
     * @JMS\Expose
     * @JMS\Groups({"DistributorDetail", "DistributorLess"})
     */
    protected $addressUpdated;

    /**
     * Get id
     *
     * @return integer
     */
    public function getId() {
        return $this->id;
    }

    /**
     * Set address updated
     *
     * @param \Boolean $addressUpdated
     * @return \Boolean
     */
    public function setAddressUpdated($addressUpdated) {
        $this->addressUpdated = $addressUpdated;

        return $this;
    }

    /**
     * Get address updated
     *
     * @return \Boolean
     */
    public function getAddressUpdated() {
        return $this->addressUpdated;
    }
}