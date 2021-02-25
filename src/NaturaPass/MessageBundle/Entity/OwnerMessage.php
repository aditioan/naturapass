<?php

namespace NaturaPass\MessageBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation as JMS;
use Gedmo\Mapping\Annotation as ORMExtension;
use NaturaPass\UserBundle\Entity\User;

/**
 * OwnerMessage
 *
 * @ORM\Table(name="user_has_message")
 * @ORM\Entity
 */
class OwnerMessage {
    const MESSAGE_UNREAD = 0;
    const MESSAGE_READ = 1;

    /**
     * @var integer
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @var \NaturaPass\UserBundle\Entity\User
     *
     * @ORM\ManyToOne(targetEntity="NaturaPass\UserBundle\Entity\User")
     * @ORM\JoinColumn(onDelete="CASCADE")
     */
    protected $owner;
    
    /**
     * @var \NaturaPass\MessageBundle\Entity\Message
     *
     * @ORM\ManyToOne(targetEntity="NaturaPass\MessageBundle\Entity\Message")
     * @ORM\JoinColumn(onDelete="CASCADE")
     */
    protected $message;

    /**
     * @var integer
     *
     * @ORM\Column(name="state", type="integer")
     *
     * @JMS\Expose
     */
    protected $read = self::MESSAGE_UNREAD;

    /**
     * Get id
     *
     * @return integer 
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set read
     *
     * @param integer $read
     * @return OwnerMessage
     */
    public function setRead($read)
    {
        $this->read = $read;

        return $this;
    }

    /**
     * Get read
     *
     * @return integer 
     */
    public function getRead()
    {
        return $this->read;
    }

    /**
     * Set owner
     *
     * @param \NaturaPass\UserBundle\Entity\User $owner
     * @return OwnerMessage
     */
    public function setOwner(\NaturaPass\UserBundle\Entity\User $owner = null)
    {
        $this->owner = $owner;

        return $this;
    }

    /**
     * Get owner
     *
     * @return \NaturaPass\UserBundle\Entity\User 
     */
    public function getOwner()
    {
        return $this->owner;
    }

    /**
     * Set message
     *
     * @param \NaturaPass\MessageBundle\Entity\Message $message
     * @return OwnerMessage
     */
    public function setMessage(\NaturaPass\MessageBundle\Entity\Message $message = null)
    {
        $this->message = $message;

        return $this;
    }

    /**
     * Get message
     *
     * @return \NaturaPass\MessageBundle\Entity\Message 
     */
    public function getMessage()
    {
        return $this->message;
    }
}
