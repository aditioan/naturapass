<?php

namespace NaturaPass\LoungeBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use NaturaPass\UserBundle\Entity\User;
use JMS\Serializer\Annotation as JMS;
use Gedmo\Mapping\Annotation as ORMExtension;

/**
 * LoungeInvitation
 *
 * @ORM\Table(name="lounge_invitation")
 * @ORM\Entity()
 * @ORM\HasLifecycleCallbacks
 * @JMS\ExclusionPolicy("all")
 */
class LoungeInvitation {

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
     * @JMS\Groups({"LoungeDetail", "LoungeLess"})
     */
    protected $id;

    /**
     *
     * @ORM\ManyToOne(targetEntity="NaturaPass\LoungeBundle\Entity\Lounge", inversedBy="inviteEmail")
     * @ORM\JoinColumn(nullable=false)
     */
    protected $lounge;

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
     * @JMS\Groups({"LoungeDetail", "LoungeLess"})
     * @var string
     */
    protected $email;

    /**
     * @var integer
     *
     * @ORM\Column(name="state", type="integer")
     *
     * @JMS\Expose
     * @JMS\Groups({"LoungeDetail", "LoungeLess"})
     */
    protected $state = self::INVITATION_SENT;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="created", type="datetime")
     * @ORMExtension\Timestampable(on="create")
     *
     * @JMS\Expose
     * @JMS\Groups({"LoungeDetail"})
     */
    protected $created;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="updated", type="datetime")
     * @ORMExtension\Timestampable(on="update")
     *
     * @JMS\Expose
     * @JMS\Groups({"LoungeDetail"})
     */
    protected $updated;

    function getId() {
        return $this->id;
    }

    /**
     * Set lounge
     *
     * @param \NaturaPass\LoungeBundle\Entity\Lounge $lounge
     * @return LoungeInvitation
     */
    public function setLounge(Lounge $lounge) {
        $this->lounge = $lounge;

        return $this;
    }

    /**
     * Get lounge
     *
     * @return \NaturaPass\LoungeBundle\Entity\Lounge
     */
    public function getLounge() {
        return $this->lounge;
    }

    /**
     * Set user
     *
     * @param \NaturaPass\UserBundle\Entity\User $user
     * @return Invitation
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
     * @return LoungeInvitation
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
     * @return LoungeInvitation
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
     * @return LoungeInvitation
     */
    public function setCreated(\DateTime $created) {
        $this->created = $created;
        return $this;
    }

    /**
     * @param \DateTime $updated
     * @return LoungeInvitation
     */
    public function setUpdated(\DateTime $updated) {
        $this->updated = $updated;
        return $this;
    }

}
