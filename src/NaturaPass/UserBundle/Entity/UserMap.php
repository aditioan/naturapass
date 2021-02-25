<?php
/**
 * User: nicolas MENDEZ
 * Date: 02/10/14
 * Time: 9:52
 */

namespace NaturaPass\UserBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation as JMS;
use Gedmo\Mapping\Annotation as ORMExtension;
use Doctrine\Common\Collections\ArrayCollection;

/**
 * Class UserMap
 *
 * @ORM\Table(name="user_has_map")
 * @ORM\Entity()
 * @ORM\HasLifecycleCallbacks
 * @JMS\ExclusionPolicy("all")
 */
class UserMap {

    const LOUNGE = 1;
    const PUBLICATION = 2;

    /**
     * @ORM\Id
     * @ORM\ManyToOne(targetEntity="NaturaPass\UserBundle\Entity\Device", inversedBy="maps")
     * @ORM\JoinColumn(nullable=false, onDelete="CASCADE")
     *
     * @JMS\Expose
     * @JMS\Groups({"MapLess"})
     */
    protected $device;

    /**
     * @ORM\Id
     * @ORM\ManyToOne(targetEntity="NaturaPass\UserBundle\Entity\User", inversedBy="maps")
     * @ORM\JoinColumn(nullable=false, onDelete="CASCADE")
     *
     * @JMS\Expose
     * @JMS\Groups({"MapLess"})
     */
    protected $owner;

    /**
     * @var integer
     * 
     * @ORM\Id
     * @ORM\Column(name="type", type="integer")
     * @ORM\JoinColumn(nullable=false)
     *
     * @JMS\Expose
     * @JMS\Groups({"MapLess"})
     */
    protected $type = self::LOUNGE;

    /**
     * @var string
     *
     * @ORM\Column(name="object_id", type="text", nullable=true)
     *
     * @JMS\Expose
     * @JMS\Groups({"MapLess"})
     */
    protected $objectID;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="created", type="datetime")
     * @ORMExtension\Timestampable(on="create")
     *
     * @JMS\Expose
     * @JMS\Groups({"MapLess"})
     */
    protected $created;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="updated", type="datetime")
     * @ORMExtension\Timestampable(on="update")
     *
     * @JMS\Expose
     * @JMS\Groups({"MapLess"})
     */
    protected $updated;

    
    /**
     * @param \NaturaPass\UserBundle\Entity\Device $device
     *
     * @return UserMap
     */
    public function setDevice(Device $device)
    {
        $this->device = $device;

        return $this;
    }

    /**
     * @return \NaturaPass\UserBundle\Entity\Device
     */
    public function getDevice()
    {
        return $this->device;
    }

    /**
     * @param User $owner
     *
     * @return UserMap
     */
    public function setOwner(User $owner)
    {
        $this->owner = $owner;

        return $this;
    }

    /**
     * @return \NaturaPass\UserBundle\Entity\User
     */
    public function getOwner()
    {
        return $this->owner;
    }

    /**
     * @param integer $type
     *
     * @return UserMap
     */
    public function setType($type)
    {
        $this->type = $type;

        return $this;
    }

    /**
     * @return integer
     */
    public function getType()
    {
        return $this->type;
    }
    
    /**
     * @param string $objectID
     *
     * @return UserMap
     */
    public function setObjectID($objectID)
    {
        $this->objectID = $objectID;
        return $this;
    }

    /**
     * @return string
     */
    public function getObjectID()
    {
        return $this->objectID;
    }

    /**
     * @param \DateTime $created
     *
     * @return UserMap
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
     * @param \DateTime $updated
     *
     * @return UserMap
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
} 