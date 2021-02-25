<?php

namespace NaturaPass\MainBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation as JMS;

/**
 * Country
 *
 * @ORM\Table()
 * @ORM\Entity()
 * @JMS\ExclusionPolicy("all")
 */
class Country
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
     * @var integer
     *
     * @ORM\Column(name="name", type="string", length=255)
     *
     * @JMS\Expose
     * @JMS\Groups("SharingLess")
     */
    protected $name;

    /**
     * Get id
     *
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    public function getName()
    {
        return $this->name;
    }

    public function setName($name)
    {
        $this->name = $name;
        return $this;
    }

    /**
     * Constructor
     */
    public function __construct()
    {
    }
}
