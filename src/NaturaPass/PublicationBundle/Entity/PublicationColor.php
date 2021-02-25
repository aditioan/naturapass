<?php

namespace NaturaPass\PublicationBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation as JMS;

/**
 * PublicationColor
 *
 * @ORM\Table()
 * @ORM\Entity()
 * @JMS\ExclusionPolicy("all")
 */
class PublicationColor
{


    /**
     * @var integer
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @var string
     *
     * @ORM\Column(name="color", type="string", length=25)
     *
     * @JMS\Expose
     * @JMS\Groups({"PublicationColorLess"})
     */
    protected $color;

    /**
     * @var string
     *
     * @ORM\Column(name="name", type="string", length=256)
     *
     * @JMS\Expose
     * @JMS\Groups({"PublicationColorLess"})
     */
    protected $name;

    /**
     * @var boolean
     *
     * @ORM\Column(name="active", type="boolean")
     */
    protected $active;

    /**
     * @var string
     *
     * @ORM\Column(name="background", type="string", length=25)
     *
     * @JMS\Expose
     * @JMS\Groups({"PublicationColorLess"})
     */
    protected $background;

    /**
     * @ORM\OneToMany(targetEntity="NaturaPass\PublicationBundle\Entity\Publication", cascade={"persist", "remove"}, orphanRemoval=true, mappedBy="publicationcolor", fetch="EXTRA_LAZY")
     *
     * @JMS\Expose
     * @JMS\Groups({"PublicationLess"})
     */
    protected $publicationcolor;

    /**
     * @ORM\OneToMany(targetEntity="NaturaPass\PublicationBundle\Entity\Favorite", cascade={"persist", "remove"}, orphanRemoval=true, mappedBy="publicationcolor", fetch="EXTRA_LAZY")
     *
     * @JMS\Expose
     * @JMS\Groups({"PublicationLess"})
     */
    protected $favorite;

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
     * Set color
     *
     * @param string $color
     *
     * @return PublicationColor
     */
    public function setColor($color)
    {
        $this->color = $color;

        return $this;
    }

    /**
     * Get color
     *
     * @return string
     */
    public function getColor()
    {
        return $this->color;
    }

    /**
     * Set name
     *
     * @param string $name
     *
     * @return PublicationColor
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
     * Constructor
     */
    public function __construct()
    {
        $this->publication = new \Doctrine\Common\Collections\ArrayCollection();
    }

    /**
     * Add publication
     *
     * @param \NaturaPass\PublicationBundle\Entity\Publication $publication
     *
     * @return PublicationColor
     */
    public function addPublication(\NaturaPass\PublicationBundle\Entity\Publication $publication)
    {
        $this->publication[] = $publication;

        return $this;
    }

    /**
     * Remove publication
     *
     * @param \NaturaPass\PublicationBundle\Entity\Publication $publication
     */
    public function removePublication(\NaturaPass\PublicationBundle\Entity\Publication $publication)
    {
        $this->publication->removeElement($publication);
    }

    /**
     * Get publication
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getPublication()
    {
        return $this->publication;
    }

    /**
     * Add publicationcolor
     *
     * @param \NaturaPass\PublicationBundle\Entity\Publication $publicationcolor
     *
     * @return PublicationColor
     */
    public function addPublicationcolor(\NaturaPass\PublicationBundle\Entity\Publication $publicationcolor)
    {
        $this->publicationcolor[] = $publicationcolor;

        return $this;
    }

    /**
     * Remove publicationcolor
     *
     * @param \NaturaPass\PublicationBundle\Entity\Publication $publicationcolor
     */
    public function removePublicationcolor(\NaturaPass\PublicationBundle\Entity\Publication $publicationcolor)
    {
        $this->publicationcolor->removeElement($publicationcolor);
    }

    /**
     * Get publicationcolor
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getPublicationcolor()
    {
        return $this->publicationcolor;
    }

    /**
     * Add publicationcolor
     *
     * @param \NaturaPass\PublicationBundle\Entity\Publication $publicationcolor
     *
     * @return PublicationColor
     */
    public function addFavorite(Favorite $favorite)
    {
        $this->favorite[] = $favorite;

        return $this;
    }

    /**
     * Remove favorite
     *
     * @param \NaturaPass\PublicationBundle\Entity\Favorite $favorite
     */
    public function removeFavorite(Favorite $favorite)
    {
        $this->favorite->removeElement($favorite);
    }

    /**
     * Get favorite
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getFavorite()
    {
        return $this->favorite;
    }

    /**
     * @return boolean
     */
    public function isActive()
    {
        return $this->active;
    }

    /**
     * @param boolean $active
     */
    public function setActive($active)
    {
        $this->active = $active;
    }

    /**
     * @return string
     */
    public function getBackground()
    {
        return $this->background;
    }

    /**
     * @param string $background
     */
    public function setBackground($background)
    {
        $this->background = $background;
    }


}
