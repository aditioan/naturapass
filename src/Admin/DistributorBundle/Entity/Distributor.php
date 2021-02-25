<?php

namespace Admin\DistributorBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use JMS\Serializer\Annotation as JMS;
use Gedmo\Mapping\Annotation as ORMExtension;
use NaturaPass\MainBundle\Entity\Geolocation;

/**
 * News
 *
 * @ORM\Table(name="distributor")
 * @ORM\Entity
 */
class Distributor {

    /**
     * @var integer
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     *
     * @JMS\Expose
     * @JMS\Groups({"DistributorDetail", "DistributorLess"})
     */
    private $id;

    /**
     * @var string
     *
     * @ORM\Column(name="name", type="string", length=255)
     *
     * @JMS\Expose
     * @JMS\Groups({"DistributorDetail", "DistributorLess"})
     */
    protected $name;

    /**
     * @var string
     *
     * @ORM\Column(name="address", type="string", length=1000)
     *
     * @JMS\Expose
     * @JMS\Groups({"DistributorDetail", "DistributorLess"})
     */
    protected $address;

    /**
     * @var string
     *
     * @ORM\Column(name="cp", type="string", length=50)
     *
     * @JMS\Expose
     * @JMS\Groups({"DistributorDetail", "DistributorLess"})
     */
    protected $cp;

    /**
     * @var string
     *
     * @ORM\Column(name="city", type="string", length=500)
     *
     * @JMS\Expose
     * @JMS\Groups({"DistributorDetail", "DistributorLess"})
     */
    protected $city;

    /**
     * @var string
     *
     * @ORM\Column(name="telephone", type="text", nullable=true)
     *
     * @JMS\Expose
     * @JMS\Groups({"DistributorDetail", "DistributorLess"})
     */
    protected $telephone;

    

    /**
     * @var string
     *
     * @ORM\Column(name="email", type="string", length=550, nullable=false)
     *
     * @Assert\Email(
     *     message = "The email '{{ value }}' is not a valid email.",
     *     checkMX = true
     * )
     * @JMS\Expose
     * @JMS\Groups({"DistributorDetail", "DistributorLess"})
     */
    protected $email;

    /**
     * @var Geolocation
     *
     * @ORM\OneToOne(targetEntity="NaturaPass\MainBundle\Entity\Geolocation", cascade={"persist", "remove"}, orphanRemoval=true)
     * @ORM\JoinColumn(nullable=true)
     *
     * @JMS\Expose
     * @JMS\Groups({"DistributorDetail"})
     */
    protected $geolocation;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="created", type="datetime")
     * @ORMExtension\Timestampable(on="create")
     *
     * @JMS\Expose
     * @JMS\Groups({"DistributorDetail", "DistributorLess"})
     */
    protected $created;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="updated", type="datetime")
     * @ORMExtension\Timestampable(on="update")
     *
     * @JMS\Expose
     * @JMS\Groups({"DistributorDetail", "DistributorLess"})
     */
    protected $updated;

    /**
     * @var DistributorMedia
     *
     * @ORM\OneToOne(targetEntity="Admin\DistributorBundle\Entity\DistributorMedia", cascade={"persist", "remove"}, orphanRemoval=true)
     * @ORM\JoinColumn(nullable=true)
     *
     * @JMS\Expose
     * @JMS\Groups({"DistributorPhoto"})
     */
    protected $logo;

    /**
     * @var Brand
     *
     * @ORM\ManyToMany(targetEntity="Admin\DistributorBundle\Entity\Brand", inversedBy="distributors")
     * @ORM\JoinColumn(nullable=false)
     * @ORM\JoinTable(name="distributor_has_brand")
     *
     * @JMS\Expose
     * @JMS\Groups({"DistributorDetail"})
     */
    protected $brands;

    /**
     * Constructor
     */
    public function __construct() {
        $this->brands = new ArrayCollection();
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
     * Get Email
     *
     * @return string
     */
    function getEmail() {
        return $this->email;
    }

    /**
     * Get Telephone
     *
     * @return string
     */
    function getTelephone() {
        return $this->telephone;
    }

    /**
     * Get Address
     *
     * @return string
     */
    function getAddress() {
        return $this->address;
    }

    /**
     * Get Cp
     *
     * @return string
     */
    function getCp() {
        return $this->cp;
    }

    /**
     * Get City
     *
     * @return string
     */
    function getCity() {
        return $this->city;
    }

    /**
     * Set Name
     *
     * @param string $name
     * @return Distributor
     */
    function setName($name) {
        $this->name = $name;
    }

    /**
     * Set Email
     *
     * @param string $email
     * @return Distributor
     */
    function setEmail($email) {
        $this->email = $email;
    }

    /**
     * Set Telephone
     *
     * @param string $telephone
     * @return Distributor
     */
    function setTelephone($telephone) {
        $this->telephone = $telephone;
    }

    /**
     * Set Address
     *
     * @param string $address
     * @return Distributor
     */
    function setAddress($address) {
        $this->address = $address;
    }

    /**
     * Set Cp
     *
     * @param string $cp
     * @return Distributor
     */
    function setCp($cp) {
        $this->cp = $cp;
    }

    /**
     * Set City
     *
     * @param string $city
     * @return Distributor
     */
    function setCity($city) {
        $this->city = $city;
    }

    /**
     * Get geolocation
     *
     * @return \Geolocation
     */
    function getGeolocation() {
        return $this->geolocation;
    }

    /**
     * Set geolocation
     *
     * @param \Geolocation $geolocation
     * @return Distributor
     */
    function setGeolocation(Geolocation $geolocation) {
        $this->geolocation = $geolocation;
        return $this;
    }

    /**
     * Set logo
     *
     * @param \Admin\DistributorBundle\Entity\DistributorMedia $logo
     * @return Distributor
     */
    public function setLogo(DistributorMedia $logo = null) {
        $this->logo = $logo;

        return $this;
    }

    /**
     * Get logo
     *
     * @return \Admin\DistributorBundle\Entity\DistributorMedia
     */
    public function getLogo() {
        return $this->logo;
    }

    /**
     * Get Brand
     *
     * @param array
     * @param boolean
     *
     * @return ArrayCollection
     */
    public function getBrands() {
        return $this->brands;
    }

    /**
     * Add Brand
     *
     * @param \Admin\DistributorBundle\Entity\Brand $brand
     * @return Distributor
     */
    public function addBrand(Brand $brand) {
        $this->brands[] = $brand;

        return $this;
    }

    /**
     * Remove Brand
     *
     * @param \Admin\DistributorBundle\Entity\Brand $brand
     */
    public function removeBrand(Brand $brand) {
        $this->brands->removeElement($brand);
    }

}
