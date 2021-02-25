<?php

namespace NaturaPass\ObservationBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation as JMS;
use Gedmo\Mapping\Annotation as ORMExtension;
use Admin\SentinelleBundle\Entity\CardLabel;

/**
 * AttachmentReceiver
 *
 * @ORM\Table(name="`receiver_has_observation_attachment`")
 * @ORM\Entity()
 *
 * @JMS\ExclusionPolicy("all")
 */
class AttachmentReceiver
{
    /**
     * @var integer
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     *
     * @JMS\Expose
     * @JMS\Groups({"AttachmentDetail", "AttachmentLess", "AttachmentID"})
     */
    protected $id;

    /**
     * @ORM\ManyToOne(targetEntity="Admin\SentinelleBundle\Entity\CardLabel")
     * @ORM\JoinColumn(nullable=false)
     *
     * @JMS\Expose
     * @JMS\Groups({"AttachmentLabel"})
     */
    protected $label;

    /**
     * @ORM\ManyToOne(targetEntity="NaturaPass\ObservationBundle\Entity\ObservationReceiver", inversedBy="attachmentreceivers")
     * @ORM\JoinColumn(nullable=false)
     *
     * @JMS\Expose
     * @JMS\Groups({"AttachmentObservation"})
     */
    protected $observationreceiver;

    /**
     * @var string
     *
     * @ORM\Column(name="value", type="text")
     *
     * @JMS\Expose
     * @JMS\Groups({"AttachmentDetail", "AttachmentLess"})
     */
    protected $value;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="created", type="datetime")
     * @ORMExtension\Timestampable(on="create")
     *
     * @JMS\Expose
     * @JMS\Groups({"AttachmentDetail"})
     */
    protected $created;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="updated", type="datetime")
     * @ORMExtension\Timestampable(on="update")
     *
     * @JMS\Expose
     * @JMS\Groups({"AttachmentDetail"})
     */
    protected $updated;

    /**
     * Set Value
     *
     * @param string $value
     * @return Attachment
     */
    public function setValue($value)
    {
        $this->value = $value;

        return $this;
    }

    /**
     * Get Value
     *
     * @return string
     */
    public function getValue()
    {
        return $this->value;
    }

    /**
     * Set created
     *
     * @param \DateTime $created
     * @return Attachment
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
     * @return Attachment
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
     * Get Category
     *
     * @return \Admin\SentinelleBundle\Entity\CardLabel
     */
    public function getLabel()
    {
        return $this->label;
    }

    /**
     * Set Category
     *
     * @param \Admin\SentinelleBundle\Entity\CardLabel $label
     * @return Attachment
     */
    public function setLabel(CardLabel $label)
    {
        $this->label = $label;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getObservationreceiver()
    {
        return $this->observationreceiver;
    }

    /**
     * @param mixed $observationreceiver
     */
    public function setObservationreceiver($observationreceiver)
    {
        $this->observationreceiver = $observationreceiver;
    }

    public function duplicateAttachment(Attachment $attachement)
    {
        $this->value = $attachement->getValue();
        $this->label = $attachement->getLabel();
        $this->created = $attachement->getCreated();
        $this->updated = $attachement->getUpdated();
    }
}
