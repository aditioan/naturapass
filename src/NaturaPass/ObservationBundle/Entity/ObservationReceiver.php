<?php

namespace NaturaPass\ObservationBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation as JMS;
use Gedmo\Mapping\Annotation as ORMExtension;
use Admin\SentinelleBundle\Entity\Receiver;
use NaturaPass\PublicationBundle\Entity\PublicationMedia;
use NaturaPass\UserBundle\Entity\User;

/**
 * ObservationReceiver
 *
 * @ORM\Table(name="`receiver_has_observation`")
 * @ORM\Entity()
 *
 * @JMS\ExclusionPolicy("all")
 */
class ObservationReceiver
{

    /**
     * @var integer
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     *
     * @JMS\Expose
     * @JMS\Groups({"ObservationReceiverDetail", "ObservationReceiverLess", "ObservationReceiverID"})
     */
    protected $id;

    /**
     * @ORM\ManyToOne(targetEntity="Admin\SentinelleBundle\Entity\Receiver", inversedBy="observations")
     *
     * @JMS\Expose
     * @JMS\Groups({"ObservationReceiverDetail", "ObservationReceiverLess", "ObservationReceiverID"})
     */
    protected $receiver;

    /**
     * @ORM\ManyToOne(targetEntity="NaturaPass\UserBundle\Entity\User")
     * @ORM\JoinColumn(nullable=true,onDelete="SET NULL")
     *
     * @JMS\Expose
     * @JMS\Groups({"ObservationReceiverDetail"})
     */
    protected $user;

    /**
     * @var integer
     *
     * @ORM\Column(name="fullname", type="string", length=255)
     *
     * @JMS\Expose
     * @JMS\Groups({"ObservationCategoryDetail"})
     */
    protected $fullname;

    /**
     * @var integer
     *
     * @ORM\Column(name="email", type="string", length=255)
     *
     * @JMS\Expose
     * @JMS\Groups({"ObservationCategoryDetail"})
     */
    protected $email;

    /**
     * @ORM\ManyToOne(targetEntity="Admin\SentinelleBundle\Entity\Locality", inversedBy="receiverObservations")
     * @ORM\JoinColumn(nullable=true, onDelete="SET NULL")
     *
     * @JMS\Expose
     * @JMS\Groups({"ObservationCategoryDetail"})
     */
    protected $locality;

    /**
     * @ORM\OneToOne(targetEntity="NaturaPass\ObservationBundle\Entity\ObservationReceiverMedia", cascade={"persist", "remove"}, orphanRemoval=true)
     *
     * @JMS\Expose
     * @JMS\Groups({"ObservationCategoryDetail"})
     */
    protected $media;

    /**
     * @var string
     *
     * @ORM\Column(name="latitude", type="string", length=255)
     *
     * @JMS\Expose
     * @JMS\Groups({"ObservationCategoryDetail"})
     */
    protected $latitude;

    /**
     * @var string
     *
     * @ORM\Column(name="longitude", type="string", length=255)
     *
     * @JMS\Expose
     * @JMS\Groups({"ObservationCategoryDetail"})
     */
    protected $longitude;

    /**
     * @var string
     *
     * @ORM\Column(name="altitude", type="string", length=255, nullable=true)
     *
     * @JMS\Expose
     * @JMS\Groups({"ObservationCategoryDetail"})
     */
    protected $altitude;

    /**
     * @var string
     *
     * @ORM\Column(name="address", type="text", nullable=true)
     *
     * @JMS\Expose
     * @JMS\Groups({"ObservationCategoryDetail"})
     */
    protected $address;

    /**
     * @var string
     *
     * @ORM\Column(name="content", type="text")
     *
     * @JMS\Expose
     * @JMS\Groups({"ObservationCategoryDetail"})
     */
    protected $content;

    /**
     * @var string
     *
     * @ORM\Column(name="legend", type="string", length=255, nullable=true)
     *
     * @JMS\Expose
     * @JMS\Groups({"PublicationLess"})
     */
    protected $legend;

    /**
     * @ORM\ManyToOne(targetEntity="Admin\SentinelleBundle\Entity\Category", inversedBy="observationReceivers")
     * @ORM\JoinColumn(nullable=true, onDelete="SET NULL")
     *
     * @JMS\Expose
     * @JMS\Groups({"ObservationReceiverDetail"})
     */
    protected $category;

    /**
     * @ORM\ManyToOne(targetEntity="Admin\AnimalBundle\Entity\Animal", inversedBy="observationReceivers")
     * @ORM\JoinColumn(nullable=true, onDelete="SET NULL")
     *
     * @JMS\Expose
     * @JMS\Groups({"ObservationReceiverDetail"})
     */
    protected $animal;

    /**
     * @var integer
     *
     * @ORM\Column(name="specific_category", type="integer")
     *
     * @JMS\Expose
     * @JMS\Groups({"ObservationCategoryDetail"})
     */
    protected $specific = 0;

    /**
     * @ORM\OneToMany(targetEntity="NaturaPass\ObservationBundle\Entity\AttachmentReceiver", mappedBy="observationreceiver", cascade={"remove"}, fetch="EXTRA_LAZY", orphanRemoval=true)
     *
     * @JMS\Expose
     * @JMS\Groups({"ObservationReceiverLess"})
     */
    protected $attachmentreceivers;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="created", type="datetime")
     * @ORMExtension\Timestampable(on="create")
     *
     * @JMS\Expose
     * @JMS\Groups({"ObservationReceiverDetail"})
     */
    protected $created;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="updated", type="datetime")
     * @ORMExtension\Timestampable(on="update")
     *
     * @JMS\Expose
     * @JMS\Groups({"ObservationReceiverDetail"})
     */
    protected $updated;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->attachmentreceivers = new ArrayCollection();
    }

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }


    /**
     * Set User
     *
     * @param \NaturaPass\UserBundle\Entity\User $user
     * @return ObservationReceiver
     */
    public function setUser(User $user)
    {
        $this->user = $user;

        return $this;
    }

    /**
     * Get owner
     *
     * @return \NaturaPass\UserBundle\Entity\User
     */
    public function getUser()
    {
        return $this->user;
    }

    /**
     * Set created
     *
     * @param \DateTime $created
     * @return ObservationReceiver
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
     * @return ObservationReceiver
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
     * Set Receiver
     *
     * @param \Admin\SentinelleBundle\Entity\Receiver $receiver
     * @return ObservationReceiver
     */
    public function setReceiver(Receiver $receiver)
    {
        $this->receiver = $receiver;

        return $this;
    }

    /**
     * Get Receiver
     *
     * @return \Admin\SentinelleBundle\Entity\Receiver
     */
    public function getReceiver()
    {
        return $this->receiver;
    }

    /**
     * Get AttachmentReceiver
     *
     * @param array
     * @param boolean
     *
     * @return ArrayCollection
     */
    public function getAttachmentreceivers()
    {
        return $this->attachmentreceivers;
    }

    /**
     * Add AttachmentReceiver
     *
     * @param AttachmentReceiver $attachmentreceiver
     *
     * @return ObservationReceiver
     */
    public function addAttachmentreceiver(AttachmentReceiver $attachmentreceiver)
    {
        if (!$this->attachmentreceivers->contains($attachmentreceiver)) {
            $attachmentreceiver->setObservationreceiver($this);

            $this->attachmentreceivers->add($attachmentreceiver);
        }

        return $this;
    }

    /**
     * remove AttachmentReceiver
     *
     * @param AttachmentReceiver $attachmentreceiver
     *
     * @return ObservationReceiver
     */
    public function removeAttachmentreceiver(AttachmentReceiver $attachmentreceiver)
    {
        $this->attachmentreceivers->removeElement($attachmentreceiver);

        return $this;
    }

    /**
     * @param AttachmentReceiver[] $attachmentreceivers
     * @return ObservationReceiver
     */
    public function setAttachmentreceivers($attachmentreceivers)
    {
        foreach ($attachmentreceivers as $attachmentreceiver) {
            $attachmentreceiver->setObservationreceiver($this);
        }

        $this->attachmentreceivers = $attachmentreceivers;

        return $this;
    }

    /**
     * @return int
     */
    public function getFullname()
    {
        return $this->fullname;
    }

    /**
     * @param int $fullname
     * @return ObservationReceiver
     */
    public function setFullname($fullname)
    {
        $this->fullname = $fullname;
        return $this;
    }

    /**
     * @return int
     */
    public function getEmail()
    {
        return $this->email;
    }

    /**
     * @param int $email
     * @return ObservationReceiver
     */
    public function setEmail($email)
    {
        $this->email = $email;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getLocality()
    {
        return $this->locality;
    }

    /**
     * @param mixed $locality
     * @return ObservationReceiver
     */
    public function setLocality($locality)
    {
        $this->locality = $locality;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getMedia()
    {
        return $this->media;
    }

    /**
     * @param mixed $media
     * @return ObservationReceiverMedia
     */
    public function setMedia($media)
    {
        $this->media = $media;
        return $this;
    }

    /**
     * @return string
     */
    public function getLatitude()
    {
        return $this->latitude;
    }

    /**
     * @param string $latitude
     * @return ObservationReceiver
     */
    public function setLatitude($latitude)
    {
        $this->latitude = $latitude;
        return $this;
    }

    /**
     * @return string
     */
    public function getLongitude()
    {
        return $this->longitude;
    }

    /**
     * @param string $longitude
     * @return ObservationReceiver
     */
    public function setLongitude($longitude)
    {
        $this->longitude = $longitude;
        return $this;
    }

    /**
     * @return string
     */
    public function getAltitude()
    {
        return $this->altitude;
    }

    /**
     * @param string $altitude
     * @return ObservationReceiver
     */
    public function setAltitude($altitude)
    {
        $this->altitude = $altitude;
        return $this;
    }

    /**
     * @return string
     */
    public function getAddress()
    {
        return $this->address;
    }

    /**
     * @param string $address
     * @return ObservationReceiver
     */
    public function setAddress($address)
    {
        $this->address = $address;
        return $this;
    }

    /**
     * @return string
     */
    public function getContent()
    {
        return $this->content;
    }

    /**
     * @param string $content
     * @return ObservationReceiver
     */
    public function setContent($content)
    {
        $this->content = $content;
        return $this;
    }

    /**
     * @return string
     */
    public function getLegend()
    {
        return $this->legend;
    }

    /**
     * @param string $legend
     * @return ObservationReceiver
     */
    public function setLegend($legend)
    {
        $this->legend = $legend;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getCategory()
    {
        return $this->category;
    }

    /**
     * @param mixed $category
     * @return ObservationReceiver
     */
    public function setCategory($category)
    {
        $this->category = $category;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getAnimal()
    {
        return $this->animal;
    }

    /**
     * @param mixed $animal
     * @return ObservationReceiver
     */
    public function setAnimal($animal)
    {
        $this->animal = $animal;
        return $this;
    }

    /**
     * @return int
     */
    public function getSpecific()
    {
        return $this->specific;
    }

    /**
     * @param int $specific
     * @return ObservationReceiver
     */
    public function setSpecific($specific)
    {
        $this->specific = $specific;
        return $this;
    }

    public function duplicateObservation(Observation $observation)
    {
        $publication = $observation->getPublication();
        $geolocation = $publication->getGeolocation();
        $user = $publication->getOwner();
        if (!is_null($geolocation)) {
            $this->setLongitude($geolocation->getLongitude());
            $this->setLatitude($geolocation->getLatitude());
            $this->setAddress($geolocation->getAddress());
            $this->setAltitude($geolocation->getAltitude());
        }
        $this->setUser($user);
        $this->setCategory($observation->getCategory());
        $this->setAnimal($observation->getAnimal());
        foreach ($observation->getAttachments() as $attachment) {
            $attachmentreceiver = new AttachmentReceiver();
            $attachmentreceiver->duplicateAttachment($attachment);
            $attachmentreceiver->setObservationreceiver($this);
        }
        $this->setContent($publication->getContent());
        $this->setEmail($user->getEmail());
        $this->setFullname($user->getFullName());
        $this->setLegend($publication->getLegend());
        $this->setLocality($publication->getLocality());
        if (!is_null($publication->getMedia())) {
            $media = new ObservationReceiverMedia();
            $media->setName($publication->getMedia()->getName());
            $media->setState($publication->getMedia()->getState());
            $media->setType($publication->getMedia()->getType());
            $path = str_replace("publications", "receivers", $publication->getMedia()->getPath());
            $path = str_replace("resize", "original", $path);
            $media->setPath($path);
            if ($publication->getMedia()->getType() == PublicationMedia::TYPE_VIDEO) {
                if (file_exists($_SERVER["DOCUMENT_ROOT"] . $publication->getMedia()->getMp4())) {
                    copy($_SERVER["DOCUMENT_ROOT"] . $publication->getMedia()->getMp4(), $_SERVER["DOCUMENT_ROOT"] . str_replace("publications", "receivers", $publication->getMedia()->getMp4()));
                }
                if (file_exists($_SERVER["DOCUMENT_ROOT"] . $publication->getMedia()->getFlv())) {
                    copy($_SERVER["DOCUMENT_ROOT"] . $publication->getMedia()->getFlv(), $_SERVER["DOCUMENT_ROOT"] . str_replace("publications", "receivers", $publication->getMedia()->getMp4()));
                }
                if (file_exists($_SERVER["DOCUMENT_ROOT"] . $publication->getMedia()->getOgv())) {
                    copy($_SERVER["DOCUMENT_ROOT"] . $publication->getMedia()->getOgv(), $_SERVER["DOCUMENT_ROOT"] . str_replace("publications", "receivers", $publication->getMedia()->getMp4()));
                }
                if (file_exists($_SERVER["DOCUMENT_ROOT"] . $publication->getMedia()->getWebm())) {
                    copy($_SERVER["DOCUMENT_ROOT"] . $publication->getMedia()->getWebm(), $_SERVER["DOCUMENT_ROOT"] . str_replace("publications", "receivers", $publication->getMedia()->getMp4()));
                }
            } else {
                if (file_exists($_SERVER["DOCUMENT_ROOT"] . str_replace("resize", "original", $publication->getMedia()->getPath()))) {
                    copy($_SERVER["DOCUMENT_ROOT"] . str_replace("resize", "original", $publication->getMedia()->getPath()), $_SERVER["DOCUMENT_ROOT"] . $path);
                }
            }
            $this->setMedia($media);
            $this->setCreated($publication->getCreated());
        }
        $this->setSpecific($observation->getSpecific());

    }
}
