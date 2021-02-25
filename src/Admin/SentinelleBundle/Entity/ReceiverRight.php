<?php

namespace Admin\SentinelleBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation as JMS;
use Gedmo\Mapping\Annotation as ORMExtension;

/**
 * ReceiverRight
 *
 * @ORM\Table(name="`receiver_category_right`")
 * @ORM\Entity()
 *
 * @JMS\ExclusionPolicy("all")
 */
class ReceiverRight {

    /**
     * @ORM\Id
     * @ORM\ManyToOne(targetEntity="Admin\SentinelleBundle\Entity\Receiver", inversedBy="receiverrights")
     *
     * @JMS\Expose
     * @JMS\Groups({"ReceiverRightDetail", "ReceiverRightLess", "ReceiverRightID"})
     */
    protected $receiver;

    /**
     * @ORM\Id
     * @ORM\ManyToOne(targetEntity="Admin\SentinelleBundle\Entity\Category", inversedBy="receiverrights")
     *
     * @JMS\Expose
     * @JMS\Groups({"ReceiverRightLess"})
     */
    protected $category;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="created", type="datetime")
     * @ORMExtension\Timestampable(on="create")
     *
     * @JMS\Expose
     * @JMS\Groups({"ReceiverRightDetail"})
     */
    protected $created;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="updated", type="datetime")
     * @ORMExtension\Timestampable(on="update")
     *
     * @JMS\Expose
     * @JMS\Groups({"ReceiverRightDetail"})
     */
    protected $updated;

    /**
     * Get id
     *
     * @return integer
     */
    public function getId() {
        return $this->id;
    }

    /**
     * Constructor
     */
    public function __construct() {

    }

    /**
     * Set created
     *
     * @param \DateTime $created
     * @return ReceiverRight
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
     * @return ReceiverRight
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
     * Set Receiver
     *
     * @param \Admin\SentinelleBundle\Entity\Receiver $receiver
     * @return ReceiverRight
     */
    public function setReceiver(Receiver $receiver) {
        $this->receiver = $receiver;

        return $this;
    }

    /**
     * Get Receiver
     *
     * @return \Admin\SentinelleBundle\Entity\Receiver
     */
    public function getReceiver() {
        return $this->receiver;
    }

    /**
     * Set Category
     *
     * @param \Admin\SentinelleBundle\Entity\Category $category
     * @return ReceiverRight
     */
    public function setCategory(Category $category) {
        $this->category = $category;

        return $this;
    }

    /**
     * Get Category
     *
     * @return \Admin\SentinelleBundle\Entity\Category
     */
    public function getCategory() {
        return $this->category;
    }

}
