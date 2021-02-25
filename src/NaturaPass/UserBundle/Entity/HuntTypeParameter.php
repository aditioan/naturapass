<?php
/**
 * Created by PhpStorm.
 * User: vincentvalot
 * Date: 17/07/14
 * Time: 10:06
 */

namespace NaturaPass\UserBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as ORMExtension;
use Doctrine\Common\Collections\ArrayCollection;
use NaturaPass\MediaBundle\Entity\Media;

/**
 * Class HuntTypeParameter
 *
 * @ORM\Table(name="user_has_hunt")
 * @ORM\Entity()
 * @ORM\HasLifecycleCallbacks
 */
class HuntTypeParameter
{

    const TYPE_PRACTICED = 0;
    const TYPE_LIKED = 1;

    /**
     * @var integer
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     *
     */
    protected $id;

    /**
     * @var \NaturaPass\UserBundle\Entity\User
     *
     * @ORM\ManyToOne(targetEntity="NaturaPass\UserBundle\Entity\User", inversedBy="hunttypes")
     */
    protected $owner;

    /**
     * @var integer
     *
     * @ORM\Column(name="type", type="integer")
     *
     */
    protected $type = self::TYPE_PRACTICED;


    /**
     * @var \Doctrine\Common\Collections\ArrayCollection|\NaturaPass\UserBundle\Entity\HuntType[]
     *
     * @ORM\ManyToMany(targetEntity="NaturaPass\UserBundle\Entity\HuntType")
     * @ORM\JoinTable(name="user_has_hunt_type",
     *      joinColumns={@ORM\JoinColumn(name="parameter_id", referencedColumnName="id")},
     *      inverseJoinColumns={@ORM\JoinColumn(name="hunttype_id", referencedColumnName="id")}
     *      )
     *
     */
    protected $hunttypes;


    /**
     * @var \DateTime
     *
     * @ORM\Column(name="created", type="datetime")
     * @ORMExtension\Timestampable(on="create")
     *
     */
    protected $created;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="updated", type="datetime")
     * @ORMExtension\Timestampable(on="update")
     *
     */
    protected $updated;


    /**
     * Constructor
     */
    public function __construct()
    {
        $this->hunttypes = new ArrayCollection();
    }

    /**
     * @param \DateTime $created
     *
     * @return HuntTypeParameter
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
     * @return HuntTypeParameter
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
     * @return \NaturaPass\UserBundle\Entity\HuntType[]|ArrayCollection
     */
    public function getHunttypes()
    {
        return $this->hunttypes;
    }

    /**
     * @param $hunttype \NaturaPass\UserBundle\Entity\HuntType
     *
     * @return HuntTypeParameter
     */
    public function addHunttype(HuntType $hunttype)
    {
        $this->hunttypes->add($hunttype);

        return $this;
    }

    /**
     * @param $hunttype \NaturaPass\UserBundle\Entity\HuntType
     *
     * @return HuntTypeParameter
     */
    public function removeHunttype(HuntType $hunttype)
    {
        $this->hunttypes->removeElement($hunttype);

        return $this;
    }

    /**
     * Set type
     *
     * @param integer $type
     * @return HuntTypeParameter
     */
    public function setType($type)
    {
        $this->type = $type;

        return $this;
    }

    /**
     * Get type
     *
     * @return integer
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * Set user
     *
     * @param \NaturaPass\UserBundle\Entity\User $user
     * @return HuntTypeParameter
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