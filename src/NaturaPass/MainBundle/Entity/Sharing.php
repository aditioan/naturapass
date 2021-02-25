<?php

namespace NaturaPass\MainBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation as JMS;
use NaturaPass\UserBundle\Entity\User;

/**
 * Sharing
 *
 * @ORM\Table()
 * @ORM\Entity()
 * @JMS\ExclusionPolicy("all")
 */
class Sharing
{

    const USER = 0;
    const FRIENDS = 1;
    const KNOWING = 2;
    const NATURAPASS = 3;
    const ALL = 4;
    const USERFRIENDS = 5;
    const ONLYFRIENDS = 6;

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
     * @ORM\Column(name="share", type="integer")
     *
     * @JMS\Expose
     * @JMS\Groups("SharingLess")
     */
    protected $share = self::NATURAPASS;

    /**
     * @var \Doctrine\Common\Collections\ArrayCollection|\NaturaPass\UserBundle\Entity\User[]
     *
     * @ORM\ManyToMany(targetEntity="NaturaPass\UserBundle\Entity\User", fetch="EXTRA_LAZY");
     * @ORM\JoinTable(name="sharing_without_somebody")
     *
     * @JMS\Expose
     * @JMS\Groups("SharingWithouts")
     */
    protected $withouts;

    /**
     * Get id
     *
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    public function getShare()
    {
        return $this->share;
    }

    public function setShare($share)
    {
        $this->share = $share;
        return $this;
    }

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->withouts = new ArrayCollection();
    }

    /**
     * Add without
     *
     * @param \NaturaPass\UserBundle\Entity\User $without
     *
     * @return Sharing
     */
    public function addWithout(User $without)
    {
        $this->withouts[] = $without;

        return $this;
    }

    /**
     * Remove without
     *
     * @param \NaturaPass\UserBundle\Entity\User $without
     */
    public function removeWithout(User $without)
    {
        $this->withouts->removeElement($without);
    }

    /**
     * Remove withouts
     *
     * @return $this
     */
    public function removeAllWithout()
    {
        $this->withouts->clear();

        return $this;
    }

    /**
     * Get withouts
     *
     * @return \Doctrine\Common\Collections\ArrayCollection|\NaturaPass\UserBundle\Entity\User[]
     */
    public function getWithouts()
    {
        return $this->withouts;
    }

    /**
     * Set withouts
     *
     * @param \Doctrine\Common\Collections\ArrayCollection|\NaturaPass\UserBundle\Entity\User[]
     *
     * @return $this
     */
    public function setWithouts($withouts)
    {
        $this->withouts = $withouts;

        return $this;
    }

}
