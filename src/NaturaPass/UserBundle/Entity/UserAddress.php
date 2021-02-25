<?php
/**
 * Created by PhpStorm.
 * User: vincentvalot
 * Date: 01/09/14
 * Time: 16:39
 */

namespace NaturaPass\UserBundle\Entity;


use NaturaPass\MainBundle\Entity\AbstractGeolocation;

use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation as JMS;

/**
 * UserAddress
 *
 * @ORM\Table(name="user_has_address")
 * @ORM\Entity
 * @JMS\ExclusionPolicy("all")
 */
class UserAddress extends AbstractGeolocation
{

    /**
     * @var integer
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     *
     * @JMS\Expose
     * @JMS\Groups({"GeolocationDetail"})
     */
    protected $id;

    /**
     * @var \NaturaPass\UserBundle\Entity\User
     *
     * @ORM\ManyToOne(targetEntity="NaturaPass\UserBundle\Entity\User", inversedBy="addresses")
     */
    protected $owner;

    /**
     * @var string
     *
     * @ORM\Column(name="title", type="string", length=255)
     */
    protected $title;

    /**
     * @var boolean
     *
     * @ORM\Column(name="favorite", type="boolean", options={"default": false})
     */
    protected $favorite = false;

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
     * @param \NaturaPass\UserBundle\Entity\User $owner
     *
     * @return UserAddress
     */
    public function setOwner($owner)
    {
        $this->owner = $owner;

        return $this;
    }

    /**
     * @return \NaturaPass\UserBundle\Entity\User
     */
    public function getOwner()
    {
        return $this->owner;
    }


    /**
     * @param boolean $favorite
     *
     * @return UserAddress
     */
    public function setFavorite($favorite)
    {
        $this->favorite = $favorite;

        return $this;
    }

    /**
     * @return boolean
     */
    public function isFavorite()
    {
        return $this->favorite;
    }

    /**
     * @param string $title
     *
     * @return UserAddress
     */
    public function setTitle($title)
    {
        $this->title = $title;

        return $this;
    }

    /**
     * @return string
     */
    public function getTitle()
    {
        return $this->title;
    }
}