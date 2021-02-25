<?php

namespace NaturaPass\UserBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation as JMS;
use Gedmo\Mapping\Annotation as ORMExtension;
use NaturaPass\NotificationBundle\Entity\AbstractNotification;

/**
 * Parameters
 *
 * @ORM\Table(name="parameters_has_notification")
 * @ORM\Entity()
 * @ORM\HasLifecycleCallbacks
 * @JMS\ExclusionPolicy("all")
 */
class ParametersNotification
{

    /**
     * @var Parameters
     *
     * @ORM\ManyToOne(targetEntity="NaturaPass\UserBundle\Entity\Parameters", inversedBy="notifications")
     * @ORM\Id
     */
    protected $parameters;

    /**
     * @ORM\Column(name="type", type="string", length=255)
     * @ORM\Id
     */
    protected $type;

    /**
     * @var integer
     * @ORM\Column(name="wanted", type="integer")
     */
    protected $wanted;

    /**
     * @var integer
     *
     * @ORM\Column(name="object_id", type="integer", options={"default" = 0})
     * @ORM\Id
     *
     */
    protected $objectID;

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

    /**
     * Set wanted
     *
     * @param integer $wanted
     * @return ParametersNotification
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
     * Set created
     *
     * @param \DateTime $created
     * @return ParametersNotification
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
     * @return ParametersNotification
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
     * @param integer $objectID
     *
     * @return $this
     */
    public function setObjectID($objectID)
    {
        $this->objectID = $objectID;
        return $this;
    }

    /**
     * @return integer
     */
    public function getObjectID()
    {
        return $this->objectID;
    }

    /**
     * Set parameters
     *
     * @param \NaturaPass\UserBundle\Entity\Parameters $parameters
     * @return ParametersNotification
     */
    public function setParameters(Parameters $parameters)
    {
        $this->parameters = $parameters;

        return $this;
    }

    /**
     * Get parameters
     *
     * @return \NaturaPass\UserBundle\Entity\Parameters
     */
    public function getParameters()
    {
        return $this->parameters;
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
     * @return ParametersNotification
     */
    public function setType($type)
    {
        $this->type = $type;

        return $this;
    }
}
