<?php
/**
 * Created by PhpStorm.
 * User: vincentvalot
 * Date: 21/07/14
 * Time: 10:46
 */

namespace NaturaPass\GraphBundle\Entity;


use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as ORMExtension;
use JMS\Serializer\Annotation as JMS;

/**
 * Class Relevance
 * @package NaturaPass\GraphBundle\Entity
 *
 * @ORM\Table(name="graph_has_pertinence")
 * @ORM\Entity()
 *
 * @JMS\ExclusionPolicy("all")
 */
class Pertinence {

    /**
     * @var integer
     *
     * @ORM\Column(name="type", type="integer")
     * @ORM\Id
     *
     * @JMS\Expose
     * @JMS\Groups({"PertinenceDetail"})
     */
    protected $type;

    /**
     * @var float
     *
     * @ORM\Column(name="value", type="float")
     *
     * @JMS\Expose
     * @JMS\Groups({"PertinenceDetail"})
     */
    protected $value = 1.0;

    /**
     * @var float
     *
     *
     * @ORM\Column(name="loss", type="float", options={"default"=0.01})
     *
     * @JMS\Expose
     * @JMS\Groups({"PertinenceDetail"})
     */
    protected $loss = 0.01;

    /**
     * @var string
     *
     * @ORM\Column(name="name", type="string", length=255)
     *
     * @JMS\Expose
     * @JMS\Groups({"PertinenceDetail"})
     */
    protected $name;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="created", type="datetime")
     * @ORMExtension\Timestampable(on="create")
     */
    protected $created;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="updated", type="datetime")
     * @ORMExtension\Timestampable(on="update")
     */
    protected $updated;

    /**
     * @param float $value
     */
    public function setValue($value)
    {
        $this->value = $value;
    }

    /**
     * @return float
     */
    public function getValue()
    {
        return $this->value;
    }

    /**
     * @param mixed $type
     */
    public function setType($type)
    {
        $this->type = $type;
    }

    /**
     * @return mixed
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * @param float $loss
     *
     * @return Pertinence
     */
    public function setLoss($loss)
    {
        $this->loss = $loss;

        return $this;
    }

    /**
     * @return float
     */
    public function getLoss()
    {
        return $this->loss;
    }

    /**
     * @param string $name
     *
     * @return $this
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
     * Set created
     *
     * @param \DateTime $created
     * @return Pertinence
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
     * @return Pertinence
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
} 