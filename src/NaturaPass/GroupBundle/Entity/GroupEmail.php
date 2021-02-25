<?php

namespace NaturaPass\GroupBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation as JMS;
use Gedmo\Mapping\Annotation as ORMExtension;

/**
 * GroupEmail
 *
 * @ORM\Table(name="group_has_email")
 * @ORM\Entity()
 * @ORM\HasLifecycleCallbacks
 * @JMS\ExclusionPolicy("all")
 */
class GroupEmail
{

    /**
     * @var integer
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     *
     * @JMS\Expose
     * @JMS\Groups({"GroupDetail", "GroupLess", "GroupID"})
     */
    protected $id;

    /**
     * @ORM\ManyToOne(targetEntity="NaturaPass\GroupBundle\Entity\Group", inversedBy="emails")
     * @ORM\JoinColumn(nullable=false)
     *
     * @JMS\Expose
     * @JMS\Groups({"GroupDetail", "GroupLess", "GroupID"})
     */
    protected $group;

    /**
     * @ORM\Column(name="type", type="string", length=255)
     */
    protected $type;

    /**
     * @var integer
     * @ORM\Column(name="wanted", type="integer")
     */
    protected $wanted;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="created", type="datetime")
     * @ORMExtension\Timestampable(on="create")
     *
     * @JMS\Expose
     * @JMS\Groups({"GroupEmailLess"})
     */
    protected $created;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="updated", type="datetime")
     * @ORMExtension\Timestampable(on="update")
     *
     * @JMS\Expose
     * @JMS\Groups({"GroupEmailLess"})
     */
    protected $updated;


    /**
     * Set created
     *
     * @param \DateTime $created
     * @return GroupEmail
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
     * Set updated
     *
     * @param \DateTime $updated
     * @return GroupEmail
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
     * Set groupe
     *
     * @param \NaturaPass\GroupBundle\Entity\Group $group
     * @return GroupEmail
     */
    public function setGroup(Group $group)
    {
        $this->group = $group;

        return $this;
    }

    /**
     * Get groupe
     *
     * @return \NaturaPass\GroupBundle\Entity\Group
     */
    public function getGroup()
    {
        return $this->group;
    }

    /**
     * Set wanted
     *
     * @param boolean $wanted
     * @return GroupEmail
     */
    public function setWanted($wanted)
    {
        $this->wanted = $wanted;

        return $this;
    }

    /**
     * Get wanted
     *
     * @return integer
     */
    public function getWanted()
    {
        return $this->wanted;
    }

    /**
     * @return mixed
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * @param mixed $type
     * @return GroupEmail
     */
    public function setType($type)
    {
        $this->type = $type;

        return $this;
    }


}
