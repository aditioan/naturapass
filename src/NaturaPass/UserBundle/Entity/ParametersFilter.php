<?php

namespace NaturaPass\UserBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation as JMS;
use Gedmo\Mapping\Annotation as ORMExtension;

/**
 * Parameters
 *
 * @ORM\Table(name="parameters_has_filter")
 * @ORM\Entity()
 * @ORM\HasLifecycleCallbacks
 * @JMS\ExclusionPolicy("all")
 */
class ParametersFilter {

    /**
     * @var integer
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     *
     * @JMS\Expose
     * @JMS\Groups({"UserParameters"})
     */
    protected $id;

    /**
     * @var Parameters
     *
     * @ORM\ManyToOne(targetEntity="NaturaPass\UserBundle\Entity\Parameters", inversedBy="filters")
     */
    protected $parameters;

    /**
     * @ORM\Column(name="groupFilter", type="integer", nullable=true, options={"default": null})
     */
    protected $groupFilter;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="created", type="datetime")
     * @ORMExtension\Timestampable(on="create")
     *
     * @JMS\Expose
     * @JMS\Groups({"LoungeDetail"})
     */
    protected $created;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="updated", type="datetime")
     * @ORMExtension\Timestampable(on="update")
     *
     * @JMS\Expose
     * @JMS\Groups({"LoungeDetail"})
     */
    protected $updated;

    /**
     * Set created
     *
     * @param \DateTime $created
     * @return ParametersEmail
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
     * @return ParametersEmail
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
     * Set parameters
     *
     * @param \NaturaPass\UserBundle\Entity\Parameters $parameters
     * @return ParametersEmail
     */
    public function setParameters(\NaturaPass\UserBundle\Entity\Parameters $parameters) {
        $this->parameters = $parameters;

        return $this;
    }

    /**
     * Get parameters
     *
     * @return \NaturaPass\UserBundle\Entity\Parameters
     */
    public function getParameters() {
        return $this->parameters;
    }

    /**
     * @param integer $groupFilter
     *
     * @return \NaturaPass\UserBundle\Entity\Parameters
     */
    public function setGroupFilter($groupFilter) {
        $this->groupFilter = $groupFilter;
        return $this;
    }

    /**
     * @return integer
     */
    public function getGroupFilter() {
        return $this->groupFilter;
    }

}
