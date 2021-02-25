<?php

namespace NaturaPass\MessageBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use JMS\Serializer\Annotation as JMS;
use Gedmo\Mapping\Annotation as ORMExtension;

/**
 * Conversation
 *
 * @ORM\Table()
 * @ORM\Entity
 */
class Conversation
{
    /**
     * @var integer
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @var ArrayCollection
     *
     * @ORM\ManyToMany(targetEntity="NaturaPass\UserBundle\Entity\User", inversedBy="conversations")
     * @ORM\JoinTable(name="conversation_has_participant")
     */
    protected $participants;

    /**
     * @var ArrayCollection
     *
     * @ORM\OneToMany(targetEntity="NaturaPass\MessageBundle\Entity\Message", mappedBy="conversation")
     */
    protected $messages;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="updated", type="datetime")
     * @ORMExtension\Timestampable(on="update")
     */
    private $updated;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="created", type="datetime")
     * @ORMExtension\Timestampable(on="create")
     */
    private $created;

    public function __construct() {
        $this->participants = new ArrayCollection();
        $this->messages = new ArrayCollection();
    }


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
     * Set updated
     *
     * @param \DateTime $updated
     * @return Conversation
     */
    public function setUpdated($updated)
    {
        $this->updated = $updated;
    
        return $this;
    }

    /**
     * Get updated
     *
     * @return \DateTime 
     */
    public function getUpdated()
    {
        return $this->updated;
    }

    /**
     * Set created
     *
     * @param \DateTime $created
     * @return Conversation
     */
    public function setCreated($created)
    {
        $this->created = $created;
    
        return $this;
    }

    /**
     * Get created
     *
     * @return \DateTime 
     */
    public function getCreated()
    {
        return $this->created;
    }

    /**
     * Add participants
     *
     * @param \NaturaPass\UserBundle\Entity\User $participants
     * @return Conversation
     */
    public function addParticipant(\NaturaPass\UserBundle\Entity\User $participants)
    {
        $this->participants[] = $participants;
    
        return $this;
    }

    /**
     * Remove participants
     *
     * @param \NaturaPass\UserBundle\Entity\User $participants
     */
    public function removeParticipant(\NaturaPass\UserBundle\Entity\User $participants)
    {
        $this->participants->removeElement($participants);
    }

    /**
     * Get participants
     *
     * @return \Doctrine\Common\Collections\ArrayCollection|\NaturaPass\UserBundle\Entity\User[]
     */
    public function getParticipants()
    {
        return $this->participants;
    }

    /**
     * Add message
     *
     * @param \NaturaPass\MessageBundle\Entity\Message $message
     * @return Conversation
     */
    public function addMessage(Message $message)
    {
        $this->messages[] = $message;
    
        return $this;
    }

    /**
     * Remove message
     *
     * @param \NaturaPass\MessageBundle\Entity\Message $message
     */
    public function removeMessage(Message $message)
    {
        $this->messages->removeElement($message);
    }

    /**
     * Get messages
     *
     * @return \Doctrine\Common\Collections\ArrayCollection|\NaturaPass\MessageBundle\Entity\Message[]
     */
    public function getMessages()
    {
        return $this->messages;
    }
}