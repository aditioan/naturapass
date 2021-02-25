<?php
/**
 * Created by PhpStorm.
 * User: vincentvalot
 * Date: 17/07/14
 * Time: 10:06
 */

namespace NaturaPass\UserBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation as JMS;
use Gedmo\Mapping\Annotation as ORMExtension;
use Doctrine\Common\Collections\ArrayCollection;

/**
 * Class DogParameter
 *
 * @ORM\Table(name="user_has_dog")
 * @ORM\Entity()
 * @ORM\HasLifecycleCallbacks
 * @JMS\ExclusionPolicy("all")
 */
class DogParameter
{


    const SEX_MALE = 0;
    const SEX_FEMALE = 1;

    /**
     * @var integer
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     *
     * @JMS\Expose
     * @JMS\Groups({"DogParameterDetail"})
     */
    protected $id;

    /**
     * @var \NaturaPass\UserBundle\Entity\User
     *
     * @ORM\ManyToOne(targetEntity="NaturaPass\UserBundle\Entity\User", inversedBy="dogs")
     */
    protected $owner;

    /**
     * @var string
     *
     * @ORM\Column(name="name", type="string", length=255, nullable=true)
     *
     * @JMS\Expose
     * @JMS\Groups({"DogParameterLess"})
     */
    protected $name;

    /**
     * @ORM\ManyToOne(targetEntity="NaturaPass\UserBundle\Entity\DogBreed")
     *
     * @JMS\Expose
     * @JMS\Groups({"DogParameterLess"})
     */
    protected $breed;

    /**
     * @var integer
     *
     * @ORM\Column(name="sex", type="integer", nullable=true)
     *
     * @JMS\Expose
     * @JMS\Groups({"DogParameterLess"})
     */
    protected $sex;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="birthday", type="datetime", nullable=true)
     * @JMS\Expose
     * @JMS\Groups({"DogParameterLess"})
     */
    protected $birthday;

    /**
     * @ORM\ManyToOne(targetEntity="NaturaPass\UserBundle\Entity\DogType")
     *
     * @JMS\Expose
     * @JMS\Groups({"DogParameterLess"})
     */
    protected $type;

    /**
     * @var \NaturaPass\UserBundle\Entity\DogPhoto
     *
     * @ORM\OneToOne(targetEntity="NaturaPass\UserBundle\Entity\DogPhoto", cascade={"persist", "remove"}, orphanRemoval=true)
     *
     */
    protected $photo;

    /**
     * @var \Doctrine\Common\Collections\ArrayCollection|\NaturaPass\UserBundle\Entity\DogMedia[]
     *
     * @ORM\OneToMany(targetEntity="NaturaPass\UserBundle\Entity\DogMedia", mappedBy="dog", cascade={"persist", "remove"}, orphanRemoval=true, fetch="EXTRA_LAZY")
     *
     */
    protected $medias;


    /**
     * @var \DateTime
     *
     * @ORM\Column(name="created", type="datetime")
     * @ORMExtension\Timestampable(on="create")
     *
     * @JMS\Expose
     * @JMS\Groups({"DogParameterDetail"})
     */
    protected $created;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="updated", type="datetime")
     * @ORMExtension\Timestampable(on="update")
     *
     * @JMS\Expose
     * @JMS\Groups({"DogParameterDetail"})
     */
    protected $updated;

    public function __construct()
    {
        $this->medias = new ArrayCollection();
    }

    /**
     * @param \DateTime $created
     *
     * @return DogParameter
     */
    public function setCreated($created)
    {
        $this->created = $created;

        return $this;
    }

    /**
     * @return \DateTime
     */
    public function getBirthday()
    {
        return $this->birthday;
    }

    /**
     * @param \DateTime $birthday
     *
     * @return DogParameter
     */
    public function setBirthday($birthday)
    {
        $this->birthday = $birthday;

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
     * @return DogParameter
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
     * @return DogParameter
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
     * @param string $sex
     *
     * @return DogParameter
     */
    public function setSex($sex)
    {
        $this->sex = $sex;

        return $this;
    }

    /**
     * @return string
     */
    public function getSex()
    {
        return $this->sex;
    }

    /**
     * @param $media \NaturaPass\UserBundle\Entity\DogPhoto
     *
     * @return DogParameter
     */
    public function setPhoto(DogPhoto $media)
    {
        $this->photo = $media;

        return $this;
    }

    /**
     * @return \NaturaPass\UserBundle\Entity\DogPhoto
     */
    public function getPhoto()
    {
        return $this->photo;
    }

    /**
     * @return \NaturaPass\UserBundle\Entity\DogMedia[]|ArrayCollection
     */
    public function getMedias()
    {
        return $this->medias;
    }

    /**
     * @param $media \NaturaPass\UserBundle\Entity\DogMedia
     *
     * @return DogParameter
     */
    public function addMedia(DogMedia $media)
    {
        $this->medias->add($media);
        $media->setDog($this);

        return $this;
    }

    /**
     * @param $media \NaturaPass\UserBundle\Entity\DogMedia
     *
     * @return DogParameter
     */
    public function removeMedia(DogMedia $media)
    {
        $this->medias->removeElement($media);

        return $this;
    }

    /**
     * Get breed
     *
     * @return \NaturaPass\UserBundle\Entity\DogBreed
     */
    public function getBreed()
    {
        return $this->breed;
    }

    /**
     * Set breed
     *
     * @param \NaturaPass\UserBundle\Entity\DogBreed $breed
     * @return DogParameter
     */
    public function setBreed($breed)
    {
        $this->breed = $breed;
        return $this;
    }

    /**
     * Get type
     *
     * @return \NaturaPass\UserBundle\Entity\DogType
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * Set type
     *
     * @param \NaturaPass\UserBundle\Entity\DogType $type
     * @return DogParameter
     */
    public function setType($type)
    {
        $this->type = $type;
        return $this;
    }

    /**
     * Set user
     *
     * @param \NaturaPass\UserBundle\Entity\User $user
     * @return DogParameter
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