<?php

namespace Admin\SentinelleBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation as JMS;
use Gedmo\Mapping\Annotation as ORMExtension;

/**
 * CardLabel
 *
 * @ORM\Table(name="`card_has_label`")
 * @ORM\Entity()
 *
 * @JMS\ExclusionPolicy("all")
 */
class CardLabel
{

    const TYPE_STRING = 0;
    const TYPE_TEXT = 1;
    const TYPE_INT = 10;
    const TYPE_FLOAT = 11;
    const TYPE_DATE = 20;
    const TYPE_HOUR = 21;
    const TYPE_DATE_HOUR = 22;
    const TYPE_SELECT = 30;
    const TYPE_SELECT_MULTIPLE = 31;
    const TYPE_SELECT2 = 32;
    const TYPE_CHECKBOX = 40;
    const TYPE_RADIO = 50;

    /**
     * @var integer
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     *
     * @JMS\Expose
     * @JMS\Groups({"LabelDetail", "LabelLess", "LabelID"})
     */
    protected $id;

    /**
     * @var string
     *
     * @ORM\Column(name="name", type="string", length=255)
     *
     * @JMS\Expose
     * @JMS\Groups({"LabelDetail", "LabelLess"})
     */
    protected $name;

    /**
     * @var integer
     *
     * @ORM\Column(name="type", type="integer")
     *
     * @JMS\Expose
     * @JMS\Groups({"LabelLess"})
     */
    protected $type = self::TYPE_STRING;

    /**
     * @var integer
     *
     * @ORM\Column(name="orderindex", type="integer")
     *
     * @JMS\Expose
     * @JMS\Groups({"LabelLess"})
     */
    protected $order = 0;

    /**
     * @var integer
     *
     * @ORM\Column(name="visible", type="integer")
     *
     * @JMS\Expose
     * @JMS\Groups({"LabelDetail", "LabelLess"})
     */
    protected $visible;

    /**
     * @var integer
     *
     * @ORM\Column(name="required", type="integer")
     *
     * @JMS\Expose
     * @JMS\Groups({"LabelDetail", "LabelLess"})
     */
    protected $required = 0;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="created", type="datetime")
     * @ORMExtension\Timestampable(on="create")
     *
     * @JMS\Expose
     * @JMS\Groups({"LabelDetail"})
     */
    protected $created;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="updated", type="datetime")
     * @ORMExtension\Timestampable(on="update")
     *
     * @JMS\Expose
     * @JMS\Groups({"LabelDetail"})
     */
    protected $updated;

    /**
     * @ORM\ManyToOne(targetEntity="Admin\SentinelleBundle\Entity\Card", inversedBy="labels")
     * @ORM\JoinColumn(nullable=false)
     */
    protected $card;

    /**
     * @var ArrayCollection|CardLabelContent[] $contents
     *
     * @ORM\OneToMany(targetEntity="Admin\SentinelleBundle\Entity\CardLabelContent", mappedBy="label", cascade={"persist", "remove"}, fetch="EXTRA_LAZY", orphanRemoval=true)
     * @ORM\JoinColumn(onDelete="CASCADE", nullable=true)
     *
     * @JMS\Expose
     * @JMS\Groups({"LabelDetail"})
     */
    protected $contents;

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
     * @return Card
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
     * @return CardLabel
     */
    function setVisible($visible)
    {
        $this->visible = $visible;

        return $this;
    }

    /**
     * Get required
     *
     * @return integer
     */
    function getRequired()
    {
        return $this->required;
    }

    /**
     * Set required
     *
     * @param integer $required
     * @return CardLabel
     */
    function setRequired($required)
    {
        $this->required = $required;

        return $this;
    }

    /**
     * Get type
     *
     * @return integer
     */
    function getType()
    {
        return $this->type;
    }

    /**
     * Set type
     *
     * @param integer $type
     * @return CardLabel
     */
    function setType($type)
    {
        $this->type = $type;

        return $this;
    }

    /**
     * Get order
     *
     * @return integer
     */
    function getOrder()
    {
        return $this->order;
    }

    /**
     * Set order
     *
     * @param integer $order
     * @return CardLabel
     */
    function setOrder($order)
    {
        $this->order = $order;

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
     * @return Card
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
     * @return Card
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
     * Add Card
     *
     * @param \Admin\SentinelleBundle\Entity\Card $card
     * @return CardLabel
     */
    public function setCard(Card $card)
    {
        $this->card = $card;

        return $this;
    }

    /**
     * Remove Card
     *
     * @return Card
     */
    public function getCard()
    {
        return $this->card;
    }

    /**
     * Get CardLabelContent
     *
     * @return ArrayCollection|CardLabelContent[]
     */
    public function getContents()
    {
        return $this->contents;
    }

    /**
     * Set CardLabelContent
     *
     * @param CardLabelContent[] $contents
     *
     * @return CardLabel
     */
    public function setContents($contents)
    {
        foreach ($contents as $content) {
            $content->setLabel($this);
        }

        $this->contents = $contents;

        return $this;
    }

    /**
     * Add CardLabelContent
     *
     * @param \Admin\SentinelleBundle\Entity\CardLabelContent $cardLabelContent
     * @return Card
     */
    public function addContent(CardLabelContent $cardLabelContent)
    {
        $cardLabelContent->setLabel($this);

        $this->contents[] = $cardLabelContent;

        return $this;
    }

    /**
     * Remove CardLabelContent
     *
     * @param \Admin\SentinelleBundle\Entity\CardLabelContent $CardLabelContent
     */
    public function removeContent(CardLabelContent $CardLabelContent)
    {
        $this->contents->removeElement($CardLabelContent);
    }

    public function allowContentType()
    {
        return (in_array($this->getType(), array(50, 40, 30, 31, 32)) ? true : false);
    }

}
