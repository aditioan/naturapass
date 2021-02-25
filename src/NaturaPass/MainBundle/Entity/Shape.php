<?php
/**
 * Created by PhpStorm.
 * User: Nicolas
 * Date: 22/12/2015
 * Time: 15:05
 */

namespace NaturaPass\MainBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation as JMS;
use Gedmo\Mapping\Annotation as ORMExtension;
use NaturaPass\GroupBundle\Entity\Group;
use NaturaPass\LoungeBundle\Entity\Lounge;
use NaturaPass\UserBundle\Entity\User;

/**
 * Shape
 *
 * @ORM\Table(name="`shape`")
 * @ORM\Entity()
 */
class Shape
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
     * @var string
     *
     * @ORM\Column(name="title", type="string", length=255, nullable=true)
     */
    protected $title;

    /**
     * @var string
     *
     * @ORM\Column(name="description", type="text", nullable=true)
     */
    protected $description;

    /**
     * @var string
     *
     * @ORM\Column(name="type", type="string", length=255)
     */
    protected $type;
    /**
     * @var string
     *
     * @ORM\Column(name="data", type="text")
     */
    private $data;

    /**
     * @var \NaturaPass\UserBundle\Entity\User
     *
     * @ORM\ManyToOne(targetEntity="NaturaPass\UserBundle\Entity\User", inversedBy="shapes")
     */
    protected $owner;

    /**
     * @ORM\OneToOne(targetEntity="NaturaPass\MainBundle\Entity\Sharing", cascade={"persist", "remove"})
     */
    protected $sharing;

    /**
     * @var \NaturaPass\MainBundle\Entity\Point
     *
     * @ORM\OneToMany(targetEntity="NaturaPass\MainBundle\Entity\Point", cascade={"persist", "remove"}, mappedBy="shape")
     */
    protected $points;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="updated", type="datetime")
     * @ORMExtension\Timestampable(on="update")
     */
    private $updated;
    /**
     * @var \DateTime
     *
     * @ORM\Column(name="created", type="datetime")
     * @ORMExtension\Timestampable(on="create")
     */
    private $created;

    /**
     * @var string
     *
     * @ORM\Column(name="ne_latitude", type="string", length=255, nullable=true)
     *
     * @JMS\Expose
     * @JMS\Groups({"GeolocationDetail", "GeolocationLess"})
     */
    protected $ne_latitude;

    /**
     * @var string
     *
     * @ORM\Column(name="ne_longitude", type="string", length=255, nullable=true)
     *
     * @JMS\Expose
     * @JMS\Groups({"GeolocationDetail", "GeolocationLess"})
     */
    protected $ne_longitude;

    /**
     * @var string
     *
     * @ORM\Column(name="sw_latitude", type="string", length=255, nullable=true)
     *
     * @JMS\Expose
     * @JMS\Groups({"GeolocationDetail", "GeolocationLess"})
     */
    protected $sw_latitude;

    /**
     * @var string
     *
     * @ORM\Column(name="sw_longitude", type="string", length=255, nullable=true)
     *
     * @JMS\Expose
     * @JMS\Groups({"GeolocationDetail", "GeolocationLess"})
     */
    protected $sw_longitude;

    /**
     * @var string
     *
     * @ORM\Column(name="lat_center", type="string", length=255, nullable=true)
     *
     * @JMS\Expose
     * @JMS\Groups({"GeolocationDetail", "GeolocationLess"})
     */
    protected $lat_center;

    /**
     * @var string
     *
     * @ORM\Column(name="lon_center", type="string", length=255, nullable=true)
     *
     * @JMS\Expose
     * @JMS\Groups({"GeolocationDetail", "GeolocationLess"})
     */
    protected $lon_center;

    /**
     * @var ArrayCollection
     *
     * @ORM\ManyToMany(targetEntity="NaturaPass\GroupBundle\Entity\Group", inversedBy="shapes", fetch="EXTRA_LAZY");
     * @ORM\JoinTable(name="group_has_shape")
     *
     * @JMS\Expose
     * @JMS\Groups({"ShapeGroup"})
     */
    protected $groups;

    /**
     * @var ArrayCollection
     *
     * @ORM\ManyToMany(targetEntity="NaturaPass\LoungeBundle\Entity\Lounge", inversedBy="shapes", fetch="EXTRA_LAZY");
     * @ORM\JoinTable(name="lounge_has_shape")
     *
     * @JMS\Expose
     * @JMS\Groups({"ShapeLounge"})
     */
    protected $hunts;

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
     * Set type
     *
     * @param string $type
     * @return Shape
     */
    public function setType($type)
    {
        $this->type = $type;
        return $this;
    }

    /**
     * Get type
     *
     * @return string
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * Set data
     *
     * @param mixed $data
     * @return Shape
     */
    public function setData($data)
    {
        $this->data = is_array($data) || is_object($data) ? json_encode($data) : $data;
        return $this;
    }

    /**
     * Get data
     *
     * @return object
     */
    public function getData()
    {
        return json_decode($this->data, true);
    }

    /**
     * Set updated
     *
     * @param \DateTime $updated
     * @return Shape
     */
    public function setUpdated($updated)
    {
        $this->updated = $updated;
        return $this;
    }

    /**
     * Get updated
     *
     * @return \DateTime
     */
    public function getUpdated()
    {
        return $this->updated;
    }

    /**
     * Set created
     *
     * @param \DateTime $created
     * @return Shape
     */
    public function setCreated($created)
    {
        $this->created = $created;
        return $this;
    }

    /**
     * Get created
     *
     * @return \DateTime
     */
    public function getCreated()
    {
        return $this->created;
    }

    /**
     * Set title
     *
     * @param string $title
     * @return Shape
     */
    public function setTitle($title)
    {
        $this->title = $title;
        return $this;
    }

    /**
     * Get title
     *
     * @return string
     */
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * Set description
     *
     * @param string $description
     * @return Shape
     */
    public function setDescription($description)
    {
        $this->description = $description;
        return $this;
    }

    /**
     * Get description
     *
     * @return string
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * Set sharing
     *
     * @param \NaturaPass\MainBundle\Entity\Sharing $sharing
     * @return Shape
     */
    public function setSharing(Sharing $sharing = null)
    {
        $this->sharing = $sharing;
        return $this;
    }

    /**
     * Get sharing
     *
     * @return \NaturaPass\MainBundle\Entity\Sharing
     */
    public function getSharing()
    {
        return $this->sharing;
    }

    /**
     * Set owner
     *
     * @param \NaturaPass\UserBundle\Entity\User $owner
     * @return Shape
     */
    public function setOwner(User $owner = null)
    {
        $this->owner = $owner;
        return $this;
    }

    /**
     * Get owner
     *
     * @return \NaturaPass\UserBundle\Entity\User
     */
    public function getOwner()
    {
        return $this->owner;
    }

    /**
     * @return string
     */
    public function getNeLatitude()
    {
        return $this->ne_latitude;
    }

    /**
     * @param string $ne_latitude
     * @return Shape
     */
    public function setNeLatitude($ne_latitude)
    {
        $this->ne_latitude = $ne_latitude;
        return $this;
    }

    /**
     * @return string
     */
    public function getNeLongitude()
    {
        return $this->ne_longitude;
    }

    /**
     * @param string $ne_longitude
     * @return Shape
     */
    public function setNeLongitude($ne_longitude)
    {
        $this->ne_longitude = $ne_longitude;
        return $this;
    }

    /**
     * @return string
     */
    public function getSwLatitude()
    {
        return $this->sw_latitude;
    }

    /**
     * @param string $sw_latitude
     * @return Shape
     */
    public function setSwLatitude($sw_latitude)
    {
        $this->sw_latitude = $sw_latitude;
        return $this;
    }

    /**
     * @return string
     */
    public function getSwLongitude()
    {
        return $this->sw_longitude;
    }

    /**
     * @param string $sw_longitude
     * @return Shape
     */
    public function setSwLongitude($sw_longitude)
    {
        $this->sw_longitude = $sw_longitude;
        return $this;
    }

    /**
     * @return string
     */
    public function getLatCenter()
    {
        return $this->lat_center;
    }

    /**
     * @param string $lat_center
     * @return Shape
     */
    public function setLatCenter($lat_center)
    {
        $this->lat_center = $lat_center;
        return $this;
    }

    /**
     * @return string
     */
    public function getLonCenter()
    {
        return $this->lon_center;
    }

    /**
     * @param string $lon_center
     * @return Shape
     */
    public function setLonCenter($lon_center)
    {
        $this->lon_center = $lon_center;
        return $this;
    }

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->points = new ArrayCollection();
        $this->groups = new ArrayCollection();
        $this->hunts = new ArrayCollection();
    }

    /**
     * Add points
     *
     * @param \NaturaPass\MainBundle\Entity\Point $points
     * @return Shape
     */
    public function addPoint(Point $points)
    {
        $this->points[] = $points;
        return $this;
    }

    /**
     * Remove points
     *
     * @param \NaturaPass\MainBundle\Entity\Point $points
     */
    public function removePoint(Point $points)
    {
        $this->points->removeElement($points);
    }

    /**
     * Get points
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getPoints()
    {
        return $this->points;
    }

    public function getLastUpdated(User $connected)
    {
        $updated = $this->getUpdated();
        if (is_object($this->getOwner())) {
            $friendUpdate = $this->getOwner()->friendshipWithUpdate($connected);
            if ($friendUpdate > $updated) {
                $updated = $friendUpdate;
            }
        }
        return $updated;
    }


    /**
     * Add group
     *
     * @param \NaturaPass\GroupBundle\Entity\Group $group
     * @return Group
     */
    public function addGroup(Group $group)
    {
        $this->groups[] = $group;

        return $this;
    }

    /**
     * Remove group
     *
     * @param \NaturaPass\GroupBundle\Entity\Group $group
     */
    public function removeGroup(Group $group)
    {
        $this->groups->removeElement($group);
    }

    /**
     * Remove groups
     *
     */
    public function removeAllGroups()
    {
        $this->groups = new ArrayCollection();
    }

    /**
     * Get group
     *
     * @return ArrayCollection|\NaturaPass\GroupBundle\Entity\Group[]
     */
    public function getGroups()
    {
        return $this->groups;
    }

    /**
     * Add hunt
     *
     * @param \NaturaPass\LoungeBundle\Entity\Lounge $hunt
     * @return Lounge
     */
    public function addHunt(Lounge $hunt)
    {
        $this->hunts[] = $hunt;

        return $this;
    }

    /**
     * Remove hunt
     *
     * @param \NaturaPass\LoungeBundle\Entity\Lounge $hunt
     */
    public function removeHunt(Lounge $hunt)
    {
        $this->hunts->removeElement($hunt);
    }

    /**
     * Remove hunts
     *
     */
    public function removeAllHunts()
    {
        $this->hunts = new ArrayCollection();
    }

    /**
     * Get hunts
     *
     * @return ArrayCollection|\NaturaPass\LoungeBundle\Entity\Lounge[]
     */
    public function getHunts()
    {
        return $this->hunts;
    }

    /**
     * Get hunts
     *
     * @return ArrayCollection|\NaturaPass\LoungeBundle\Entity\Lounge[]
     */
    public function hasHunt(Lounge $hunt)
    {
        return $this->hunts->filter(function (Lounge $lounge) use ($hunt) {
            return $lounge->getId() == $hunt->getId();
        })->first();
    }


    public function calculPoints()
    {
        $shapeParams = $this->getData();
        if ($this->getType() == "circle" || $this->getType() == "rectangle") {
            $nb = 0;
            foreach ($shapeParams["bounds"] as $point) {
                if ($nb == 0) {
                    $point1 = $point;
                } else {
                    $point2 = $point;
                }
                $nb++;
            }
            $this->setSwLatitude($point1[0]);
            $this->setSwLongitude($point1[1]);
            $this->setNeLatitude($point2[0]);
            $this->setNeLongitude($point2[1]);
        } else if ($this->getType() == "polygon" || $this->getType() == "polyline") {
            $latMin = $latMax = $lngMin = $lngMax = false;
            foreach ($shapeParams["paths"] as $point) {
                if ($point[0] < $latMin || !$latMin) {
                    $latMin = $point[0];
                }
                if ($point[0] > $latMax || !$latMax) {
                    $latMax = $point[0];
                }
                if ($point[1] < $lngMin || !$lngMin) {
                    $lngMin = $point[1];
                }
                if ($point[1] > $lngMax || !$lngMax) {
                    $lngMax = $point[1];
                }
            }
            $this->setSwLatitude($latMin);
            $this->setSwLongitude($lngMin);
            $this->setNeLatitude($latMax);
            $this->setNeLongitude($lngMax);
        }
    }

    /*
     * calculate the centroid of a polygon
     * return a 2-element list: array($x,$y)
     */
    public function getCentre()
    {
        $shapeParams = $this->getData();
        if ($this->getType() == "circle") {
            $this->setLatCenter($shapeParams["center"][0]);
            $this->setLonCenter($shapeParams["center"][1]);
        } else if ($this->getType() == "rectangle") {
            $swlat = $this->getSwLatitude();
            $swLon = $this->getSwLongitude();
            $neLat = $this->getNeLatitude();
            $neLon = $this->getNeLongitude();
            $this->setLatCenter($neLat + ($swlat - $neLat) / 2);
            $this->setLonCenter($neLon + ($swLon - $neLon) / 2);
        } else if ($this->getType() == "polygon" || $this->getType() == "polyline") {
            $PI = 22 / 7;
            $X = 0;
            $Y = 0;
            $Z = 0;
            foreach ($shapeParams["paths"] as $point) {
                $lat1 = $point[0];
                $lon1 = $point[1];
                $lat1 = $lat1 * $PI / 180;
                $lon1 = $lon1 * $PI / 180;
                $X += cos($lat1) * cos($lon1);
                $Y += cos($lat1) * sin($lon1);
                $Z += sin($lat1);
            }
            $Lon = atan2($Y, $X);
            $Hyp = sqrt($X * $X + $Y * $Y);
            $Lat = atan2($Z, $Hyp);
            $Lat = $Lat * 180 / $PI;
            $Lon = $Lon * 180 / $PI;
            $this->setLatCenter($Lat);
            $this->setLonCenter($Lon);
        }
    }
}