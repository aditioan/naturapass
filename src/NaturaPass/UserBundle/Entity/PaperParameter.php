<?php
/**
 * Created by PhpStorm.
 * User: vincentvalot
 * Date: 17/07/14
 * Time: 10:06
 */

namespace NaturaPass\UserBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as ORMExtension;
use Doctrine\Common\Collections\ArrayCollection;
use NaturaPass\MediaBundle\Entity\Media;

/**
 * Class PaperParameter
 *
 * @ORM\Table(name="user_has_paper")
 * @ORM\Entity()
 * @ORM\HasLifecycleCallbacks
 */
class PaperParameter
{

    const TYPE_ALL = 0;
    const ACCESS_MEDIA = 1;
    const ACCESS_MEDIA_NAME = 2;
    const ACCESS_MEDIA_TEXT = 3;

    const DELETABLE = 1;
    const NO_DELETABLE = 0;

    /**
     * @var integer
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     *
     */
    protected $id;

    /**
     * @var \NaturaPass\UserBundle\Entity\User
     *
     * @ORM\ManyToOne(targetEntity="NaturaPass\UserBundle\Entity\User", inversedBy="papers")
     */
    protected $owner;

    /**
     * @var string
     *
     * @ORM\Column(name="name", type="string", length=255, nullable=true)
     *
     */
    protected $name;

    /**
     * @var string
     *
     * @ORM\Column(name="text", type="text", nullable=true)
     *
     */
    protected $text;

    /**
     * @var integer
     *
     * @ORM\Column(name="type", type="integer")
     *
     */
    protected $type = self::TYPE_ALL;

    /**
     * @var integer
     *
     * @ORM\Column(name="deletable", type="integer")
     *
     */
    protected $deletable = self::DELETABLE;

    /**
     * @var \Doctrine\Common\Collections\ArrayCollection|\NaturaPass\UserBundle\Entity\PaperMedia[]
     *
     * @ORM\OneToMany(targetEntity="NaturaPass\UserBundle\Entity\PaperMedia", mappedBy="paper", cascade={"persist", "remove"}, orphanRemoval=true, fetch="EXTRA_LAZY")
     * @ORM\JoinColumn(nullable=true)
     *
     */
    protected $medias;


    /**
     * @var \DateTime
     *
     * @ORM\Column(name="created", type="datetime")
     * @ORMExtension\Timestampable(on="create")
     *
     */
    protected $created;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="updated", type="datetime")
     * @ORMExtension\Timestampable(on="update")
     *
     */
    protected $updated;

    public function __construct()
    {
        $this->medias = new ArrayCollection();
    }

    /**
     * @param \DateTime $created
     *
     * @return PaperParameter
     */
    public function setCreated($created)
    {
        $this->created = $created;

        return $this;
    }

    /**
     * @return \DateTime
     */
    public function getCreated()
    {
        return $this->created;
    }

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param \DateTime $updated
     *
     * @return PaperParameter
     */
    public function setUpdated($updated)
    {
        $this->updated = $updated;

        return $this;
    }

    /**
     * @return \DateTime
     */
    public function getUpdated()
    {
        return $this->updated;
    }

    /**
     * @param string $name
     *
     * @return PaperParameter
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param string $text
     *
     * @return PaperParameter
     */
    public function setText($text)
    {
        $this->text = $text;

        return $this;
    }

    /**
     * @return string
     */
    public function getText()
    {
        return $this->text;
    }


    /**
     * @return \NaturaPass\UserBundle\Entity\PaperMedia[]|ArrayCollection
     */
    public function getMedias()
    {
        return $this->medias;
    }

    /**
     * @param $media \NaturaPass\UserBundle\Entity\PaperMedia
     *
     * @return PaperParameter
     */
    public function addMedia(PaperMedia $media)
    {
        $this->medias->add($media);
        $media->setPaper($this);

        return $this;
    }

    /**
     * @param $media \NaturaPass\UserBundle\Entity\PaperMedia
     *
     * @return PaperParameter
     */
    public function removeMedia(PaperMedia $media)
    {
        $this->medias->remove($media);

        return $this;
    }

    /**
     * Set type
     *
     * @param integer $type
     * @return PaperParameter
     */
    public function setType($type)
    {
        $this->type = $type;

        return $this;
    }

    /**
     * Get type
     *
     * @return integer
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * Set deletable
     *
     * @param integer $deletable
     * @return PaperParameter
     */
    public function setDeletable($deletable)
    {
        $this->deletable = $deletable;

        return $this;
    }

    /**
     * Get type
     *
     * @return integer
     */
    public function getDeletable()
    {
        return $this->deletable;
    }


    /**
     * Set user
     *
     * @param \NaturaPass\UserBundle\Entity\User $user
     * @return PaperParameter
     */
    public function setOwner(User $user)
    {
        $this->owner = $user;

        return $this;
    }

    /**
     * Get user
     *
     * @return \NaturaPass\UserBundle\Entity\User
     */
    public function getOwner()
    {
        return $this->owner;
    }
}