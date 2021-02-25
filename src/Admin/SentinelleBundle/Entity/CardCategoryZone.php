<?php

namespace Admin\SentinelleBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation as JMS;
use Gedmo\Mapping\Annotation as ORMExtension;

/**
 * CardCategoryZone
 *
 * @ORM\Table(name="`card_category_by_zone`")
 * @ORM\Entity()
 *
 * @JMS\ExclusionPolicy("all")
 */
class CardCategoryZone
{

    const VISIBLE_ON = 1;
    const VISIBLE_OFF = 0;

    /**
     * @ORM\Id
     * @ORM\ManyToOne(targetEntity="Admin\SentinelleBundle\Entity\Card", inversedBy="zone_categories")
     * @ORM\JoinColumn(onDelete="Cascade")
     *
     * @JMS\Expose
     * @JMS\Groups({"CardCategoryZoneDetail", "CardCategoryZoneLess", "CardCategoryZoneID"})
     */
    protected $card;

    /**
     * @ORM\Id
     * @ORM\ManyToOne(targetEntity="Admin\SentinelleBundle\Entity\Zone", inversedBy="cards")
     * @ORM\JoinColumn(onDelete="Cascade")
     *
     * @JMS\Expose
     * @JMS\Groups({"CardCategoryZoneLess"})
     */
    protected $zone;

    /**
     * @ORM\Id
     * @ORM\ManyToOne(targetEntity="Admin\SentinelleBundle\Entity\Category", inversedBy="cardszone")
     * @ORM\JoinColumn(onDelete="Cascade")
     *
     * @JMS\Expose
     * @JMS\Groups({"CardCategoryZoneLess"})
     */
    protected $category;

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
     * @var \DateTime
     *
     * @ORM\Column(name="created", type="datetime")
     * @ORMExtension\Timestampable(on="create")
     *
     * @JMS\Expose
     * @JMS\Groups({"CardCategoryZoneDetail"})
     */
    protected $created;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="updated", type="datetime")
     * @ORMExtension\Timestampable(on="update")
     *
     * @JMS\Expose
     * @JMS\Groups({"CardCategoryZoneDetail"})
     */
    protected $updated;

    /**
     * Set created
     *
     * @param \DateTime $created
     * @return CardCategoryZone
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
     * @return CardCategoryZone
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
     * Set CardCategory
     *
     * @param \Admin\SentinelleBundle\Entity\Card $card
     * @return CardCategoryZone
     */
    public function setCard(Card $card)
    {
        $this->card = $card;

        return $this;
    }

    /**
     * Get Card
     *
     * @return \Admin\SentinelleBundle\Entity\Card
     */
    public function getCard()
    {
        return $this->card;
    }

    /**
     * Set Zone
     *
     * @param \Admin\SentinelleBundle\Entity\Zone $zone
     * @return CardCategoryZone
     */
    public function setZone(Zone $zone)
    {
        $this->zone = $zone;

        return $this;
    }

    /**
     * Get Zone
     *
     * @return \Admin\SentinelleBundle\Entity\Zone
     */
    public function getZone()
    {
        return $this->zone;
    }

    /**
     * Set Category
     *
     * @param \Admin\SentinelleBundle\Entity\Category $category
     * @return CardCategoryZone
     */
    public function setCategory(Category $category)
    {
        $this->category = $category;

        return $this;
    }

    /**
     * Get Category
     *
     * @return \Admin\SentinelleBundle\Entity\Category
     */
    public function getCategory()
    {
        return $this->category;
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
     * @return Category
     */
    function setVisible($visible)
    {
        $this->visible = $visible;

        return $this;
    }

}
