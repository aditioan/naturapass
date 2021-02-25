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

/**
 * Class Device
 *
 * @ORM\Table(name="user_device_sended_ids")
 * @ORM\Entity()
 * @ORM\HasLifecycleCallbacks
 * @JMS\ExclusionPolicy("all")
 */
class UserDeviceSended
{

    const TYPE_MAP = 1;
    const TYPE_SHAPE = 2;
    const TYPE_DISTRIBUTOR = 3;
    const TYPE_GROUP = 4;
    const TYPE_HUNT = 5;
    const TYPE_ADDRESS = 6;
    const TYPE_COLOR = 7;
    const TYPE_FAVORITE = 8;
    const TYPE_DOG_BREED = 9;
    const TYPE_DOG_TYPE = 10;
    const TYPE_WEAPON_BRAND = 11;
    const TYPE_WEAPON_CALIBRE = 12;

    /**
     * @var \NaturaPass\UserBundle\Entity\User
     *
     * @ORM\OneToOne(targetEntity="NaturaPass\UserBundle\Entity\User", cascade={"persist", "remove"})
     * @ORM\Id
     *
     */
    protected $user;

    /**
     * @var string
     *
     * @ORM\Column(name="guid", type="string", length=255)
     * @ORM\Id
     *
     */
    protected $guid;

    /**
     * @var integer
     *
     * @ORM\Column(name="type", type="integer")
     * @ORM\Id
     *
     */
    protected $type = self::TYPE_MAP;

    /**
     * @var string
     *
     * @ORM\Column(name="ids", type="text", length=100000, nullable=true)
     *
     */
    protected $ids;

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

    public function __construct()
    {
    }

    /**
     * @param mixed $user
     *
     * @return UserDeviceSended
     */
    public function setUser(User $user)
    {
        $this->user = $user;

        return $this;
    }

    /**
     * @return User
     */
    public function getUser()
    {
        return $this->user;
    }

    /**
     * @param int $type
     *
     * @return UserDeviceSended
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
     * @param \DateTime $created
     *
     * @return UserDeviceSended
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
     * @return UserDeviceSended
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
     * @param string $guid
     *
     * @return UserDeviceSended
     */
    public function setGuid($guid)
    {
        $this->guid = $guid;

        return $this;
    }

    /**
     * @return Device
     */
    public function getGuid()
    {
        return $this->guid;
    }

    /**
     * @param string $ids
     *
     * @return UserDeviceSended
     */
    public function setIds($ids)
    {
        $this->ids = $ids;

        return $this;
    }

    /**
     * @return string
     */
    public function getIds()
    {
        return $this->ids;
    }

    /**
     * @return array
     */
    public function getIdsArray()
    {
        return explode(",", $this->ids);
    }


}