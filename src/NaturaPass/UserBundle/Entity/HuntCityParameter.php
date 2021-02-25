<?php
/**
 * Created by PhpStorm.
 * User: vincentvalot
 * Date: 17/07/14
 * Time: 10:06
 */

namespace NaturaPass\UserBundle\Entity;

use Admin\SentinelleBundle\Entity\Locality;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as ORMExtension;

/**
 * Class HuntCityParameter
 *
 * @ORM\Table(name="user_hunt_city")
 * @ORM\Entity()
 * @ORM\HasLifecycleCallbacks
 */
class HuntCityParameter
{

    /**
     * @var \NaturaPass\UserBundle\Entity\User
     *
     * @ORM\ManyToOne(targetEntity="NaturaPass\UserBundle\Entity\User", inversedBy="huntcities")
     * @ORM\Id
     */
    protected $owner;

    /**
     * @var \Admin\SentinelleBundle\Entity\Locality
     *
     * @ORM\ManyToOne(targetEntity="Admin\SentinelleBundle\Entity\Locality")
     * @ORM\Id
     *
     */
    protected $city;


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
    }

    /**
     * @param \DateTime $created
     *
     * @return HuntCityParameter
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
     * @param \DateTime $updated
     *
     * @return HuntCityParameter
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
     * @param Locality $city
     *
     * @return HuntCityParameter
     */
    public function setCity(Locality $city)
    {
        $this->city = $city;

        return $this;
    }

    /**
     * @return Locality
     */
    public function getCity()
    {
        return $this->city;
    }

    /**
     * Set user
     *
     * @param \NaturaPass\UserBundle\Entity\User $user
     * @return HuntCityParameter
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