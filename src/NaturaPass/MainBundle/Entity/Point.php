<?php
/**
 * Created by PhpStorm.
 * User: Nicolas
 * Date: 22/12/2015
 * Time: 15:08
 */

namespace NaturaPass\MainBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation as JMS;
use Gedmo\Mapping\Annotation as ORMExtension;

/**
 * ShapePoint
 *
 * @ORM\Table(name="`shape_has_point`")
 * @ORM\Entity()
 */
class Point
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
     * @var \NaturaPass\MainBundle\Entity\Shape
     *
     * @ORM\ManyToOne(targetEntity="NaturaPass\MainBundle\Entity\Shape", inversedBy="points")
     */
    protected $shape;

    /**
     * @var string
     *
     * @ORM\Column(name="latitude", type="string", length=255)
     */
    protected $latitude;
    /**
     * @var string
     *
     * @ORM\Column(name="longitude", type="string", length=255)
     */
    protected $longitude;

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
     * Set latitude
     *
     * @param string $latitude
     * @return ShapePoint
     */
    public function setLatitude($latitude)
    {
        $this->latitude = $latitude;
        return $this;
    }

    /**
     * Get latitude
     *
     * @return string
     */
    public function getLatitude()
    {
        return $this->latitude;
    }

    /**
     * Set longitude
     *
     * @param string $longitude
     * @return ShapePoint
     */
    public function setLongitude($longitude)
    {
        $this->longitude = $longitude;
        return $this;
    }

    /**
     * Get longitude
     *
     * @return string
     */
    public function getLongitude()
    {
        return $this->longitude;
    }

    /**
     * Set shape
     *
     * @param \NaturaPass\MainBundle\Entity\Shape $shape
     * @return ShapePoint
     */
    public function setShape(Shape $shape = null)
    {
        $this->shape = $shape;
        return $this;
    }

    /**
     * Get shape
     *
     * @return \NaturaPass\MainBundle\Entity\Shape
     */
    public function getShape()
    {
        return $this->shape;
    }
}