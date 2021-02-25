<?php

namespace NaturaPass\UserBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation as JMS;
use Gedmo\Mapping\Annotation as ORMExtension;

/**
 * UserFriend
 *
 * @ORM\Table(name="user_has_friend")
 * @ORM\Entity()
 * @JMS\ExclusionPolicy("all")
 */
class UserFriend {

    const ASKED = 1;
    const CONFIRMED = 2;
    const REJECTED = 3;
    const TYPE_BOTH = 0;
    const TYPE_FRIEND = 1;
    const TYPE_KNOWING = 2;

    /**
     * @ORM\Id
     * @ORM\ManyToOne(targetEntity="NaturaPass\UserBundle\Entity\User", inversedBy="userFriends")
     * @ORM\JoinColumn(nullable=false)
     *
     * @JMS\Expose
     * @JMS\Groups({"UserFriendDetail"})
     */
    protected $user;

    /**
     * @ORM\Id
     * @ORM\ManyToOne(targetEntity="NaturaPass\UserBundle\Entity\User", inversedBy="friendsUser")
     * @ORM\JoinColumn(nullable=false)
     *
     * @JMS\Expose
     * @JMS\Groups({"UserFriendDetail", "UserFriend"})
     */
    protected $friend;

    /**
     * @var integer
     *
     * @ORM\Column(name="state", type="integer")
     *
     * @JMS\Expose
     * @JMS\Groups({"UserFriendDetail", "UserFriend"})
     */
    protected $state = self::ASKED;

    /**
     * @var integer
     *
     * @ORM\Column(name="type", type="integer")
     *
     * @JMS\Expose
     * @JMS\Groups({"UserFriendDetail", "UserFriend"})
     */
    protected $type = self::TYPE_FRIEND;

    /**
     * @var \DateTime
     * @ORMExtension\Timestampable(on="create")
     *
     * @ORM\Column(name="created", type="datetime")
     *
     * @JMS\Expose
     * @JMS\Groups({"UserFriendDetail", "UserFriend"})
     */
    protected $created;

    /**
     * @var \DateTime
     * @ORMExtension\Timestampable(on="update")
     *
     * @ORM\Column(name="updated", type="datetime")
     *
     * @JMS\Expose
     * @JMS\Groups({"UserFriendDetail", "UserFriend"})
     */
    protected $updated;

    /**
     * Set state
     *
     * @param integer $state
     * @return UserFriend
     */
    public function setState($state) {
        $this->state = $state;

        return $this;
    }

    /**
     * Get state
     *
     * @return integer
     */
    public function getState() {
        return $this->state;
    }

    /**
     * Set updated
     *
     * @param \DateTime $updated
     * @return UserFriend
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
     * Set updated
     *
     * @param \DateTime $updated
     * @return UserFriend
     */
    public function setCreated($updated) {
        $this->updated = $updated;

        return $this;
    }

    /**
     * Get updated
     *
     * @return \DateTime
     */
    public function getCreated() {
        return $this->updated;
    }

    /**
     * Set user
     *
     * @param \NaturaPass\UserBundle\Entity\User $user
     * @return UserFriend
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
     * Set friend
     *
     * @param \NaturaPass\UserBundle\Entity\User $friend
     * @return UserFriend
     */
    public function setFriend(User $friend) {
        $this->friend = $friend;

        return $this;
    }

    /**
     * Get friend
     *
     * @return \NaturaPass\UserBundle\Entity\User
     */
    public function getFriend() {
        return $this->friend;
    }

    /**
     * @return int
     */
    public function getType() {
        return $this->type;
    }

    /**
     * @param $type
     * @return $this
     */
    public function setType($type) {
        $this->type = $type;
        return $this;
    }

}
