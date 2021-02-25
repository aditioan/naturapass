<?php

namespace NaturaPass\GraphBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation as JMS;
use Gedmo\Mapping\Annotation as ORMExtension;

/**
 * Graph
 *
 * @ORM\Table()
 * @ORM\Entity()
 *
 * @JMS\ExclusionPolicy("all")
 */

class Graph {
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
     * @ORM\Column(name="degree", type="integer")
     *
     * @JMS\Expose
     * @JMS\Groups({"GraphDetail"})
     */
    protected $degree = 1;

    /**
     * @ORM\OneToMany(targetEntity="NaturaPass\GraphBundle\Entity\Edge", mappedBy="graph", cascade={"persist", "remove"})
     *
     * @JMS\Expose
     * @JMS\Groups({"GraphDetail", "GraphLess"})
     */
    protected $edges;

    /**
     * Constructor
     */
    public function __construct() {
        $this->edges = new ArrayCollection();
    }

    /**
     * Get id
     *
     * @return integer
     */
    public function getId() {
        return $this->id;
    }

    /**
     * Get degree
     *
     * @return integer
     */
    public function getDegree() {
        return $this->degree;
    }

    /**
     * Set name
     *
     * @param int $degree
     * @return Graph
     */
    public function setDegree($degree) {
        $this->degree = $degree;
        return $this;
    }

    /**
     * Vérifie qu'un noeud n'existe pas déjà
     *
     * @param Edge $exist
     * @return int
     */
    public function isEdgeExisting(Edge $exist) {
        return count($this->edges->filter(function($element) use ($exist) {
                return
                    (($element->getFrom() == $exist->getFrom() && $element->getTo() == $exist->getTo())
                    ||
                    ($element->getFrom() == $exist->getTo() && $element->getTo() == $exist->getFrom()))
                    && $element->getType() == $exist->getType() && $element->getObjectID() == $exist->getObjectID();
        }));
    }

    /**
     * Get edges
     *
     * @return \Doctrine\Common\Collections\ArrayCollection|\NaturaPass\GraphBundle\Entity\Edge[]
     */
    public function getEdges() {
        return $this->edges;
    }

    /**
     * Add Edge
     *
     * @param \NaturaPass\GraphBundle\Entity\Edge $edge
     * @return Graph
     */
    public function addEdge(Edge $edge) {
        $this->edges[] = $edge;

        return $this;
    }

    /**
     * Remove Edge
     *
     * @param \NaturaPass\GraphBundle\Entity\Edge $edge
     */
    public function removeEdge(Edge $edge) {
        $this->edges->removeElement($edge);
    }

}
