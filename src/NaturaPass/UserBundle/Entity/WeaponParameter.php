<?php
/**
 * Created by PhpStorm.
 * User: vincentvalot
 * Date: 17/07/14
 * Time: 10:06
 */

namespace NaturaPass\UserBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation as JMS;
use Gedmo\Mapping\Annotation as ORMExtension;
use Doctrine\Common\Collections\ArrayCollection;

/**
 * Class WeaponParameter
 *
 * @ORM\Table(name="user_has_weapon")
 * @ORM\Entity()
 * @ORM\HasLifecycleCallbacks
 * @JMS\ExclusionPolicy("all")
 */
class WeaponParameter
{


    const TYPE_SHOTGUN = 0;
    const TYPE_CARABINE = 1;

    /**
     * @var integer
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     *
     * @JMS\Expose
     * @JMS\Groups({"WeaponParameterDetail"})
     */
    protected $id;

    /**
     * @var \NaturaPass\UserBundle\Entity\User
     *
     * @ORM\ManyToOne(targetEntity="NaturaPass\UserBundle\Entity\User", inversedBy="WeaponParameters")
     */
    protected $owner;

    /**
     * @var string
     *
     * @ORM\Column(name="name", type="string", length=255, nullable=true)
     *
     * @JMS\Expose
     * @JMS\Groups({"WeaponParameterLess"})
     */
    protected $name;

    /**
     * @var integer
     *
     * @ORM\Column(name="type", type="integer", nullable=true)
     *
     * @JMS\Expose
     * @JMS\Groups({"WeaponParameterLess"})
     */
    protected $type;

    /**
     * @ORM\ManyToOne(targetEntity="NaturaPass\UserBundle\Entity\WeaponCalibre")
     *
     * @JMS\Expose
     * @JMS\Groups({"WeaponParameterLess"})
     */
    protected $calibre;

    /**
     * @ORM\ManyToOne(targetEntity="NaturaPass\UserBundle\Entity\WeaponBrand")
     *
     * @JMS\Expose
     * @JMS\Groups({"WeaponParameterLess"})
     */
    protected $brand;

    /**
     * @var \NaturaPass\UserBundle\Entity\WeaponPhoto
     *
     * @ORM\OneToOne(targetEntity="NaturaPass\UserBundle\Entity\WeaponPhoto", cascade={"persist", "remove"}, orphanRemoval=true)
     *
     */
    protected $photo;


    /**
     * @var \Doctrine\Common\Collections\ArrayCollection|\NaturaPass\UserBundle\Entity\WeaponMedia[]
     *
     * @ORM\OneToMany(targetEntity="NaturaPass\UserBundle\Entity\WeaponMedia", mappedBy="weapon", cascade={"persist", "remove"}, orphanRemoval=true, fetch="EXTRA_LAZY")
     * @ORM\JoinColumn(nullable=true)
     *
     */
    protected $medias;


    /**
     * @var \DateTime
     *
     * @ORM\Column(name="created", type="datetime")
     * @ORMExtension\Timestampable(on="create")
     *
     * @JMS\Expose
     * @JMS\Groups({"WeaponParameterDetail"})
     */
    protected $created;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="updated", type="datetime")
     * @ORMExtension\Timestampable(on="update")
     *
     * @JMS\Expose
     * @JMS\Groups({"WeaponParameterDetail"})
     */
    protected $updated;

    public function __construct()
    {
        $this->medias = new ArrayCollection();
    }

    /**
     * @param \DateTime $created
     *
     * @return WeaponParameter
     */
    public function setCreated($created)
    {
        $this->created = $created;

        return $this;
    }

    /**
     * @return \DateTime
     */
    public function getCreated()
    {
        return $this->created;
    }

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param \DateTime $updated
     *
     * @return WeaponParameter
     */
    public function setUpdated($updated)
    {
        $this->updated = $updated;

        return $this;
    }

    /**
     * @return \DateTime
     */
    public function getUpdated()
    {
        return $this->updated;
    }

    /**
     * @param string $name
     *
     * @return WeaponParameter
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param $media \NaturaPass\UserBundle\Entity\WeaponPhoto
     *
     * @return DogParameter
     */
    public function setPhoto(WeaponPhoto $media)
    {
        $this->photo = $media;

        return $this;
    }

    /**
     * @return \NaturaPass\UserBundle\Entity\WeaponPhoto
     */
    public function getPhoto()
    {
        return $this->photo;
    }

    /**
     * @return \NaturaPass\UserBundle\Entity\WeaponMedia[]|ArrayCollection
     */
    public function getMedias()
    {
        return $this->medias;
    }

    /**
     * @param $media \NaturaPass\MediaBundle\Entity\WeaponMedia
     *
     * @return WeaponParameter
     */
    public function addMedia(WeaponMedia $media)
    {
        $this->medias->add($media);
        $media->setWeapon($this);

        return $this;
    }

    /**
     * @param $media \NaturaPass\MediaBundle\Entity\WeaponMedia
     *
     * @return WeaponParameter
     */
    public function removeMedia(WeaponMedia $media)
    {
        $this->medias->removeElement($media);

        return $this;
    }

    /**
     * Get brand
     *
     * @return \NaturaPass\UserBundle\Entity\WeaponBrand
     */
    public function getBrand()
    {
        return $this->brand;
    }

    /**
     * Set brand
     *
     * @param \NaturaPass\UserBundle\Entity\WeaponBrand $brand
     * @return WeaponParameter
     */
    public function setBrand($brand)
    {
        $this->brand = $brand;
        return $this;
    }

    /**
     * Get calibre
     *
     * @return \NaturaPass\UserBundle\Entity\WeaponCalibre
     */
    public function getCalibre()
    {
        return $this->calibre;
    }

    /**
     * Set calibre
     *
     * @param \NaturaPass\UserBundle\Entity\WeaponCalibre $calibre
     * @return WeaponParameter
     */
    public function setCalibre($calibre)
    {
        $this->calibre = $calibre;
        return $this;
    }

    /**
     * @param string $type
     *
     * @return WeaponParameter
     */
    public function setType($type)
    {
        $this->type = $type;

        return $this;
    }

    /**
     * @return string
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * Set user
     *
     * @param \NaturaPass\UserBundle\Entity\User $user
     * @return WeaponParameter
     */
    public function setOwner(User $user)
    {
        $this->owner = $user;

        return $this;
    }

    /**
     * Get user
     *
     * @return \NaturaPass\UserBundle\Entity\User
     */
    public function getOwner()
    {
        return $this->owner;
    }
}