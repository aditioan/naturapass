<?php

namespace NaturaPass\LoungeBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation as JMS;
use Gedmo\Mapping\Annotation as ORMExtension;
use NaturaPass\UserBundle\Entity\User;

/**
 * LoungeUser
 *
 * @ORM\Table(name="lounge_has_user")
 * @ORM\Entity()
 * @ORM\HasLifecycleCallbacks
 * @JMS\ExclusionPolicy("all")
 */
class LoungeUser {

    const PARTICIPATION_NO = 0;
    const PARTICIPATION_YES = 1;
    const PARTICIPATION_DONTKNOW = 2;

    const ACCESS_INVITED = 0;
    const ACCESS_RESTRICTED = 1;
    const ACCESS_DEFAULT = 2;
    const ACCESS_ADMIN = 3;

    /**
     * @ORM\Id
     * @ORM\ManyToOne(targetEntity="NaturaPass\LoungeBundle\Entity\Lounge", inversedBy="subscribers")
     * @ORM\JoinColumn(nullable=false)
     */
    protected $lounge;

    /**
     * @ORM\Id
     * @ORM\ManyToOne(targetEntity="NaturaPass\UserBundle\Entity\User", inversedBy="loungeSubscribes")
     * @ORM\JoinColumn(nullable=false)
     *
     * @JMS\Expose
     * @JMS\Groups({"LoungeUserLess"})
     */
    protected $user;

    /**
     * @var integer
     *
     * @ORM\Column(name="access", type="integer")
     *
     * @JMS\Expose
     * @JMS\Groups({"LoungeUserLess"})
     */
    protected $access;

    /**
     * @var boolean
     *
     * @ORM\Column(name="geolocation", type="boolean")
     *
     * @JMS\Expose
     * @JMS\Groups({"LoungeUserLess"})
     */
    protected $geolocation = false;

    /**
     * @var integer
     *
     * @ORM\Column(name="participation", type="integer")
     *
     * @JMS\Expose
     * @JMS\Groups({"LoungeUserLess"})
     */
    protected $participation = self::PARTICIPATION_DONTKNOW;

    /**
     * @ORM\Column(name="publicComment", type="string", length=255, nullable=true)
     *
     * @JMS\Expose
     * @JMS\Groups({"LoungeUserDetail"})
     *
     * @var string
     */
    protected $publicComment;

    /**
     * @ORM\Column(name="privateComment", type="string", length=255, nullable=true)
     *
     * @JMS\Expose
     * @JMS\Groups({"LoungeUserDetail"})
     *
     * @var string
     */
    protected $privateComment;

    /**
     * @ORM\Column(name="quiet", type="boolean")
     *
     * @JMS\Expose
     * @JMS\Groups({"LoungeUserDetail"})
     *
     * @var boolean
     */
    protected $quiet = false;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="created", type="datetime")
     * @ORMExtension\Timestampable(on="create")
     *
     * @JMS\Expose
     * @JMS\Groups({"LoungeUserLess"})
     */
    protected $created;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="updated", type="datetime")
     * @ORMExtension\Timestampable(on="update")
     *
     * @JMS\Expose
     * @JMS\Groups({"LoungeUserLess"})
     */
    protected $updated;

    /**
     * @var integer
     *
     * @ORM\Column(name="mailable", type="integer", options={"default"=true})
     *
     * @JMS\Expose
     * @JMS\Groups({"LoungeUserLess"})
     */
    protected $mailable = 1;

    /**
     * Set access
     *
     * @param integer $access
     * @return LoungeUser
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
     * Set geolocation
     *
     * @param boolean $geolocation
     * @return LoungeUser
     */
    public function setGeolocation($geolocation) {
        $this->geolocation = $geolocation;

        return $this;
    }

    /**
     * Get geolocation
     *
     * @return boolean
     */
    public function getGeolocation() {
        return $this->geolocation;
    }

    /**
     * Set lounge
     *
     * @param \NaturaPass\LoungeBundle\Entity\Lounge $lounge
     * @return LoungeUser
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
     * @return LoungeUser
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
     * Set participation
     *
     * @param integer $participation
     * @return LoungeUser
     */
    public function setParticipation($participation) {
        $this->participation = $participation;

        return $this;
    }

    /**
     * Get participation
     *
     * @return integer
     */
    public function getParticipation() {
        return $this->participation;
    }

    /**
     * @return string
     */
    public function getPublicComment() {
        return $this->publicComment;
    }

    /**
     * @return string
     */
    public function getPrivateComment() {
        return $this->privateComment;
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
     * @param $publicComment
     * @return $this
     */
    public function setPublicComment($publicComment) {
        $this->publicComment = $publicComment;
        return $this;
    }

    /**
     * @param $privateComment
     * @return $this
     */
    public function setPrivateComment($privateComment) {
        $this->privateComment = $privateComment;
        return $this;
    }

    /**
     * @param \DateTime $created
     * @return $this
     */
    public function setCreated(\DateTime $created) {
        $this->created = $created;
        return $this;
    }

    /**
     * @param \DateTime $updated
     * @return $this
     */
    public function setUpdated(\DateTime $updated) {
        $this->updated = $updated;
        return $this;
    }

    /**
     * @return bool
     */
    public function getQuiet() {
        return $this->quiet;
    }

    /**
     * @param $quiet
     * @return $this
     */
    public function setQuiet($quiet) {
        $this->quiet = $quiet;
        return $this;
    }

    /**
     * @param integer $mailable
     *
     * @return GroupUser
     */
    public function setMailable($mailable)
    {
        $this->mailable = $mailable;

        return $this;
    }

    /**
     * @return integer
     */
    public function getMailable()
    {
        return $this->mailable;
    }
}
