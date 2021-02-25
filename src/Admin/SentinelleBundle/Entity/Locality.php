<?php

namespace Admin\SentinelleBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation as JMS;
use Gedmo\Mapping\Annotation as ORMExtension;
use FOS\RestBundle\Util\Codes;

/**
 * Locality
 *
 * @ORM\Table(name="`locality`")
 * @ORM\Entity()
 *
 * @JMS\ExclusionPolicy("all")
 */
class Locality
{

    /**
     * @var integer
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     *
     * @JMS\Expose
     * @JMS\Groups({"LocalityDetail", "LocalityLess", "ZoneID"})
     */
    protected $id;

    /**
     * @var string
     *
     * @ORM\Column(name="name", type="string", length=255, nullable=true)
     *
     * @JMS\Expose
     * @JMS\Groups({"LocalityDetail", "LocalityLess"})
     */
    protected $name;

    /**
     * @var string
     *
     * @ORM\Column(name="administrative_area_level_2", type="string", length=255, nullable=true)
     *
     * @JMS\Expose
     * @JMS\Groups({"LocalityDetail", "LocalityLess"})
     */
    protected $administrative_area_level_2;

    /**
     * @var string
     *
     * @ORM\Column(name="administrative_area_level_1", type="string", length=255, nullable=true)
     *
     * @JMS\Expose
     * @JMS\Groups({"LocalityDetail", "LocalityLess"})
     */
    protected $administrative_area_level_1;

    /**
     * @var string
     *
     * @ORM\Column(name="country", type="string", length=255, nullable=true)
     *
     * @JMS\Expose
     * @JMS\Groups({"LocalityDetail", "LocalityLess"})
     */
    protected $country;

    /**
     * @var string
     *
     * @ORM\Column(name="postal_code", type="string", length=255, nullable=true)
     *
     * @JMS\Expose
     * @JMS\Groups({"LocalityDetail", "LocalityLess"})
     */
    protected $postal_code;

    /**
     * @var string
     *
     * @ORM\Column(name="insee", type="string", length=255, nullable=true)
     *
     * @JMS\Expose
     * @JMS\Groups({"LocalityDetail", "LocalityLess"})
     */
    protected $insee;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="created", type="datetime")
     * @ORMExtension\Timestampable(on="create")
     *
     * @JMS\Expose
     * @JMS\Groups({"LocalityDetail"})
     */
    protected $created;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="updated", type="datetime")
     * @ORMExtension\Timestampable(on="update")
     *
     * @JMS\Expose
     * @JMS\Groups({"LocalityDetail"})
     */
    protected $updated;

    /**
     * @ORM\ManyToMany(targetEntity="Admin\SentinelleBundle\Entity\Receiver", inversedBy="localities", cascade={"persist"})
     * @ORM\JoinTable(name="receiver_localities")
     */
    protected $receivers;

    /**
     * @var \Doctrine\Common\Collections\ArrayCollection|\Admin\SentinelleBundle\Entity\Zone[]
     *
     * @ORM\ManyToOne(targetEntity="Admin\SentinelleBundle\Entity\Zone", inversedBy="localities")
     * @ORM\JoinColumn(nullable=true, onDelete="SET NULL")
     *
     * @JMS\Expose
     * @JMS\Groups({"LocalityDetail"})
     */
    protected $zone;

    /**
     * @ORM\OneToMany(targetEntity="NaturaPass\PublicationBundle\Entity\Publication", mappedBy="locality")
     */
    protected $publications;

    /**
     * @ORM\OneToMany(targetEntity="NaturaPass\ObservationBundle\Entity\ObservationReceiver", mappedBy="locality")
     */
    protected $receiverObservations;

    public function __construct()
    {
        $this->publications = new ArrayCollection();
        $this->receiverObservations = new ArrayCollection();
        $this->receivers = new ArrayCollection();
    }

    /**
     * @return mixed
     */
    public function getPublications()
    {
        return $this->publications;
    }

    /**
     * @return mixed
     */
    public function getReceiverObservations()
    {
        return $this->receiverObservations;
    }

    /**
     * @param mixed $publications
     * @return Locality
     */
    public function setPublications($publications)
    {
        $this->publications = $publications;

        return $this;
    }

    /**
     * @param mixed $receivers
     * @return Locality
     */
    public function setReceiverObservations($receivers)
    {
        $this->receiverObservations = $receivers;

        return $this;
    }

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
     * @return string
     */
    public function getAdministrativeAreaLevel2()
    {
        return $this->administrative_area_level_2;
    }

    /**
     * @param string $administrative_area_level_2
     * @return Locality
     */
    public function setAdministrativeAreaLevel2($administrative_area_level_2)
    {
        $this->administrative_area_level_2 = $administrative_area_level_2;

        return $this;
    }

    /**
     * @return string
     */
    public function getAdministrativeAreaLevel1()
    {
        return $this->administrative_area_level_1;
    }

    /**
     * @param string $administrative_area_level_1
     * @return Locality
     */
    public function setAdministrativeAreaLevel1($administrative_area_level_1)
    {
        $this->administrative_area_level_1 = $administrative_area_level_1;

        return $this;
    }

    /**
     * @return string
     */
    public function getCountry()
    {
        return $this->country;
    }

    /**
     * @param string $country
     * @return Locality
     */
    public function setCountry($country)
    {
        $this->country = $country;

        return $this;
    }

    /**
     * @return string
     */
    public function getPostal_code()
    {
        return $this->postal_code;
    }

    /**
     * @param string $postal_code
     * @return Locality
     */
    public function setPostal_code($postal_code)
    {
        $this->postal_code = $postal_code;

        return $this;
    }

    /**
     * @return string
     */
    public function getInsee()
    {
        return $this->insee;
    }

    /**
     * @param string $insee
     * @return Locality
     */
    public function setInsee($insee)
    {
        $this->insee = $insee;

        return $this;
    }

    /**
     * Set name
     *
     * @param string $name
     * @return Locality
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * Get name
     *
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
     * @return Locality
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
     * Set updated
     *
     * @param \DateTime $updated
     * @return Locality
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
     * Get Receiver
     *
     * @param array
     * @param boolean
     *
     * @return ArrayCollection|Receiver[]
     */
    public function getReceivers()
    {
        return $this->receivers;
    }

    /**
     * Add Receiver
     *
     * @param \Admin\SentinelleBundle\Entity\Receiver $receiver
     * @return Receiver
     */
    public function addReceiver(Receiver $receiver)
    {
        $this->receivers[] = $receiver;

        return $this;
    }

    /**
     * Remove Receiver
     *
     * @param \Admin\SentinelleBundle\Entity\Receiver $receiver
     */
    public function removeReceiver(Receiver $receiver)
    {
        $this->receivers->removeElement($receiver);
    }

    public function __toString()
    {
        return $this->postal_code . ' - ' . $this->name . ', ' . $this->country;
    }

    /**
     * Get Zone
     *
     * @return \Admin\SentinelleBundle\Entity\Locality
     */
    public function getZone()
    {
        return $this->zone;
    }

    /**
     * Add Zone
     *
     * @param \Admin\SentinelleBundle\Entity\Zone $zone
     * @return Locality
     */
    public function setZone(Zone $zone)
    {
        $this->zone = $zone;

        return $this;
    }

    public function getCurlLocality()
    {
        $url = 'https://maps.googleapis.com/maps/api/geocode/json?components=locality:' . $this->getName() . ''
            . '&components=country:' . $this->getCountry() . ''
            . '&components=administrative_area_level_2:' . $this->getAdministrativeAreaLevel2() . ''
            . '&components=administrative_area_level_1:' . $this->getAdministrativeAreaLevel1() . ''
            . '&components=postal_code:' . $this->getPostal_code();


        $curl = curl_init();
        curl_setopt_array(
            $curl, array(
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_FOLLOWLOCATION => false,
                CURLOPT_SSL_VERIFYPEER => false,
                CURLOPT_SSLVERSION => 1,
                CURLOPT_HEADER => false,
                CURLOPT_CONNECTTIMEOUT => 2,
                CURLOPT_TIMEOUT => 10,
                CURLOPT_REFERER => 'http://www.naturapass.com',
                CURLOPT_URL => $url
            )
        );

        $raw = curl_exec($curl);

        if ($raw && curl_getinfo($curl, CURLINFO_HTTP_CODE) == Codes::HTTP_OK) {
            $response = json_decode($raw, true);

            if ($response['status'] == 'OK') {
                return $response['results'][0];
            }
        }
        return null;
    }

}
