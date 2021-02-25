<?php

namespace Admin\SentinelleBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation as JMS;
use Gedmo\Mapping\Annotation as ORMExtension;

/**
 * CardLabel
 *
 * @ORM\Table(name="`card_has_label_has_content`")
 * @ORM\Entity()
 *
 * @JMS\ExclusionPolicy("all")
 */
class CardLabelContent
{

    /**
     * @var integer
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     *
     * @JMS\Expose
     * @JMS\Groups({"LabelContentDetail", "LabelContentLess", "LabelContentID"})
     */
    protected $id;

    /**
     * @var string
     *
     * @ORM\Column(name="name", type="string", length=255)
     *
     * @JMS\Expose
     * @JMS\Groups({"LabelContentDetail", "LabelContentLess"})
     */
    protected $name;

    /**
     * @var integer
     *
     * @ORM\Column(name="visible", type="integer")
     *
     * @JMS\Expose
     * @JMS\Groups({"LabelContentDetail", "LabelContentLess"})
     */
    protected $visible;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="created", type="datetime")
     * @ORMExtension\Timestampable(on="create")
     *
     * @JMS\Expose
     * @JMS\Groups({"LabelContentDetail"})
     */
    protected $created;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="updated", type="datetime")
     * @ORMExtension\Timestampable(on="update")
     *
     * @JMS\Expose
     * @JMS\Groups({"LabelContentDetail"})
     */
    protected $updated;

    /**
     * @ORM\ManyToOne(targetEntity="Admin\SentinelleBundle\Entity\CardLabel", inversedBy="contents", cascade={"persist", "remove"})
     * @ORM\JoinColumn(onDelete="CASCADE", nullable=false)
     */
    protected $label;


    public function __toString()
    {
        return $this->id . $this->name;
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
     * Set name
     *
     * @param string $name
     * @return CardLabelContent
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * Get name
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }


    /**
     * Get visible
     *
     * @return integer
     */
    function getVisible()
    {
        return $this->visible;
    }

    /**
     * Set visible
     *
     * @param integer $visible
     * @return CardLabelContent
     */
    function setVisible($visible)
    {
        $this->visible = $visible;

        return $this;
    }

    /**
     * Constructor
     */
    public function __construct()
    {

    }

    /**
     * Set created
     *
     * @param \DateTime $created
     * @return CardLabelContent
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
     * @return CardLabelContent
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
     * Add Label
     *
     * @param \Admin\SentinelleBundle\Entity\CardLabel $label
     * @return CardLabelContent
     */
    public function setLabel(CardLabel $label)
    {
        $this->label = $label;

        return $this;
    }

    /**
     * Remove Label
     *
     * @return CardLabel
     */
    public function getLabel()
    {
        return $this->label;
    }
}
