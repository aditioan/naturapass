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
 * Class Device
 *
 * @ORM\Table(name="Device")
 * @ORM\Entity()
 * @ORM\HasLifecycleCallbacks
 * @JMS\ExclusionPolicy("all")
 */
class Device {

    const IOS = 1;
    const ANDROID = 2;

    /**
     * @var integer
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     *
     * @JMS\Expose
     * @JMS\Groups({"DeviceDetail"})
     */
    protected $id;

    /**
     * @var \NaturaPass\UserBundle\Entity\UserDevice[]|ArrayCollection
     *
     * @ORM\OneToMany(targetEntity="NaturaPass\UserBundle\Entity\UserDevice", mappedBy="device", fetch="EXTRA_LAZY", orphanRemoval=true)
     *
     * @JMS\Expose
     * @JMS\Groups({"DeviceOwner"})
     */
    protected $owners;

    /**
     * @var \NaturaPass\UserBundle\Entity\UserMap[]|ArrayCollection
     *
     * @ORM\OneToMany(targetEntity="NaturaPass\UserBundle\Entity\UserMap", mappedBy="device", fetch="EXTRA_LAZY", orphanRemoval=true)
     *
     * @JMS\Expose
     * @JMS\Groups({"DeviceMap"})
     */
    protected $maps;

    /**
     * @var string
     *
     * @ORM\Column(name="name", type="string", length=255, nullable=true)
     *
     * @JMS\Expose
     * @JMS\Groups({"DeviceLess"})
     */
    protected $name;

    /**
     * @var string
     *
     * @ORM\Column(name="identifier", type="text")
     *
     * @JMS\Expose
     * @JMS\Groups({"DeviceLess"})
     */
    protected $identifier;

    /**
     * @var integer
     *
     * @ORM\Column(name="type", type="integer")
     *
     * @JMS\Expose
     * @JMS\Groups({"DeviceLess"})
     */
    protected $type;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="created", type="datetime")
     * @ORMExtension\Timestampable(on="create")
     *
     * @JMS\Expose
     * @JMS\Groups({"DeviceDetail"})
     */
    protected $created;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="updated", type="datetime")
     * @ORMExtension\Timestampable(on="update")
     *
     * @JMS\Expose
     * @JMS\Groups({"DeviceDetail"})
     */
    protected $updated;

    public function __construct() {
        $this->owners = new ArrayCollection();
        $this->maps = new ArrayCollection();
    }

    /**
     * @param mixed $identifier
     *
     * @return Device
     */
    public function setIdentifier($identifier)
    {
        $this->identifier = $identifier;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getIdentifier()
    {
        return $this->identifier;
    }

    /**
     * @param int $type
     *
     * @return Device
     */
    public function setType($type)
    {
        $this->type = $type;

        return $this;
    }

    /**
     * @return int
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * @param \NaturaPass\UserBundle\Entity\User $owner
     *
     * @return UserDevice|null
     */
    public function hasOwner(User $owner)
    {
        return $this->owners->filter(function(UserDevice $element) use ($owner) {
            return $element->getOwner()->getId() === $owner->getId();
        })->first();
    }

    /**
     * @return \NaturaPass\UserBundle\Entity\UserDevice[]|ArrayCollection
     */
    public function getOwners()
    {
        return $this->owners;
    }

    /**
     * @param $owner \NaturaPass\UserBundle\Entity\UserDevice
     *
     * @return Device
     */
    public function addOwner(UserDevice $owner)
    {
        $this->owners->add($owner);

        return $this;
    }

    /**
     * @param $owner \NaturaPass\UserBundle\Entity\UserDevice
     *
     * @return Device
     */
    public function removeOwner(UserDevice $owner)
    {
        $this->owners->remove($owner);

        return $this;
    }
    
    /**
     * @return \NaturaPass\UserBundle\Entity\UserMap[]|ArrayCollection
     */
    public function getMaps()
    {
        return $this->maps;
    }

    /**
     * @param $maps \NaturaPass\UserBundle\Entity\UserMap
     *
     * @return Device
     */
    public function addMap(UserMap $maps)
    {
        $this->maps->add($maps);

        return $this;
    }

    /**
     * @param $maps \NaturaPass\UserBundle\Entity\UserMap
     *
     * @return Device
     */
    public function removeMap(UserMap $maps)
    {
        $this->maps->remove($owner);

        return $this;
    }

    /**
     * @param \DateTime $created
     *
     * @return Device
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
     * @return Device
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
     * @return Device
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


} 