<?php

namespace NaturaPass\LoungeBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as ORMExtension;
use JMS\Serializer\Annotation as JMS;
use NaturaPass\UserBundle\Entity\User;

/**
 * LoungeMessage
 *
 * @ORM\Table(name="lounge_has_message")
 * @ORM\Entity()
 * @ORM\HasLifecycleCallbacks
 * @JMS\ExclusionPolicy("all")
 */
class LoungeMessage {

    /**
     * @var integer
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @ORM\ManyToOne(targetEntity="NaturaPass\LoungeBundle\Entity\Lounge", inversedBy="messages")
     *
     * @JMS\Expose
     * @JMS\Groups({"LoungeMessageDetail"})
     */
    protected $lounge;

    /**
     * @ORM\ManyToOne(targetEntity="NaturaPass\UserBundle\Entity\User")
     *
     * @JMS\Expose
     * @JMS\Groups({"LoungeMessageDetail", "LoungeMessageLess"})
     */
    protected $owner;

    /**
     * @var string
     *
     * @ORM\Column(name="content", type="text")
     *
     * @JMS\Expose
     * @JMS\Groups({"LoungeMessageDetail", "LoungeMessageLess"})
     */
    protected $content;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="created", type="datetime")
     * @ORMExtension\Timestampable(on="create")
     *
     * @JMS\Expose
     * @JMS\Groups({"LoungeMessageDetail", "LoungeMessageLess"})
     */
    protected $created;

    /**
     * @var string
     *
     * @ORM\Column(name="guid", type="text", nullable=true)
     */
    protected $guid;
    
    /**
     * Get id
     *
     * @return integer
     */
    public function getId() {
        return $this->id;
    }

    /**
     * Set content
     *
     * @param string $content
     * @return LoungeMessage
     */
    public function setContent($content) {
        $this->content = $content;

        return $this;
    }

    /**
     * Get content
     *
     * @return string
     */
    public function getContent() {
        return $this->content;
    }

    /**
     * Set created
     *
     * @param \DateTime $created
     * @return LoungeMessage
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
     * @return Lounge
     */
    public function getLounge() {
        return $this->lounge;
    }

    public function setLounge(Lounge $lounge) {
        $this->lounge = $lounge;
        return $this;
    }

    /**
     * Set owner
     *
     * @param \NaturaPass\UserBundle\Entity\User $owner
     * @return LoungeMessage
     */
    public function setOwner(User $owner) {
        $this->owner = $owner;

        return $this;
    }

    /**
     * Get owner
     *
     * @return \NaturaPass\LoungeBundle\Entity\LoungeUser
     */
    public function getOwner() {
        return $this->owner;
    }

    /**
     * Set guid
     *
     * @param string $guid
     * @return Message
     */
    public function setGuid($guid)
    {
        $this->guid = $guid;
    
        return $this;
    }

    /**
     * Get guid
     *
     * @return string 
     */
    public function getGuid()
    {
        return $this->guid;
    }    

}