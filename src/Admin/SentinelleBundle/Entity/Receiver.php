<?php

namespace Admin\SentinelleBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation as JMS;
use Gedmo\Mapping\Annotation as ORMExtension;
use NaturaPass\MainBundle\Entity\Geolocation;
use NaturaPass\ObservationBundle\Entity\Observation;
use NaturaPass\GroupBundle\Entity\Group;
use NaturaPass\UserBundle\Entity\User;

/**
 * Receiver
 *
 * @ORM\Table(name="`receiver`")
 * @ORM\Entity()
 *
 * @JMS\ExclusionPolicy("all")
 */
class Receiver
{

    /**
     * @var integer
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     *
     * @JMS\Expose
     * @JMS\Groups({"ReceiverDetail", "ReceiverLess", "ReceiverID"})
     */
    protected $id;

    /**
     * @var string
     *
     * @ORM\Column(name="name", type="string", length=255)
     *
     * @JMS\Expose
     * @JMS\Groups({"ReceiverDetail", "ReceiverLess"})
     */
    protected $name;

    /**
     * @var string
     *
     * @ORM\Column(name="ftplogin", type="string", length=255, nullable=true)
     *
     * @JMS\Expose
     * @JMS\Groups({"ReceiverDetail", "ReceiverLess"})
     */
    protected $ftplogin;

    /**
     * @var string
     *
     * @ORM\Column(name="ftppassword", type="string", length=255, nullable=true)
     *
     * @JMS\Expose
     * @JMS\Groups({"ReceiverDetail", "ReceiverLess"})
     */
    protected $ftppassword;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="created", type="datetime")
     * @ORMExtension\Timestampable(on="create")
     *
     * @JMS\Expose
     * @JMS\Groups({"ReceiverDetail"})
     */
    protected $created;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="updated", type="datetime")
     * @ORMExtension\Timestampable(on="update")
     *
     * @JMS\Expose
     * @JMS\Groups({"ReceiverDetail"})
     */
    protected $updated;

    /**
     * @var ReceiverMedia
     *
     * @ORM\OneToOne(targetEntity="Admin\SentinelleBundle\Entity\ReceiverMedia", cascade={"persist", "remove"}, orphanRemoval=true)
     * @ORM\JoinColumn(nullable=true)
     *
     * @JMS\Expose
     * @JMS\Groups({"ReceiverPhoto"})
     */
    protected $photo;

    /**
     *
     * @ORM\ManyToMany(targetEntity="Admin\SentinelleBundle\Entity\Locality", mappedBy="receivers")
     *
     * @JMS\Expose
     * @JMS\Groups({"ReceiverDetail"})
     */
    protected $localities;

    /**
     *
     * @var \Doctrine\Common\Collections\ArrayCollection|\NaturaPass\ObservationBundle\Entity\ObservationReceiver[]
     *
     * @ORM\OneToMany(targetEntity="NaturaPass\ObservationBundle\Entity\ObservationReceiver", mappedBy="receiver", cascade={"persist", "remove"}, fetch="EXTRA_LAZY", orphanRemoval=true)
     *
     * @JMS\Expose
     * @JMS\Groups({"ReceiverDetail"})
     */
    protected $observations;

    /**
     * @var \Doctrine\Common\Collections\ArrayCollection|\Admin\SentinelleBundle\Entity\ReceiverRight[]
     *
     * @ORM\OneToMany(targetEntity="Admin\SentinelleBundle\Entity\ReceiverRight", mappedBy="receiver", cascade={"persist", "remove"}, fetch="EXTRA_LAZY", orphanRemoval=true)
     *
     * @JMS\Expose
     * @JMS\Groups({"ReceiverDetail"})
     */
    protected $receiverrights;

    /**
     * @var ArrayCollection
     *
     * @ORM\ManyToMany(targetEntity="NaturaPass\UserBundle\Entity\User", inversedBy="receivers", fetch="EXTRA_LAZY");
     * @ORM\JoinTable(name="receiver_has_user")
     *
     * @JMS\Expose
     * @JMS\Groups({"ReceiverDetail"})
     */
    protected $users;

    /**
     * @var ArrayCollection
     *
     * @ORM\ManyToMany(targetEntity="NaturaPass\GroupBundle\Entity\Group", fetch="EXTRA_LAZY");
     * @ORM\JoinTable(name="receiver_has_group")
     *
     * @JMS\Expose
     * @JMS\Groups({"ReceiverDetail"})
     */
    protected $groups;

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
     * Set name
     *
     * @param string $name
     * @return Receiver
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
     * @return string
     */
    public function getFtplogin()
    {
        return $this->ftplogin;
    }

    /**
     * @return string
     */
    public function getFtppassword()
    {
        return $this->ftppassword;
    }

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->groups = new ArrayCollection();
        $this->localities = new ArrayCollection();
        $this->observations = new ArrayCollection();
        $this->receiverrights = new ArrayCollection();
        $this->users = new ArrayCollection();
    }

    /**
     * Set created
     *
     * @param \DateTime $created
     * @return Receiver
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
     * @return Receiver
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
     * Set photo
     *
     * @param \Admin\SentinelleBundle\Entity\ReceiverMedia $photo
     * @return Receiver
     */
    public function setPhoto(ReceiverMedia $photo = null)
    {
        $this->photo = $photo;

        return $this;
    }

    /**
     * Get photo
     *
     * @return \Admin\SentinelleBundle\Entity\ReceiverMedia
     */
    public function getPhoto()
    {
        return $this->photo;
    }

    /**
     * Get Observation
     *
     * @param array
     * @param boolean
     *
     * @return ArrayCollection
     */
    public function getObservations()
    {
        return $this->observations;
    }

    /**
     * Add Observation
     *
     * @param \NaturaPass\ObservationBundle\Entity\Observation $observation
     * @return Receiver
     */
    public function addObservation(Observation $observation)
    {
        $this->observations[] = $observation;

        return $this;
    }

    /**
     * Remove Observation
     *
     * @param \NaturaPass\ObservationBundle\Entity\Observation $observation
     */
    public function removeObservation(Observation $observation)
    {
        $this->observations->removeElement($observation);
    }

    /**
     * Get Localities
     *
     * @param array
     * @param boolean
     *
     * @return ArrayCollection
     */
    public function getLocalities()
    {
        return $this->localities;
    }

    /**
     * Add Locality
     *
     * @param \Admin\SentinelleBundle\Entity\Locality $locality
     * @return Receiver
     */
    public function addLocality(Locality $locality)
    {
        $this->localities[] = $locality;

        return $this;
    }

    /**
     * Remove Locality
     *
     * @param \Admin\SentinelleBundle\Entity\Locality $locality
     */
    public function removeLocality(Locality $locality)
    {
        $this->localities->removeElement($locality);
    }

    /**
     * Remove localities
     *
     */
    public function removeAllLocalities()
    {
        $elements = $this->getLocalities();
        foreach ($elements as $element) {
            $element->removeReceiver($this);
        }
    }

    /**
     * Get ReceiverRight
     *
     * @param array
     * @param boolean
     *
     * @return ArrayCollection
     */
    public function getReceiverrights()
    {
        return $this->receiverrights;
    }

    /**
     * Add ReceiverRight
     *
     * @param \Admin\SentinelleBundle\Entity\ReceiverRight $receiverrights
     * @return Receiver
     */
    public function addReceiverright(ReceiverRight $receiverrights)
    {
        $this->receiverrights[] = $receiverrights;

        return $this;
    }

    /**
     * Remove ReceiverRight
     *
     * @param \Admin\SentinelleBundle\Entity\ReceiverRight $receiverrights
     */
    public function removeReceiverright(ReceiverRight $receiverrights)
    {
        $this->receiverrights->removeElement($receiverrights);
    }

    /**
     * Add group
     *
     * @param \NaturaPass\GroupBundle\Entity\Group $group
     * @return \Admin\SentinelleBundle\Entity\ReceiverUser
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
        $elements = $this->getGroups();
        foreach ($elements as $element) {
            $this->groups->removeElement($element);
        }
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
     * Get group
     *
     */
    public function getGroupsFormat()
    {
        $array = array();
        foreach ($this->groups as $group) {
            $array[] = array("id" => $group->getId(), "text" => $group->getName());
        }
        return $array;
    }

    /**
     *
     * @param \NaturaPass\GroupBundle\Entity\Group $group
     * @return boolean
     */
    public function hasGroup(Group $group)
    {
        if ($this->groups->isEmpty()) {
            return false;
        } else {
            $groups = $this->groups->filter(function ($element) use ($group) {
                return $element->getId() == $group->getId();
            });
            return !$groups->isEmpty();
        }
    }

    /**
     * Add user
     *
     * @param \NaturaPass\UserBundle\Entity\User $user
     * @return \Admin\SentinelleBundle\Entity\ReceiverUser
     */
    public function addUser(User $user)
    {
        $this->users[] = $user;

        return $this;
    }

    /**
     * Remove user
     *
     * @param \NaturaPass\UserBundle\Entity\User $user
     */
    public function removeUser(User $user)
    {
        $this->users->removeElement($user);
    }

    /**
     * Remove users
     *
     */
    public function removeAllUsers($em)
    {
        $elements = $this->getUsers();
        foreach ($elements as $element) {
            $element->removeRole("ROLE_BACKOFFICE");
            $em->persist($element);
            $em->flush();
            $this->users->removeElement($element);
        }
    }

    /**
     * Get user
     *
     * @return ArrayCollection|\NaturaPass\UserBundle\Entity\User[]
     */
    public function getUsers()
    {
        return $this->users;
    }

}
