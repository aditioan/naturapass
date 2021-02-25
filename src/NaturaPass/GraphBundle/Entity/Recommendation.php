<?php
/**
 * Created by PhpStorm.
 * User: vincentvalot
 * Date: 22/07/14
 * Time: 09:57
 */


namespace NaturaPass\GraphBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation as JMS;
use Gedmo\Mapping\Annotation as ORMExtension;
use NaturaPass\UserBundle\Entity\User;

/**
 * Edge
 *
 * @ORM\Table(name="user_has_recommendation")
 * @ORM\Entity(repositoryClass="NaturaPass\GraphBundle\Repository\RecommendationRepository")
 *
 * @JMS\ExclusionPolicy("all")
 */
class Recommendation {

    const DEFAULT_LOSS = 0.01;

    const ACTION_NO = 0;
    const ACTION_VIEWED = 1;
    const ACTION_USED = 2;
    const ACTION_REMOVED = 3;

    /**
     * @var \NaturaPass\UserBundle\Entity\User
     *
     * @ORM\ManyToOne(targetEntity="NaturaPass\UserBundle\Entity\User")
     * @ORM\Id
     *
     * @JMS\Expose
     * @JMS\Groups({"RecommendationDetail", "RecommendationLess"})
     */
    protected $owner;

    /**
     * @var \NaturaPass\UserBundle\Entity\User
     * @ORM\Id
     *
     * @ORM\ManyToOne(targetEntity="NaturaPass\UserBundle\Entity\User")
     *
     * @JMS\Expose
     * @JMS\Groups({"RecommendationDetail", "RecommendationLess"})
     */
    protected $target;

    /**
     * @var float
     *
     * @ORM\Column(name="pertinence", type="float")
     *
     * @JMS\Expose
     * @JMS\Groups({"RecommendationDetail"})
     */
    protected $pertinence;

    /**
     * @var integer
     *
     * @ORM\Column(name="action", type="integer")
     *
     * @JMS\Expose
     * @JMS\Groups({"RecommendationDetail"})
     */
    protected $action = self::ACTION_NO;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="created", type="datetime")
     * @ORMExtension\Timestampable(on="create")
     *
     * @JMS\Expose
     * @JMS\Groups({"RecommendationDetail"})
     */
    protected $created;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="updated", type="datetime")
     * @ORMExtension\Timestampable(on="update")
     *
     * @JMS\Expose
     * @JMS\Groups({"RecommendationDetail"})
     */
    protected $updated;

    /**
     * @param \DateTime $created
     *
     * @return Recommendation
     */
    public function setCreated(\DateTime $created)
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
     * @param User $owner
     *
     * @return Recommendation
     */
    public function setOwner(User $owner)
    {
        $this->owner = $owner;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getOwner()
    {
        return $this->owner;
    }

    /**
     * @param float $pertinence
     *
     * @return Recommendation
     */
    public function setPertinence($pertinence)
    {
        $this->pertinence = $pertinence;

        return $this;
    }

    /**
     * @return float
     */
    public function getPertinence()
    {
        return $this->pertinence;
    }

    /**
     * @param User $target
     *
     * @return Recommendation
     */
    public function setTarget(User $target)
    {
        $this->target = $target;

        return $this;
    }

    /**
     * @return \NaturaPass\UserBundle\Entity\User
     */
    public function getTarget()
    {
        return $this->target;
    }

    /**
     * @param \DateTime $updated
     *
     * @return Recommendation
     */
    public function setUpdated(\DateTime $updated)
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
     * @param int $action
     *
     * @return Recommendation
     */
    public function setAction($action)
    {
        $this->action = $action;
        return $this;
    }

    /**
     * @return int
     */
    public function getAction()
    {
        return $this->action;
    }

} 