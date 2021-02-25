<?php

namespace NaturaPass\LoungeBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation as JMS;
use Gedmo\Mapping\Annotation as ORMExtension;
use NaturaPass\UserBundle\Entity\User;

/**
 * LoungeUser
 *
 * @ORM\Table(name="lounge_not_member")
 * @ORM\Entity()
 * @ORM\HasLifecycleCallbacks
 * @JMS\ExclusionPolicy("all")
 */
class LoungeNotMember {

    const PARTICIPATION_NO = 0;
    const PARTICIPATION_YES = 1;
    const PARTICIPATION_DONTKNOW = 2;

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
     * @ORM\ManyToOne(targetEntity="NaturaPass\LoungeBundle\Entity\Lounge", inversedBy="subscribersNotMember")
     * @ORM\JoinColumn(nullable=false)
     */
    protected $lounge;
    
    /**
     * 
     * @ORM\Column(name="firstname", type="string", length=255)
     * @ORM\JoinColumn(nullable=false)
     *
     * @JMS\Expose
     * @JMS\Groups({"LoungeUserLess"})
     * @var string
     */
    protected $firstname;
    
    /**
     * @ORM\Column(name="lastname", type="string", length=255)
     * @ORM\JoinColumn(nullable=false)
     *
     * @JMS\Expose
     * @JMS\Groups({"LoungeUserLess"})
     * @var string
     */
    protected $lastname;

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
    
    
    function getId() {
        return $this->id;
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
    }/**
     * Set firstname
     *
     * @param string $firstname
     * @return User
     */
    public function setFirstname($firstname) {
        $this->firstname = $firstname;

        return $this;
    }

    /**
     * Get firstname
     *
     * @return string
     */
    public function getFirstname() {
        return $this->firstname;
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
     * Set lastname
     *
     * @param string $lastname
     * @return User
     */
    public function setLastname($lastname) {
        $this->lastname = $lastname;

        return $this;
    }

    /**
     * Get lastname
     *
     * @return string
     */
    public function getLastname() {
        return $this->lastname;
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
     * Get participation
     *
     * @return integer
     */
    public function getParticipation() {
        return $this->participation;
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
    
    
    
    public function getFullName() {
        return $this->firstname . ' ' . $this->lastname;
    }

}
