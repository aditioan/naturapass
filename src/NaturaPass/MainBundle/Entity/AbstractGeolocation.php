<?php
/**
 * Created by PhpStorm.
 * User: vincentvalot
 * Date: 02/09/14
 * Time: 10:37
 */

namespace NaturaPass\MainBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation as JMS;
use Gedmo\Mapping\Annotation as ORMExtension;

/**
 * Class AbstractGeolocation
 * @package NaturaPass\MainBundle\Entity
 *
 * @ORM\MappedSuperclass
 */
abstract class AbstractGeolocation {

    const EARTH_RADIUS = 3963.19;

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
     * @var string
     *
     * @ORM\Column(name="latitude", type="string", length=255)
     *
     * @JMS\Expose
     * @JMS\Groups({"GeolocationDetail", "GeolocationLess"})
     */
    protected $latitude;

    /**
     * @var string
     *
     * @ORM\Column(name="longitude", type="string", length=255)
     *
     * @JMS\Expose
     * @JMS\Groups({"GeolocationDetail", "GeolocationLess"})
     */
    protected $longitude;

    /**
     * @var string
     *
     * @ORM\Column(name="altitude", type="string", length=255, nullable=true)
     *
     * @JMS\Expose
     * @JMS\Groups({"GeolocationDetail", "GeolocationLess"})
     */
    protected $altitude;

    /**
     * @var string
     *
     * @ORM\Column(name="address", type="text", nullable=true)
     *
     * @JMS\Expose
     * @JMS\Groups({"GeolocationDetail", "GeolocationLess"})
     */
    protected $address;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="created", type="datetime")
     * @ORMExtension\Timestampable(on="create")
     *
     * @JMS\Expose
     * @JMS\Groups({"GeolocationDetail"})
     */
    protected $created;

    /**
     * Retourne un ensemble de longitude/latitude pour avoir une couverture en cercle depuis un point donné
     *
     * @param $bearing
     * @param $distance float Distance en km voulue
     *
     * @return array
     */
    protected function getRadiusCoverage($bearing, $distance) {
        $rLat = deg2rad($this->latitude);
        $rLng = deg2rad($this->longitude);
        $rBearing = deg2rad($bearing);
        $rAngDist = $distance / self::EARTH_RADIUS;

        $rLatB = asin(sin($rLat) * cos($rAngDist) +
            cos($rLat) * sin($rAngDist) * cos($rBearing));

        $rLonB = $rLng + atan2(sin($rBearing) * sin($rAngDist) * cos($rLat),
                cos($rAngDist) - sin($rLat) * sin($rLatB));

        return array("lat" => rad2deg($rLatB), "lng" => rad2deg($rLonB));
    }

    /**
     * Retourne les limites de vue d'un point avec une distance donnée en kilomètres
     *
     * @param $distance
     * @return array
     */
    public function getBounds($distance) {
        return array(
            "N" => $this->getRadiusCoverage(0, $distance),
            "E" => $this->getRadiusCoverage(90, $distance),
            "S" => $this->getRadiusCoverage(180, $distance),
            "W" => $this->getRadiusCoverage(270, $distance)
        );
    }

    /**
     * Calcul la distance entre deux coordonnées géographiques
     *
     * @param AbstractGeolocation $geolocation
     * @return int
     */
    public function getDistanceWith(AbstractGeolocation $geolocation) {
        $rHalfDeltaLat = deg2rad(($geolocation->getLatitude() - $this->latitude) / 2);
        $rHalfDeltaLon = deg2rad(($geolocation->getLongitude() - $this->longitude) / 2);

        return 2 * self::EARTH_RADIUS * asin(sqrt(pow(sin($rHalfDeltaLat), 2) +
                cos(deg2rad($this->latitude)) * cos(deg2rad($geolocation->getLatitude())) * pow(sin($rHalfDeltaLon), 2)));
    }

    /**
     * Test si des coordonnées géographiques se trouvent dans la zone définie par deux autres points
     *
     * @param AbstractGeolocation $northEast
     * @param AbstractGeolocation $southWest
     *
     * @return boolean
     */
    public function inArea(AbstractGeolocation $northEast, AbstractGeolocation $southWest) {
        return $this->latitude <= $northEast->getLatitude() && $this->latitude >= $southWest->getLatitude() && $this->longitude >= $southWest->getLongitude() && $this->longitude <= $northEast->getLongitude();
    }

    /**
     * Set latitude
     *
     * @param string $latitude
     * @return $this
     */
    public function setLatitude($latitude) {
        $this->latitude = $latitude;

        return $this;
    }

    /**
     * Get latitude
     *
     * @return string
     */
    public function getLatitude() {
        return $this->latitude;
    }

    /**
     * Set address
     *
     * @param string $address
     *
     * @return $this
     */
    public function setAddress($address) {
        $this->address = $address;

        return $this;
    }

    /**
     * Get address
     *
     * @return string
     */
    public function getAddress() {
        return $this->address;
    }

    /**
     * Set longitude
     *
     * @param string $longitude
     * @return $this
     */
    public function setLongitude($longitude) {
        $this->longitude = $longitude;

        return $this;
    }

    /**
     * Get longitude
     *
     * @return string
     */
    public function getLongitude() {
        return $this->longitude;
    }

    /**
     * Set created
     *
     * @param \DateTime $created
     * @return $this
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
     * Set altitude
     *
     * @param string $altitude
     * @return $this
     */
    public function setAltitude($altitude) {
        $this->altitude = $altitude;

        return $this;
    }

    /**
     * Get altitude
     *
     * @return string
     */
    public function getAltitude() {
        return $this->altitude;
    }

} 