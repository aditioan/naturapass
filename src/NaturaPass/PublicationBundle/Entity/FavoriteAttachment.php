<?php

namespace NaturaPass\PublicationBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation as JMS;
use Gedmo\Mapping\Annotation as ORMExtension;
use Admin\SentinelleBundle\Entity\CardLabel;

/**
 * Attachment
 *
 * @ORM\Table(name="`favorite_has_attachment`")
 * @ORM\Entity()
 *
 * @JMS\ExclusionPolicy("all")
 */
class FavoriteAttachment
{

    /**
     * @var integer
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     *
     * @JMS\Expose
     * @JMS\Groups({"AttachmentDetail", "AttachmentLess", "AttachmentID"})
     */
    protected $id;

    /**
     * @ORM\ManyToOne(targetEntity="Admin\SentinelleBundle\Entity\CardLabel")
     * @ORM\JoinColumn(nullable=false)
     *
     * @JMS\Expose
     * @JMS\Groups({"AttachmentLabel"})
     */
    protected $label;

    /**
     * @ORM\ManyToOne(targetEntity="NaturaPass\PublicationBundle\Entity\Favorite", inversedBy="attachments")
     * @ORM\JoinColumn(nullable=false)
     *
     * @JMS\Expose
     * @JMS\Groups({"AttachmentObservation"})
     */
    protected $favorite;

    /**
     * @var string
     *
     * @ORM\Column(name="value", type="text")
     *
     * @JMS\Expose
     * @JMS\Groups({"AttachmentDetail", "AttachmentLess"})
     */
    protected $value;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="created", type="datetime")
     * @ORMExtension\Timestampable(on="create")
     *
     * @JMS\Expose
     * @JMS\Groups({"AttachmentDetail"})
     */
    protected $created;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="updated", type="datetime")
     * @ORMExtension\Timestampable(on="update")
     *
     * @JMS\Expose
     * @JMS\Groups({"AttachmentDetail"})
     */
    protected $updated;

    /**
     * Set Value
     *
     * @param string $value
     * @return FavoriteAttachment
     */
    public function setValue($value)
    {
        $this->value = $value;

        return $this;
    }

    /**
     * Get Value
     *
     * @return string
     */
    public function getValue()
    {
        return $this->value;
    }

    /**
     * Set created
     *
     * @param \DateTime $created
     * @return FavoriteAttachment
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
     * @return FavoriteAttachment
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
     * Get Category
     *
     * @return \Admin\SentinelleBundle\Entity\CardLabel
     */
    public function getLabel()
    {
        return $this->label;
    }

    /**
     * Set Category
     *
     * @param \Admin\SentinelleBundle\Entity\CardLabel $label
     * @return FavoriteAttachment
     */
    public function setLabel(CardLabel $label)
    {
        $this->label = $label;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getFavorite()
    {
        return $this->favorite;
    }

    /**
     * @param mixed $favorite
     * @return FavoriteAttachment
     */
    public function setFavorite($favorite)
    {
        $this->favorite = $favorite;

        return $this;
    }

}
