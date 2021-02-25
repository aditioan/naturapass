<?php
/**
 * Created by PhpStorm.
 * User: vincentvalot
 * Date: 08/09/14
 * Time: 16:45
 */

namespace NaturaPass\UserBundle\Entity;

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
 * @ORM\Table(name="user_has_device")
 * @ORM\Entity()
 * @ORM\HasLifecycleCallbacks
 * @JMS\ExclusionPolicy("all")
 */
class UserDevice {

    /**
     * @ORM\Id
     * @ORM\ManyToOne(targetEntity="NaturaPass\UserBundle\Entity\Device", inversedBy="owners")
     * @ORM\JoinColumn(nullable=false, onDelete="CASCADE")
     *
     * @JMS\Expose
     * @JMS\Groups({"DeviceLess"})
     */
    protected $device;

    /**
     * @ORM\Id
     * @ORM\ManyToOne(targetEntity="NaturaPass\UserBundle\Entity\User", inversedBy="devices")
     * @ORM\JoinColumn(nullable=false, onDelete="CASCADE")
     *
     * @JMS\Expose
     * @JMS\Groups({"DeviceLess"})
     */
    protected $owner;

    /**
     * @var boolean
     *
     * @ORM\Column(name="authorized", type="boolean", options={"default": true})
     *
     * @JMS\Expose
     * @JMS\Groups({"DeviceLess"})
     */
    protected $authorized = true;

    /**
     * @var boolean
     *
     * @ORM\Column(name="verified", type="boolean", options={"default": true})
     *
     * @JMS\Expose
     * @JMS\Groups({"DeviceLess"})
     */
    protected $verified = false;

    /**
     * @var string
     *
     * @ORM\Column(name="verification_token", type="string", length=255, nullable=true)
     *
     * @JMS\Expose
     * @JMS\Groups({"DeviceLess"})
     */
    protected $verificationToken;

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

    /**
     * @param \NaturaPass\UserBundle\Entity\Device $device
     *
     * @return UserDevice
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
     * @return UserDevice
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
     * @param boolean $authorized
     *
     * @return UserDevice
     */
    public function setAuthorized($authorized)
    {
        $this->authorized = $authorized;

        return $this;
    }

    /**
     * @return boolean
     */
    public function isAuthorized()
    {
        return $this->authorized;
    }

    /**
     * @param \DateTime $created
     *
     * @return UserDevice
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
     * @return UserDevice
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
     * @param boolean $verified
     *
     * @return UserDevice
     */
    public function setVerified($verified)
    {
        $this->verified = $verified;

        return $this;
    }

    /**
     * @return boolean
     */
    public function isVerified()
    {
        return $this->verified;
    }

    /**
     * @param string $verificationToken
     *
     * @return UserDevice
     */
    public function setVerificationToken($verificationToken)
    {
        $this->verificationToken = $verificationToken;

        return $this;
    }

    /**
     * @return string
     */
    public function getVerificationToken()
    {
        return $this->verificationToken;
    }
} 