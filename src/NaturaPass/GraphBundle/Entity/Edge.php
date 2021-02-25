<?php

namespace NaturaPass\GraphBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation as JMS;
use Gedmo\Mapping\Annotation as ORMExtension;
use NaturaPass\UserBundle\Entity\User;

/**
 * Edge
 *
 * @ORM\Table(name="graph_has_edge")
 * @ORM\Entity(repositoryClass="NaturaPass\GraphBundle\Repository\EdgeRepository")
 *
 * @JMS\ExclusionPolicy("all")
 */
class Edge {
    const LOCATION = 100;
    const LOCATION_DEPARTMENT = 101;
    const LOCATION_HOMETOWN = 102;
    const LOCATION_TOWN = 103;

    const FRIENDSHIP = 200;
    const FRIENDSHIP_FRIEND = 201;
    const FRIENDSHIP_MUTUAL_FRIEND = 202;

    const GROUP = 300;
    const GROUP_SUBSCRIBER = 301;

    const PUBLICATION = 400;
    const PUBLICATION_COMMENTED = 401;
    const PUBLICATION_LIKED = 402;
    const PUBLICATION_UNLIKED = 403;

    const LOUNGE = 500;
    const LOUNGE_SUBSCRIBER = 501;

    /**
     * @var integer
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     *
     * @JMS\Expose
     * @JMS\Groups({"GraphDetail", "GraphLess"})
     */
    protected $id;

    /**
     * @var integer
     *
     * @ORM\ManyToOne(targetEntity="NaturaPass\GraphBundle\Entity\Graph", inversedBy="edges")
     *
     * @JMS\Expose
     * @JMS\Groups({"GraphDetail"})
     */
    protected $graph;

    /**
     * @ORM\ManyToOne(targetEntity="NaturaPass\UserBundle\Entity\User")
     *
     * @JMS\Expose
     * @JMS\Groups({"GraphDetail", "GraphLess"})
     */
    protected $from;

    /**
     * @ORM\ManyToOne(targetEntity="NaturaPass\UserBundle\Entity\User")
     *
     * @JMS\Expose
     * @JMS\Groups({"GraphDetail", "GraphLess"})
     */
    protected $to;

    /**
     * @var integer
     *
     * @ORM\Column(name="type", type="integer")
     *
     * @JMS\Expose
     * @JMS\Groups({"GraphDetail", "GraphLess"})
     */
    protected $type;

    /**
     * Décris un objet visé par la relation
     *
     * @var string
     *
     * @ORM\Column(name="object_id", type="text", nullable=true)
     *
     * @JMS\Expose
     * @JMS\Groups({"GraphDetail", "GraphLess"})
     */
    protected $objectID;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="created", type="datetime")
     * @ORMExtension\Timestampable(on="create")
     *
     * @JMS\Expose
     * @JMS\Groups({"GraphDetail"})
     */
    protected $created;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="updated", type="datetime")
     * @ORMExtension\Timestampable(on="update")
     *
     * @JMS\Expose
     * @JMS\Groups({"GraphDetail"})
     */
    protected $updated;

    /**
     * Get id
     *
     * @return integer
     */
    public function getId() {
        return $this->id;
    }

    /**
     * Get id
     *
     * @return integer
     */
    public function getGraph() {
        return $this->graph;
    }

    /**
     * Set Graph
     *
     * @param Graph $graph
     * @return Edge
     */
    public function setGraph($graph) {
        $this->graph = $graph;
        return $this;
    }

    /**
     * Get type
     *
     * @return integer
     */
    public function getType() {
        return $this->type;
    }

    /**
     * Set type
     *
     * @param integer $type
     * @return Edge
     */
    public function setType($type) {
        $this->type = $type;

        return $this;
    }

    /**
     * Get from
     *
     * @return \NaturaPass\UserBundle\Entity\User
     */
    public function getFrom() {
        return $this->from;
    }

    /**
     * Get from
     *
     * @return \NaturaPass\UserBundle\Entity\User
     */
    public function getTo() {
        return $this->to;
    }

    /**
     * Set from
     *
     * @param \NaturaPass\UserBundle\Entity\User $from
     * @return Edge
     */
    public function setFrom(User $from) {
        $this->from = $from;
        return $this;
    }

    /**
     * Set to
     *
     * @param \NaturaPass\UserBundle\Entity\User $to
     * @return Edge
     */
    public function setTo(User $to) {
        $this->to = $to;
        return $this;
    }

    /**
     * Set created
     *
     * @param \DateTime $created
     * @return Edge
     */
    public function setCreated($created) {
        $this->created = $created;

        return $this;
    }

    /**
     * Get created
     *
     * @return \DateTime
     */
    public function getCreated() {
        return $this->created;
    }

    /**
     * Set updated
     *
     * @param \DateTime $updated
     * @return Edge
     */
    public function setUpdated($updated) {
        $this->updated = $updated;

        return $this;
    }

    /**
     * Get updated
     *
     * @return \DateTime
     */
    public function getUpdated() {
        return $this->updated;
    }

    /**
     * @param mixed $objectID
     *
     * @return Edge
     */
    public function setObjectID($objectID)
    {
        $this->objectID = $objectID;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getObjectID()
    {
        return $this->objectID;
    }
}
