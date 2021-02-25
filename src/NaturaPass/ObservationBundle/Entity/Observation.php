<?php

namespace NaturaPass\ObservationBundle\Entity;

use Admin\SentinelleBundle\Entity\Category;
use Admin\AnimalBundle\Entity\Animal;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation as JMS;
use Gedmo\Mapping\Annotation as ORMExtension;
use NaturaPass\PublicationBundle\Entity\Publication;
use Admin\SentinelleBundle\Entity\Receiver;

/**
 * Observation
 *
 * @ORM\Table()
 * @ORM\Entity()
 *
 * @JMS\ExclusionPolicy("all")
 */
class Observation
{

    /**
     * @var integer
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     *
     * @JMS\Expose
     * @JMS\Groups({"ObservationDetail", "ObservationLess", "ObservationID"})
     */
    protected $id;

    /**
     * @ORM\ManyToOne(targetEntity="NaturaPass\PublicationBundle\Entity\Publication", inversedBy="observations")
     *
     * @JMS\Expose
     * @JMS\Groups({"ObservationPublicationDetail"})
     */
    protected $publication;

    /**
     * @ORM\ManyToOne(targetEntity="Admin\SentinelleBundle\Entity\Category", inversedBy="observations")
     *
     * @JMS\Expose
     * @JMS\Groups({"ObservationCategoryDetail"})
     */
    protected $category;

    /**
     * @ORM\ManyToOne(targetEntity="Admin\AnimalBundle\Entity\Animal", inversedBy="observations")
     *
     * @JMS\Expose
     * @JMS\Groups({"ObservationCategoryDetail"})
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
     * @ORM\OneToMany(targetEntity="NaturaPass\ObservationBundle\Entity\Attachment", mappedBy="observation", cascade={"remove"}, fetch="EXTRA_LAZY", orphanRemoval=true)
     *
     * @JMS\Expose
     * @JMS\Groups({"ObservationAttachementDetail"})
     */
    protected $attachments;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="created", type="datetime")
     * @ORMExtension\Timestampable(on="create")
     *
     * @JMS\Expose
     * @JMS\Groups({"ObservationDetail"})
     */
    protected $created;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="updated", type="datetime")
     * @ORMExtension\Timestampable(on="update")
     *
     * @JMS\Expose
     * @JMS\Groups({"ObservationDetail"})
     */
    protected $updated;

    /**
     * @var \Doctrine\Common\Collections\ArrayCollection|ObservationReceiver[]
     *
     * @ORM\ManyToMany(targetEntity="\Admin\SentinelleBundle\Entity\Receiver")
     * @ORM\JoinColumn(nullable=false,onDelete="CASCADE")
     * @ORM\JoinTable(name="observation_sharing_receiver")
     *
     * @JMS\Expose
     * @JMS\Groups({"ObservationReceiverDetail"})
     */
    protected $receivers;

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
     * Get specific
     *
     * @return integer
     */
    public function getSpecific()
    {
        return $this->specific;
    }

    /**
     * Set specific
     *
     * @param integer $specific
     * @return Observation
     */
    public function setSpecific($specific)
    {
        $this->specific = $specific;

        return $this;
    }

    /**
     * Set user
     *
     * @param \NaturaPass\PublicationBundle\Entity\Publication $publication
     * @return Observation
     */
    public function setPublication(Publication $publication)
    {
        $this->publication = $publication;

        return $this;
    }

    /**
     * Get user
     *
     * @return \NaturaPass\PublicationBundle\Entity\Publication
     */
    public function getPublication()
    {
        return $this->publication;
    }

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->receivers = new ArrayCollection();
        $this->attachments = new ArrayCollection();
    }

    /**
     * Set created
     *
     * @param \DateTime $created
     * @return Observation
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
     * @return Observation
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
     * @return Category
     */
    public function getCategory()
    {
        return $this->category;
    }

    /**
     * @param Category $category
     * @return Observation
     */
    public function setCategory(Category $category)
    {
        $this->category = $category;

        return $this;
    }

    /**
     * @return Animal
     */
    public function getAnimal()
    {
        return $this->animal;
    }

    /**
     * @param null|Animal $animal
     * @return Observation
     */
    public function setAnimal(Animal $animal = null)
    {
        $this->animal = $animal;

        return $this;
    }


    /**
     * Get Attachment
     *
     * @param array
     * @param boolean
     *
     * @return ArrayCollection
     */
    public function getAttachments()
    {
        return $this->attachments;
    }

    /**
     * Add attachment
     *
     * @param Attachment $attachment
     *
     * @return Observation
     */
    public function addAttachment(Attachment $attachment)
    {
        if (!$this->attachments->contains($attachment)) {
            $attachment->setObservation($this);

            $this->attachments->add($attachment);
        }

        return $this;
    }

    /**
     * remove attachment
     *
     * @param Attachment $attachment
     *
     * @return Observation
     */
    public function removeAttachment(Attachment $attachment)
    {
        $this->attachments->removeElement($attachment);

        return $this;
    }

    /**
     * @param Attachment[] $attachments
     * @return Observation
     */
    public function setAttachments($attachments)
    {
        foreach ($attachments as $attachment) {
            $attachment->setObservation($this);
        }

        $this->attachments = $attachments;

        return $this;
    }

    /**
     * Get Receiver
     *
     * @param array
     * @param boolean
     *
     * @return ArrayCollection
     */
    public function getReceivers()
    {
        return $this->receivers;
    }

    /**
     * @param Receiver[] $receivers
     * @return Observation
     */
    public function setReceivers($receivers)
    {
        $this->receivers = $receivers;

        return $this;
    }

    /**
     * Add Receiver
     *
     * @param \Admin\SentinelleBundle\Entity\Receiver $receiver
     * @return Observation
     */
    public function addReceiver(Receiver $receiver)
    {
        if (!$this->receivers->contains($receiver)) {
            $this->receivers->add($receiver);
        }

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

}
