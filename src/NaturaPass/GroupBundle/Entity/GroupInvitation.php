<?php

namespace NaturaPass\GroupBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use NaturaPass\UserBundle\Entity\User;
use JMS\Serializer\Annotation as JMS;
use Gedmo\Mapping\Annotation as ORMExtension;

/**
 * GroupInvitation
 *
 * @ORM\Table(name="group_invitation")
 * @ORM\Entity()
 * @ORM\HasLifecycleCallbacks
 * @JMS\ExclusionPolicy("all")
 */
class GroupInvitation {

    const INVITATION_SENT = 0;
    const INVITATION_INSCRIPTION_SUCCESS = 1;

    /**
     * @var integer
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     *
     * @JMS\Expose
     * @JMS\Groups({"GroupDetail", "GroupLess"})
     */
    protected $id;

    /**
     *
     * @ORM\ManyToOne(targetEntity="NaturaPass\GroupBundle\Entity\Group", inversedBy="inviteEmail")
     * @ORM\JoinColumn(nullable=false)
     */
    protected $group;

    /**
     * @ORM\ManyToOne(targetEntity="NaturaPass\UserBundle\Entity\User")
     * @ORM\JoinColumn(nullable=false)
     *
     * @JMS\Expose
     */
    protected $user;

    /**
     *
     * @ORM\Column(name="email", type="string", length=255)
     * @ORM\JoinColumn(nullable=false)
     *
     * @JMS\Expose
     * @JMS\Groups({"GroupDetail", "GroupLess"})
     * @var string
     */
    protected $email;

    /**
     * @var integer
     *
     * @ORM\Column(name="state", type="integer")
     *
     * @JMS\Expose
     * @JMS\Groups({"GroupDetail", "GroupLess"})
     */
    protected $state = self::INVITATION_SENT;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="created", type="datetime")
     * @ORMExtension\Timestampable(on="create")
     *
     * @JMS\Expose
     * @JMS\Groups({"GroupDetail"})
     */
    protected $created;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="updated", type="datetime")
     * @ORMExtension\Timestampable(on="update")
     *
     * @JMS\Expose
     * @JMS\Groups({"GroupDetail"})
     */
    protected $updated;

    function getId() {
        return $this->id;
    }

    /**
     * Set group
     *
     * @param \NaturaPass\GroupBundle\Entity\Group $group
     * @return GroupInvitation
     */
    public function setGroup(Group $group) {
        $this->group = $group;

        return $this;
    }

    /**
     * Get group
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
     * @return GroupInvitation
     */
    public function setUser(\NaturaPass\UserBundle\Entity\User $user) {
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
     * Set email
     *
     * @param string $email
     * @return GroupInvitation
     */
    public function setEmail($email) {
        $this->email = $email;

        return $this;
    }

    /**
     * Get email
     *
     * @return string
     */
    public function getEmail() {
        return $this->email;
    }

    /**
     * Set state
     *
     * @param integer $state
     * @return GroupInvitation
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
     * @return \DateTime
     */
    public function getCreated() {
        return $this->created;
    }

    /**
     * @return \DateTime
     */
    public function getUpdated() {
        return $this->updated;
    }

    /**
     * @param \DateTime $created
     * @return GroupInvitation
     */
    public function setCreated(\DateTime $created) {
        $this->created = $created;
        return $this;
    }

    /**
     * @param \DateTime $updated
     * @return GroupInvitation
     */
    public function setUpdated(\DateTime $updated) {
        $this->updated = $updated;
        return $this;
    }

}
