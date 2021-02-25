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
 * Class DogType
 *
 * @ORM\Table(name="dog_type")
 * @ORM\Entity()
 * @ORM\HasLifecycleCallbacks
 * @JMS\ExclusionPolicy("all")
 */
class DogType
{


    /**
     * @var integer
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     *
     * @JMS\Expose
     * @JMS\Groups({"DogTypeDetail"})
     */
    protected $id;

    /**
     * @var string
     *
     * @ORM\Column(name="name", type="string", length=255, nullable=true)
     *
     * @JMS\Expose
     * @JMS\Groups({"DogTypeLess"})
     */
    protected $name;


    /**
     * @var \DateTime
     *
     * @ORM\Column(name="created", type="datetime")
     * @ORMExtension\Timestampable(on="create")
     *
     * @JMS\Expose
     * @JMS\Groups({"DogTypeDetail"})
     */
    protected $created;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="updated", type="datetime")
     * @ORMExtension\Timestampable(on="update")
     *
     * @JMS\Expose
     * @JMS\Groups({"DogTypeDetail"})
     */
    protected $updated;

    public function __construct()
    {
    }

    /**
     * @param \DateTime $created
     *
     * @return DogType
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
     * @return DogType
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
     * @return DogType
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
}