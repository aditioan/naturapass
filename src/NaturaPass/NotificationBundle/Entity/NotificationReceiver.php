<?php

namespace NaturaPass\NotificationBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as ORMExtension;
use JMS\Serializer\Annotation as JMS;
use NaturaPass\UserBundle\Entity\User;

/**
 * UserNotification
 *
 * @ORM\Table(name="notification_has_receiver")
 * @ORM\Entity
 * @ORM\HasLifecycleCallbacks
 * @JMS\ExclusionPolicy("all")
 */
class NotificationReceiver {

    const STATE_UNREAD = 0;
    const STATE_READ = 1;

    /**
     * @ORM\Id
     * @ORM\ManyToOne(targetEntity="NaturaPass\NotificationBundle\Entity\AbstractNotification", inversedBy="receivers")
     *
     * @var \NaturaPass\NotificationBundle\Entity\AbstractNotification
     *
     * @JMS\Expose
     * @JMS\Groups({"NotificationLess"})
     */
    protected $notification;

    /**
     * @ORM\Id
     * @ORM\ManyToOne(targetEntity="NaturaPass\UserBundle\Entity\User", inversedBy="notifications")
     * @ORM\JoinColumn(onDelete="CASCADE")
     *
     * @var \NaturaPass\UserBundle\Entity\User
     */
    protected $receiver;

    /**
     * @var integer
     *
     * @ORM\Column(name="state", type="integer")
     */
    protected $state;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="updated", type="datetime")
     * @ORMExtension\Timestampable(on="update")
     *
     * @JMS\Expose
     * @JMS\Groups({"NotificationLess"})
     */
    protected $updated;

    /**
     * Set state
     *
     * @param integer $state
     * @return NotificationReceiver
     */
    public function setState($state) {
        $this->state = $state;

        return $this;
    }

    /**
     * Get state
     *
     * @return integer
     */
    public function getState() {
        return $this->state;
    }

    /**
     * Set updated
     *
     * @param \DateTime $updated
     * @return NotificationReceiver
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
     * Set notification
     *
     * @param \NaturaPass\NotificationBundle\Entity\AbstractNotification $notification
     * @return NotificationReceiver
     */
    public function setNotification(AbstractNotification $notification) {
        $this->notification = $notification;

        return $this;
    }

    /**
     * Get notification
     *
     * @return \NaturaPass\NotificationBundle\Entity\AbstractNotification
     */
    public function getNotification() {
        return $this->notification;
    }

    /**
     * Set receiver
     *
     * @param \NaturaPass\UserBundle\Entity\User $receiver
     * @return NotificationReceiver
     */
    public function setReceiver(User $receiver) {
        $this->receiver = $receiver;

        return $this;
    }

    /**
     * Get receiver
     *
     * @return \NaturaPass\UserBundle\Entity\User
     */
    public function getReceiver() {
        return $this->receiver;
    }

}