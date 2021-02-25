<?php

namespace NaturaPass\GroupBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as ORMExtension;
use JMS\Serializer\Annotation as JMS;
use NaturaPass\UserBundle\Entity\User;

/**
 * GroupMessage
 *
 * @ORM\Table(name="group_has_message")
 * @ORM\Entity()
 * @ORM\HasLifecycleCallbacks
 * @JMS\ExclusionPolicy("all")
 */
class GroupMessage {

    /**
     * @var integer
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @ORM\ManyToOne(targetEntity="NaturaPass\GroupBundle\Entity\Group", inversedBy="messages")
     *
     * @JMS\Expose
     * @JMS\Groups({"GroupMessageDetail"})
     */
    protected $group;

    /**
     * @ORM\ManyToOne(targetEntity="NaturaPass\UserBundle\Entity\User")
     *
     * @JMS\Expose
     * @JMS\Groups({"GroupMessageDetail", "GroupMessageLess"})
     */
    protected $owner;

    /**
     * @var string
     *
     * @ORM\Column(name="content", type="text")
     *
     * @JMS\Expose
     * @JMS\Groups({"GroupMessageDetail", "GroupMessageLess"})
     */
    protected $content;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="created", type="datetime")
     * @ORMExtension\Timestampable(on="create")
     *
     * @JMS\Expose
     * @JMS\Groups({"GroupMessageDetail", "GroupMessageLess"})
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
     * @return GroupMessage
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
     * @return GroupMessage
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
     * @return Group
     */
    public function getGroup() {
        return $this->group;
    }

    public function setGroup(Group $group) {
        $this->group = $group;
        return $this;
    }

    /**
     * Set owner
     *
     * @param \NaturaPass\UserBundle\Entity\User $owner
     * @return GroupMessage
     */
    public function setOwner(User $owner) {
        $this->owner = $owner;

        return $this;
    }

    /**
     * Get owner
     *
     * @return \NaturaPass\GroupBundle\Entity\GroupUser
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
