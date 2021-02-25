<?php

namespace Admin\SentinelleBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation as JMS;
use Gedmo\Mapping\Annotation as ORMExtension;

/**
 * Card
 *
 * @ORM\Table(name="`card")
 * @ORM\Entity()
 *
 * @JMS\ExclusionPolicy("all")
 */
class Card
{

    const VISIBLE_ON = 1;
    const VISIBLE_OFF = 0;

    /**
     * @var integer
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     *
     * @JMS\Expose
     * @JMS\Groups({"CardDetail", "CardLess", "CardID"})
     */
    protected $id;

    /**
     * @var string
     *
     * @ORM\Column(name="name", type="string", length=255)
     *
     * @JMS\Expose
     * @JMS\Groups({"CardDetail", "CardLess"})
     */
    protected $name;

    /**
     * @var integer
     *
     * @ORM\Column(name="visible", type="integer")
     *
     * @JMS\Expose
     * @JMS\Groups({"CardDetail", "CardLess"})
     */
    protected $visible = self::VISIBLE_ON;

    /**
     * @var integer
     *
     * @ORM\Column(name="animal", type="integer")
     *
     * @JMS\Expose
     * @JMS\Groups({"CardDetail", "CardLess"})
     */
    protected $animal = 0;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="created", type="datetime")
     * @ORMExtension\Timestampable(on="create")
     *
     * @JMS\Expose
     * @JMS\Groups({"CardDetail"})
     */
    protected $created;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="updated", type="datetime")
     * @ORMExtension\Timestampable(on="update")
     *
     * @JMS\Expose
     * @JMS\Groups({"CardDetail"})
     */
    protected $updated;

    /**
     * @var CardCategoryZone[] $zone_categories
     *
     * @ORM\OneToMany(targetEntity="Admin\SentinelleBundle\Entity\CardCategoryZone", mappedBy="card", cascade={"persist", "remove"}, fetch="EXTRA_LAZY", orphanRemoval=true)
     * @ORM\JoinColumn(nullable=true)
     *
     * @JMS\Expose
     * @JMS\Groups({"CardDetail"})
     */
    protected $zone_categories;

    /**
     * @var Category[] $categories
     *
     * @ORM\OneToMany(targetEntity="Admin\SentinelleBundle\Entity\Category", mappedBy="card")
     * @ORM\JoinColumn(nullable=true)
     *
     * @JMS\Expose
     * @JMS\Groups({"CardDetail"})
     */
    protected $categories;

    /**
     * @var ArrayCollection|CardLabel[] $labels
     *
     * @ORM\OneToMany(targetEntity="Admin\SentinelleBundle\Entity\CardLabel", mappedBy="card", cascade={"persist", "remove"}, fetch="EXTRA_LAZY", orphanRemoval=true)
     * @ORM\JoinColumn(onDelete="CASCADE", nullable=false)
     * @ORM\OrderBy({"order" = "ASC"})
     *
     * @JMS\Expose
     * @JMS\Groups({"CardDetail"})
     */
    protected $labels;

    public function __construct()
    {
        $this->zones_categories = new ArrayCollection();
        $this->labels = new ArrayCollection();
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
     * Get CardCategoryZone
     *
     * @param array
     *
     * @return ArrayCollection
     */
    public function getZone_categories()
    {
        return $this->zone_categories;
    }

    /**
     * Add Card
     *
     * @param \Admin\SentinelleBundle\Entity\CardCategoryZone $cardCategoryZone
     * @return Card
     */
    public function addZone_category(CardCategoryZone $cardCategoryZone)
    {
        $this->zone_categories[] = $cardCategoryZone;

        return $this;
    }

    /**
     * Remove Category
     *
     * @param \Admin\SentinelleBundle\Entity\CardCategoryZone $cardCategoryZone
     */
    public function removeZone_category(CardCategoryZone $cardCategoryZone)
    {
        $this->zone_categories->removeElement($cardCategoryZone);
    }

    /**
     * Get CardLabel
     *
     * @return ArrayCollection|CardLabel[]
     */
    public function getLabels()
    {
        return $this->labels;
    }

    /**
     * Get CardLabel
     *
     * @return ArrayCollection|CardLabel[]
     */
    public function getLabelsVisible()
    {
//        return $this->labels;
        return $this->labels->filter(function ($element) {
            return $element->getVisible() == 1;
        });
    }

    /**
     * Set CardLabel
     *
     * @param CardLabel[] $labels
     *
     * @return Card
     */
    public function setLabels($labels)
    {
        foreach ($labels as $label) {
            $label->setCard($this);
        }

        $this->labels = $labels;

        return $this;
    }

    /**
     * Add CardLabel
     *
     * @param \Admin\SentinelleBundle\Entity\CardLabel $cardLabel
     * @return Card
     */
    public function addLabel(CardLabel $cardLabel)
    {
        $cardLabel->setCard($this);

        $this->labels[] = $cardLabel;

        return $this;
    }

    /**
     * Remove CardLabel
     *
     * @param \Admin\SentinelleBundle\Entity\CardLabel $cardLabel
     */
    public function removeLabel(CardLabel $cardLabel)
    {
        $this->labels->removeElement($cardLabel);
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
     * @return Card
     */
    function setVisible($visible)
    {
        $this->visible = $visible;

        return $this;
    }

    /**
     * Get animal
     *
     * @return integer
     */
    function getAnimal()
    {
        return $this->animal;
    }

    /**
     * Set animal
     *
     * @param integer $animal
     * @return Card
     */
    function setAnimal($animal)
    {
        $this->animal = $animal;

        return $this;
    }

    public function isCardToEpos()
    {
        return in_array($this->getId(), array(6));
    }

}
