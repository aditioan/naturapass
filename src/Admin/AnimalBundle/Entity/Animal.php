<?php

namespace Admin\AnimalBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation as JMS;
use Gedmo\Mapping\Annotation as Gedmo;
use Gedmo\Mapping\Annotation as ORMExtension;
use NaturaPass\MainBundle\Entity\Geolocation;
use Admin\AnimalBundle\Entity\Repository\AnimalRepository;

/**
 * Animal
 *
 * @Gedmo\Tree(type="nested")
 * @ORM\Table(name="`animal`")
 * @ORM\Entity(repositoryClass="Admin\AnimalBundle\Entity\Repository\AnimalRepository")
 *
 * @JMS\ExclusionPolicy("all")
 */
class Animal
{

    const DEFAULT_MEDIA = '/img/interface/default-observation.jpg';

    /**
     * @var integer
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     *
     * @JMS\Expose
     * @JMS\Groups({"AnimalDetail", "AnimalLess", "AnimalID"})
     */
    protected $id;

    /**
     * @var string
     *
     * @ORM\Column(name="name_fr", type="string", length=255)
     *
     * @JMS\Expose
     * @JMS\Groups({"AnimalDetail", "AnimalLess"})
     */
    protected $name_fr;

    /**
     * @var string
     *
     * @ORM\Column(name="name_en", type="string", length=255, nullable=true)
     *
     * @JMS\Expose
     * @JMS\Groups({"AnimalDetail", "AnimalLess"})
     */
    protected $name_en;

    /**
     * @Gedmo\TreeLeft
     * @ORM\Column(name="lft", type="integer")
     *
     * @JMS\Expose
     * @JMS\Groups({"CategoryTree"})
     */
    private $lft;

    /**
     * @Gedmo\TreeRight
     * @ORM\Column(name="rgt", type="integer")
     *
     * @JMS\Expose
     * @JMS\Groups({"CategoryTree"})
     */
    protected $rgt;

    /**
     * @Gedmo\TreeParent
     * @ORM\ManyToOne(targetEntity="Animal", inversedBy="children")
     * @ORM\JoinColumn(name="parent_id", referencedColumnName="id", onDelete="CASCADE")
     */
    private $parent;

    /**
     * @Gedmo\TreeRoot
     * @ORM\Column(type="integer", nullable=true)
     */
    private $root;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="created", type="datetime")
     * @ORMExtension\Timestampable(on="create")
     *
     * @JMS\Expose
     * @JMS\Groups({"AnimalDetail"})
     */
    protected $created;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="updated", type="datetime")
     * @ORMExtension\Timestampable(on="update")
     *
     * @JMS\Expose
     * @JMS\Groups({"AnimalDetail"})
     */
    protected $updated;

    /**
     * @Gedmo\TreeLevel
     * @ORM\Column(name="lvl", type="integer")
     *
     * @JMS\Expose
     * @JMS\Groups({"CategoryTree"})
     */
    private $lvl;

    /**
     * @ORM\OneToMany(targetEntity="Animal", mappedBy="parent")
     */
    private $children;

    /**
     * @ORM\ManyToOne(targetEntity="Admin\AnimalBundle\Entity\AnimalMedia")
     * @ORM\JoinColumn(onDelete="SET NULL")
     *
     * @JMS\Expose
     * @JMS\Groups({"AnimalDetail"})
     */
    protected $media;

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
     * @param string $name_fr
     * @return Animal
     */
    public function setName_fr($name_fr)
    {
        $this->name_fr = $name_fr;

        return $this;
    }

    /**
     * Set name
     *
     * @param string $name_en
     * @return Animal
     */
    public function setName_en($name_en)
    {
        $this->name_en = $name_en;

        return $this;
    }

    /**
     * Get name_fr
     *
     * @return string
     */
    public function getName_fr()
    {
        return $this->name_fr;
    }

    /**
     * Get name_en
     *
     * @return string
     */
    public function getName_en()
    {
        return $this->name_en;
    }

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->children = new ArrayCollection();
    }

    /**
     * Set created
     *
     * @param \DateTime $created
     * @return Animal
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
     * @return Animal
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
     * Get lft
     *
     * @return integer
     */
    function getLft()
    {
        return $this->lft;
    }

    /**
     * Get rgt
     *
     * @return integer
     */
    function getRgt()
    {

        return $this->rgt;
    }

    /**
     * Get parent
     *
     * @return integer
     */
    function getParent()
    {
        return $this->parent;
    }

    /**
     * Get root
     *
     * @return integer
     */
    function getRoot()
    {
        return $this->root;
    }

    /**
     * Get lvl
     *
     * @return integer
     */
    function getLvl()
    {
        return $this->lvl;
    }

    /**
     * Get children
     *
     * @return integer
     */
    function getChildren()
    {
        return $this->children;
    }

    /**
     * Set lft
     *
     * @param integer $lft
     * @return Animal
     */
    function setLft($lft)
    {
        $this->lft = $lft;

        return $this;
    }

    /**
     * Set rgt
     *
     * @param integer $rgt
     * @return Animal
     */
    function setRgt($rgt)
    {
        $this->rgt = $rgt;

        return $this;
    }

    /**
     * Set parent
     *
     * @param integer $parent
     * @return Animal
     */
    function setParent($parent)
    {
        $this->parent = $parent;

        return $this;
    }

    /**
     * Set root
     *
     * @param integer $root
     * @return Animal
     */
    function setRoot($root)
    {
        $this->root = $root;

        return $this;
    }

    /**
     * Set lvl
     *
     * @param integer $lvl
     * @return Animal
     */
    function setLvl($lvl)
    {
        $this->lvl = $lvl;

        return $this;
    }

    /**
     * Set children
     *
     * @param integer $children
     * @return Animal
     */
    function setChildren($children)
    {
        $this->children = $children;

        return $this;
    }

    /**
     * Get media
     *
     * @return \Admin\AnimalBundle\Entity\AnimalMedia
     */
    public function getMedia()
    {
        return $this->media;
    }

    /**
     * Set media
     *
     * @param \Admin\AnimalBundle\Entity\AnimalMedia $media
     * @return \Admin\AnimalBundle\Entity\Animal
     */
    public function setMedia(AnimalMedia $media)
    {
        $this->media = $media;
        return $this;
    }

    /**
     * Return a picto of the branch
     * @return AnimalMedia
     */
    public function getPicto()
    {
        if (!is_null($this->getMedia())) {
            return $this->getMedia();
        } else if (!is_null($this->getParent())) {
            return $this->getParent()->getPicto();
        }
        return null;
    }

}
