<?php

namespace NaturaPass\GroupBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation as JMS;
use Gedmo\Mapping\Annotation as ORMExtension;
use NaturaPass\UserBundle\Entity\User;

/**
 * GroupUser
 *
 * @ORM\Table(name="group_has_user")
 * @ORM\Entity()
 * @ORM\HasLifecycleCallbacks
 * @JMS\ExclusionPolicy("all")
 */
class GroupUser {

    const ACCESS_INVITED = 0;
    const ACCESS_RESTRICTED = 1;
    const ACCESS_DEFAULT = 2;
    const ACCESS_ADMIN = 3;

    /**
     * @ORM\Id
     * @ORM\ManyToOne(targetEntity="NaturaPass\GroupBundle\Entity\Group", inversedBy="subscribers")
     *
     * @JMS\Expose
     * @JMS\Groups({"GroupDetail", "GroupLess", "GroupID"})
     */
    protected $group;

    /**
     * @ORM\Id
     * @ORM\ManyToOne(targetEntity="NaturaPass\UserBundle\Entity\User", inversedBy="groupSubscribes")
     *
     * @JMS\Expose
     * @JMS\Groups({"GroupUserLess"})
     */
    protected $user;

    /**
     * @var integer
     *
     * @ORM\Column(name="access", type="integer")
     *
     * @JMS\Expose
     * @JMS\Groups({"GroupUserLess"})
     */
    protected $access = self::ACCESS_DEFAULT;

    /**
     * @var boolean
     *
     * @ORM\Column(name="mailable", type="integer", options={"default"=true})
     *
     * @JMS\Expose
     * @JMS\Groups({"GroupUserLess"})
     */
    protected $mailable = 1;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="created", type="datetime")
     * @ORMExtension\Timestampable(on="create")
     *
     * @JMS\Expose
     * @JMS\Groups({"GroupUserLess"})
     */
    protected $created;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="updated", type="datetime")
     * @ORMExtension\Timestampable(on="update")
     *
     * @JMS\Expose
     * @JMS\Groups({"GroupUserLess"})
     */
    protected $updated;

    /**
     * @var integer
     *
     * @ORM\Column(name="refresh", type="integer")
     *
     * @JMS\Expose
     * @JMS\Groups({"GroupUserLess"})
     */
    protected $refresh = 1;

    /**
     * Set access
     *
     * @param integer $access
     * @return GroupUser
     */
    public function setAccess($access) {
        $this->access = $access;

        return $this;
    }

    /**
     * Get access
     *
     * @return integer
     */
    public function getAccess() {
        return $this->access;
    }

    /**
     * Set created
     *
     * @param \DateTime $created
     * @return GroupUser
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
     * @return GroupUser
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
     * Set groupe
     *
     * @param \NaturaPass\GroupBundle\Entity\Group $group
     * @return GroupUser
     */
    public function setGroup(Group $group) {
        $this->group = $group;

        return $this;
    }

    /**
     * Get groupe
     *
     * @return \NaturaPass\GroupBundle\Entity\Group
     */
    public function getGroup() {
        return $this->group;
    }

    /**
     * Set user
     *
     * @param \NaturaPass\UserBundle\Entity\User $user
     * @return GroupUser
     */
    public function setUser(User $user) {
        $this->user = $user;

        return $this;
    }

    /**
     * Get user
     *
     * @return \NaturaPass\UserBundle\Entity\User
     */
    public function getUser() {
        return $this->user;
    }

    /**
     * @param boolean $mailable
     *
     * @return GroupUser
     */
    public function setMailable($mailable)
    {
        $this->mailable = $mailable;

        return $this;
    }

    /**
     * @return boolean
     */
    public function getMailable()
    {
        return $this->mailable;
    }

    /**
     * @param integer $refresh
     *
     * @return GroupUser
     */
    public function setRefresh($refresh)
    {
        $this->refresh = $refresh;

        return $this;
    }

    /**
     * @return integer
     */
    public function getRefresh()
    {
        return $this->refresh;
    }
}
