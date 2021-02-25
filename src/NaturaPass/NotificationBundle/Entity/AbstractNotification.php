<?php

namespace NaturaPass\NotificationBundle\Entity;

use Api\ApiBundle\Controller\v2\Serialization\NotificationSerialization;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as ORMExtension;
use JMS\Serializer\Annotation as JMS;
use NaturaPass\UserBundle\Entity\User;

/**
 * AbstractNotification
 *
 * @ORM\Table(name="Notification")
 * @ORM\Entity
 *
 * @ORM\InheritanceType("SINGLE_TABLE")
 * @ORM\DiscriminatorColumn(name="type", type="string")
 * @ORM\DiscriminatorMap({
 *      "user.friendship.confirmed" = "NaturaPass\NotificationBundle\Entity\User\UserFriendshipConfirmedNotification",
 *      "user.friendship.asked"     = "NaturaPass\NotificationBundle\Entity\User\UserFriendshipAskedNotification",
 *
 *      "lounge.publication.new"    = "NaturaPass\NotificationBundle\Entity\Lounge\LoungeNewPublicationNotification",
 *      "lounge.join.accepted"      = "NaturaPass\NotificationBundle\Entity\Lounge\LoungeJoinAcceptedNotification",
 *      "lounge.join.invited"       = "NaturaPass\NotificationBundle\Entity\Lounge\LoungeJoinInvitedNotification",
 *      "lounge.join.refused"       = "NaturaPass\NotificationBundle\Entity\Lounge\LoungeJoinRefusedNotification",
 *      "lounge.join.valid-asked"   = "NaturaPass\NotificationBundle\Entity\Lounge\LoungeJoinValidationAskedNotification",
 *      "lounge.chat.new_message"   = "NaturaPass\NotificationBundle\Entity\Lounge\LoungeMessageNotification",
 *      "lounge.status.changed"     = "NaturaPass\NotificationBundle\Entity\Lounge\LoungeStatusChangedNotification",
 *      "lounge.status.admin"       = "NaturaPass\NotificationBundle\Entity\Lounge\LoungeStatusAdminNotification",
 *
 *      "group.publication.new"    = "NaturaPass\NotificationBundle\Entity\Group\GroupPublicationNotification",
 *      "group.chat.new_message"    = "NaturaPass\NotificationBundle\Entity\Group\GroupMessageNotification",
 *      "group.join.valid-asked"    = "NaturaPass\NotificationBundle\Entity\Group\GroupValidationAskedNotification",
 *      "group.join.invited"        = "NaturaPass\NotificationBundle\Entity\Group\GroupJoinInvitedNotification",
 *      "group.join.accepted"       = "NaturaPass\NotificationBundle\Entity\Group\GroupJoinAcceptedNotification",
 *      "group.join.refused"        = "NaturaPass\NotificationBundle\Entity\Group\GroupJoinRefusedNotification",
 *      "group.subscriber.banned"   = "NaturaPass\NotificationBundle\Entity\Group\GroupSubscriberBannedNotification",
 *      "group.status.admin"        = "NaturaPass\NotificationBundle\Entity\Group\GroupStatusAdminNotification",
 *
 *      "publication.processed.success" = "NaturaPass\NotificationBundle\Entity\Publication\PublicationProcessedNotification",
 *      "publication.processed.error"   = "NaturaPass\NotificationBundle\Entity\Publication\PublicationProcessedErrorNotification",
 *      "publication.commented"         = "NaturaPass\NotificationBundle\Entity\Publication\PublicationCommentedNotification",
 *      "publication.liked"             = "NaturaPass\NotificationBundle\Entity\Publication\PublicationLikedNotification",
 *      "publication.comment.liked"             = "NaturaPass\NotificationBundle\Entity\Publication\PublicationCommentLikedNotification",
 *      "publication.shared"            = "NaturaPass\NotificationBundle\Entity\Publication\PublicationShareNotification",
 *      "publication.same_commented"    = "NaturaPass\NotificationBundle\Entity\Publication\PublicationSameCommentedNotification",
 *
 *      "chat.new_message"              = "NaturaPass\NotificationBundle\Entity\Chat\SocketOnly\ChatMessageNotification",
 * })
 *
 * @ORM\HasLifecycleCallbacks
 * @JMS\ExclusionPolicy("all")
 */
abstract class AbstractNotification implements NotificationInterface
{

    const TYPE = '';
    const PERIOD = false;

    /**
     * @var integer
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     *
     * @JMS\Expose
     *
     * @JMS\Groups({"NotificationLess"})
     */
    protected $id;

    /**
     * @var \NaturaPass\UserBundle\Entity\User $sender
     *
     * @ORM\ManyToOne(targetEntity="NaturaPass\UserBundle\Entity\User")
     *
     * @JMS\Expose
     * @JMS\Groups({"NotificationLess"})
     */
    protected $sender;

    /**
     * @var string
     *
     * @ORM\Column(name="link", type="text")
     *
     * @JMS\Expose
     * @JMS\Groups({"NotificationLess"})
     */
    protected $link;

    /**
     * @var string
     *
     * @ORM\Column(name="content", type="string", length=255)
     *
     * @JMS\Expose
     * @JMS\Groups({"NotificationLess"})
     */
    protected $content;

    /**
     * @var string
     *
     * @ORM\Column(name="object_id", type="text", nullable=true)
     *
     * @JMS\Expose
     * @JMS\Groups({"NotificationLess"})
     */
    protected $objectID;

    /**
     * @var string
     *
     * @ORM\Column(name="publication_id", type="text", nullable=true)
     *
     * @JMS\Expose
     * @JMS\Groups({"NotificationLess"})
     */
    protected $publicationID;

    /**
     * @var boolean
     *
     * @ORM\Column(name="visible", type="boolean", options={"default": true})
     *
     * @JMS\Expose
     * @JMS\Groups({"NotificationLess"})
     */
    protected $visible = true;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="created", type="datetime")
     * @ORMExtension\Timestampable(on="create")
     *
     * @JMS\Expose
     * @JMS\Groups({"NotificationLess"})
     */
    protected $created;

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
     * @var NotificationReceiver[] $receivers
     * @ORM\OneToMany(targetEntity="NaturaPass\NotificationBundle\Entity\NotificationReceiver", mappedBy="notification", cascade={"persist", "remove"}, fetch="EXTRA_LAZY", orphanRemoval=true)
     */
    protected $receivers;

    /**
     * Parameters of the notification
     *
     * @var array $options
     */
    protected $options = array(
        'route' => '',
        'sender' => 'client',
        'multiple' => false,
        'persistable' => true,
        'socket' => array(
            'enabled' => true,
            'event_name' => 'api-notification:incoming'
        ),
        'push' => array(
            'enabled' => true,
            'silent' => false
        )
    );

    public function __construct(array $options)
    {
        $this->receivers = new ArrayCollection();

        $this->options = array_merge($this->options, $options);
    }

    /**
     * Returns the data for the push data to be created
     *
     * @return array
     */
    public function getPushData()
    {
        return array(
            'notification_id' => $this->id,
            'object_id' => $this->getObjectID(),
            'type' => $this->getType()
        );
    }

    /**
     * Returns the data for the socket data to be created
     *
     * @return array
     */
    public function getSocketData()
    {
        return NotificationSerialization::serializeNotification($this);
    }

    /**
     * Tell if the notification is send by a client or the server
     *
     * @return boolean
     */
    public function isSenderServer()
    {
        return $this->options['sender'] === 'server';
    }

    /**
     * Tell if the notification is send by a client or the server
     *
     * @return boolean
     */
    public function isSenderClient()
    {
        return $this->options['sender'] === 'client';
    }

    /**
     * The route to follow
     *
     * @return string
     */
    public function getRoute()
    {
        return $this->options['route'];
    }

    /**
     * The type of the notification
     *
     * @return string
     */
    public function getType()
    {
        return $this::TYPE;
    }

    /**
     * Tell if the notification is socket enabled
     *
     * @return boolean
     */
    public function isSocketEnabled()
    {
        return $this->options['socket']['enabled'];
    }

    /**
     * Tell whether the notification is persistable or not
     *
     * @return boolean
     */
    public function isPersistable()
    {
        return $this->options['persistable'];
    }

    /**
     * Tell if the notification is push enabled
     *
     * @return boolean
     */
    public function isPushEnabled()
    {
        return $this->options['push']['enabled'];
    }

    /**
     * Return the name of the socket event
     *
     * @return string
     */
    public function getSocketEventName()
    {
        return $this->options['socket']['event_name'];
    }

    /**
     * Whether the notification can be multiple or not
     *
     * @return boolean
     */
    public function isMultiple()
    {
        return $this->options['multiple'];
    }

    /**
     * Determine whether the notification can be silent or not
     *
     * @return boolean
     */
    public function isPushSilent()
    {
        return $this->options['push']['silent'];
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
     * Set link
     *
     * @param string $link
     * @return AbstractNotification
     */
    public function setLink($link)
    {
        $this->link = $link;

        return $this;
    }

    /**
     * Get link
     *
     * @return string
     */
    public function getLink()
    {
        return $this->link;
    }

    /**
     * Set content
     *
     * @param string $content
     * @return AbstractNotification
     */
    public function setContent($content)
    {
        $this->content = $content;

        return $this;
    }

    /**
     * Get content
     *
     * @return string
     */
    public function getContent()
    {
        return $this->content;
    }

    /**
     * Set sender
     *
     * @param \NaturaPass\UserBundle\Entity\User $sender
     * @return AbstractNotification
     */
    public function setSender(User $sender = null)
    {
        $this->sender = $sender;

        return $this;
    }

    /**
     * Get sender
     *
     * @return \NaturaPass\UserBundle\Entity\User
     */
    public function getSender()
    {
        return $this->sender;
    }

    /**
     * @param string $objectID
     *
     * @return $this
     */
    public function setObjectID($objectID)
    {
        $this->objectID = $objectID;
        return $this;
    }

    /**
     * @return string
     */
    public function getObjectID()
    {
        return $this->objectID;
    }

    /**
     * @param string $publicationID
     *
     * @return $this
     */
    public function setPublicationID($publicationID)
    {
        $this->publicationID = $publicationID;
        return $this;
    }

    /**
     * @return string
     */
    public function getPublicationID()
    {
        return $this->publicationID;
    }

    /**
     * @return integer
     */
    public function getObjectIDModel()
    {
        return 0;
    }

    /**
     * Add receiver
     *
     * @param \NaturaPass\NotificationBundle\Entity\NotificationReceiver $receiver
     * @return AbstractNotification
     */
    public function addReceiver(NotificationReceiver $receiver)
    {
        $this->receivers[] = $receiver;

        return $this;
    }

    /**
     * Remove receiver
     *
     * @param \NaturaPass\NotificationBundle\Entity\NotificationReceiver $receiver
     */
    public function removeReceiver(NotificationReceiver $receiver)
    {
        $this->receivers->removeElement($receiver);
    }

    /**
     * Get receivers
     *
     * @return \Doctrine\Common\Collections\ArrayCollection|\NaturaPass\NotificationBundle\Entity\NotificationReceiver[]
     */
    public function getReceivers()
    {
        return $this->receivers;
    }

    /**
     * has Receiver
     *
     * @param User $user
     *
     * @return int
     */
    public function hasReceiver(User $user)
    {
        return count($this->receivers->filter(function ($element) use ($user) {
            return $element->getReceiver()->getId() === $user->getId();
        }));
    }

    /**
     * @param boolean $visible
     *
     * @return AbstractNotification
     */
    public function setVisible($visible)
    {
        $this->visible = $visible;

        return $this;
    }

    /**
     * @return boolean
     */
    public function getVisible()
    {
        return $this->visible;
    }

    /**
     * Set created
     *
     * @param \DateTime $created
     * @return AbstractNotification
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
     * @return AbstractNotification
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
     * @return array
     */
    public function getOptions()
    {
        return $this->options;
    }

    /**
     * @param array $options
     * @return AbstractNotification
     */
    public function setOptions($options)
    {
        $this->options = $options;

        return $this;
    }

}