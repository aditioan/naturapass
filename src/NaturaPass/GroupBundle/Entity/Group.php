<?php

namespace NaturaPass\GroupBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation as JMS;
use Gedmo\Mapping\Annotation as ORMExtension;
use NaturaPass\MainBundle\Entity\Shape;
use NaturaPass\PublicationBundle\Entity\Favorite;
use NaturaPass\PublicationBundle\Entity\Publication;
use NaturaPass\UserBundle\Entity\User;
use NaturaPass\LoungeBundle\Entity\Lounge;

/**
 * Group
 *
 * @ORM\Table(name="`Group`")
 * @ORM\Entity()
 *
 * @JMS\ExclusionPolicy("all")
 */
class Group
{

    const ACCESS_PROTECTED = 0;
    const ACCESS_SEMIPROTECTED = 1;
    const ACCESS_PUBLIC = 2;

    const ALLOW_ALL_MEMBERS = 1;
    const ALLOW_ADMIN = 0;

    /**
     * @var integer
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     *
     * @JMS\Expose
     * @JMS\Groups({"GroupDetail", "GroupLess", "GroupID"})
     */
    protected $id;

    /**
     * @var User
     *
     * @ORM\ManyToOne(targetEntity="NaturaPass\UserBundle\Entity\User", inversedBy="groupsOwner")
     * @ORM\JoinColumn(nullable=false)
     *
     * @JMS\Expose
     * @JMS\Groups({"GroupDetail"})
     */
    protected $owner;

    /**
     * @var string
     *
     * @ORMExtension\Slug(fields={"name"}, updatable=false)
     * @ORM\Column(name="grouptag", type="string", length=255, unique=true, nullable=false)
     *
     * @JMS\Expose
     * @JMS\Groups({"GroupDetail", "GroupLess"})
     */
    protected $grouptag;

    /**
     * @var string
     *
     * @ORM\Column(name="name", type="string", length=255)
     *
     * @JMS\Expose
     * @JMS\Groups({"GroupDetail", "GroupLess"})
     */
    protected $name;

    /**
     * @var string
     *
     * @ORM\Column(name="description", type="text")
     *
     * @JMS\Expose
     * @JMS\Groups({"GroupDetail", "GroupLess"})
     */
    protected $description;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="created", type="datetime")
     * @ORMExtension\Timestampable(on="create")
     *
     * @JMS\Expose
     * @JMS\Groups({"GroupDetail"})
     */
    protected $created;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="updated", type="datetime")
     * @ORMExtension\Timestampable(on="update")
     *
     * @JMS\Expose
     * @JMS\Groups({"GroupDetail"})
     */
    protected $updated;

    /**
     * @var GroupMedia
     *
     * @ORM\OneToOne(targetEntity="NaturaPass\GroupBundle\Entity\GroupMedia", cascade={"persist", "remove"}, orphanRemoval=true)
     * @ORM\JoinColumn(nullable=true)
     *
     * @JMS\Expose
     * @JMS\Groups({"GroupPhoto"})
     */
    protected $photo;

    /**
     * @var integer
     *
     * @ORM\Column(name="access", type="integer")
     *
     * @JMS\Expose
     * @JMS\Groups({"GroupDetail"})
     */
    protected $access = Group::ACCESS_SEMIPROTECTED;

    /**
     * @var integer
     *
     * @ORM\Column(name="allow_add", type="integer", options={"default" = 1})
     *
     * @JMS\Expose
     * @JMS\Groups({"GroupDetail"})
     */
    protected $allowAdd = Group::ALLOW_ALL_MEMBERS;

    /**
     * @var integer
     *
     * @ORM\Column(name="allow_show", type="integer", options={"default" = 1})
     *
     * @JMS\Expose
     * @JMS\Groups({"GroupDetail"})
     */
    protected $allowShow = Group::ALLOW_ALL_MEMBERS;

    /**
     * @var integer
     *
     * @ORM\Column(name="allow_add_chat", type="integer", options={"default" = 1})
     *
     * @JMS\Expose
     * @JMS\Groups({"GroupDetail"})
     */
    protected $allowAddChat = Group::ALLOW_ALL_MEMBERS;

    /**
     * @var integer
     *
     * @ORM\Column(name="allow_show_chat", type="integer", options={"default" = 1})
     *
     * @JMS\Expose
     * @JMS\Groups({"GroupDetail"})
     */
    protected $allowShowChat = Group::ALLOW_ALL_MEMBERS;

    /**
     * @var \Doctrine\Common\Collections\ArrayCollection|\NaturaPass\GroupBundle\Entity\GroupUser[]
     *
     * @ORM\OneToMany(targetEntity="NaturaPass\GroupBundle\Entity\GroupUser", mappedBy="group", cascade={"persist", "remove"}, fetch="EXTRA_LAZY", orphanRemoval=true)
     *
     * @JMS\Expose
     * @JMS\Groups({"GroupSubscribers"})
     */
    protected $subscribers;

    /**
     * @var \Doctrine\Common\Collections\ArrayCollection|\NaturaPass\GroupBundle\Entity\GroupNotification[]
     *
     * @ORM\OneToMany(targetEntity="NaturaPass\GroupBundle\Entity\GroupNotification", mappedBy="group", cascade={"persist", "remove"}, fetch="EXTRA_LAZY", orphanRemoval=true)
     *
     * @JMS\Expose
     * @JMS\Groups({"GroupSubscribers"})
     */
    protected $notifications;

    /**
     * @var \Doctrine\Common\Collections\ArrayCollection|\NaturaPass\GroupBundle\Entity\GroupEmail[]
     *
     * @ORM\OneToMany(targetEntity="NaturaPass\GroupBundle\Entity\GroupEmail", mappedBy="group", cascade={"persist", "remove"}, fetch="EXTRA_LAZY", orphanRemoval=true)
     *
     * @JMS\Expose
     * @JMS\Groups({"GroupSubscribers"})
     */
    protected $emails;

    /**
     * @ORM\ManyToMany(targetEntity="NaturaPass\PublicationBundle\Entity\Publication", mappedBy="groups", fetch="EXTRA_LAZY");
     * @ORM\OrderBy({"created" = "DESC"})
     *
     * @JMS\Expose
     * @JMS\Groups({"GroupPublication"})
     */
    protected $publications;

    /**
     * @ORM\ManyToMany(targetEntity="NaturaPass\PublicationBundle\Entity\Favorite", mappedBy="groups", fetch="EXTRA_LAZY");
     * @ORM\OrderBy({"created" = "DESC"})
     *
     * @JMS\Expose
     * @JMS\Groups({"GroupPublication"})
     */
    protected $favorites;

    /**
     * @ORM\ManyToMany(targetEntity="NaturaPass\MainBundle\Entity\Shape", mappedBy="groups", fetch="EXTRA_LAZY");
     * @ORM\OrderBy({"created" = "DESC"})
     *
     * @JMS\Expose
     * @JMS\Groups({"GroupShape"})
     */
    protected $shapes;

    /**
     * @ORM\ManyToMany(targetEntity="NaturaPass\LoungeBundle\Entity\Lounge", mappedBy="groups", fetch="EXTRA_LAZY");
     * @ORM\OrderBy({"meetingDate" = "DESC"})
     *
     * @JMS\Expose
     * @JMS\Groups({"GroupLounge"})
     */
    protected $lounges;

    /**
     *
     * @var \Doctrine\Common\Collections\ArrayCollection|\NaturaPass\GroupBundle\Entity\GroupInvitation[]
     *
     * @ORM\OneToMany(targetEntity="NaturaPass\GroupBundle\Entity\GroupInvitation", mappedBy="group", cascade={"persist", "remove"}, fetch="EXTRA_LAZY", orphanRemoval=true)
     *
     * @JMS\Expose
     * @JMS\Groups({"GroupInvitation"})
     */
    protected $inviteEmail;

    /**
     * @var \Doctrine\Common\Collections\ArrayCollection|\NaturaPass\GroupBundle\Entity\GroupMessage[]
     * @ORM\OneToMany(targetEntity="NaturaPass\GroupBundle\Entity\GroupMessage", mappedBy="group", cascade={"persist", "remove"}, fetch="EXTRA_LAZY", orphanRemoval=true)
     *
     * @JMS\Expose
     * @JMS\Groups({"GroupMessages"})
     */
    protected $messages;

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
     * Get grouptag
     *
     * @return string
     */
    public function getGrouptag()
    {
        return $this->grouptag;
    }

    /**
     * Set name
     *
     * @param string $grouptag
     * @return Group
     */
    public function setGrouptag($grouptag)
    {
        $this->grouptag = $grouptag;
        return $this;
    }

    /**
     * Set name
     *
     * @param string $name
     * @return Group
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
     * Set description
     *
     * @param string $description
     * @return Group
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
     * Constructor
     */
    public function __construct()
    {
        $this->subscribers = new ArrayCollection();
        $this->publications = new ArrayCollection();
        $this->favorites = new ArrayCollection();
        $this->shapes = new ArrayCollection();
        $this->lounges = new ArrayCollection();
        $this->messages = new ArrayCollection();
        $this->notifications = new ArrayCollection();
        $this->emails = new ArrayCollection();
    }

    /**
     * Set owner
     *
     * @param \NaturaPass\UserBundle\Entity\User $owner
     * @return Group
     */
    public function setOwner(User $owner)
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
     * Add subscriber
     *
     * @param \NaturaPass\GroupBundle\Entity\GroupUser $subscriber
     * @return Group
     */
    public function addSubscriber(GroupUser $subscriber)
    {
        $this->subscribers[] = $subscriber;

        return $this;
    }

    /**
     * Remove subscriber
     *
     * @param \NaturaPass\GroupBundle\Entity\GroupUser $subscriber
     */
    public function removeSubscriber(GroupUser $subscriber)
    {
        $this->subscribers->removeElement($subscriber);
    }

    /**
     * Get subscribers
     *
     * @param array $access
     * @param boolean $arrayOfUsers
     *
     * @return \Doctrine\Common\Collections\ArrayCollection
     */
    public function getSubscribers($access = array(GroupUser::ACCESS_DEFAULT, GroupUser::ACCESS_ADMIN), $arrayOfUsers = false)
    {
        $users = new ArrayCollection();

        $filter = $this->subscribers->filter(function ($element) use ($access, $users) {
            if (in_array($element->getAccess(), $access)) {
                $users->add($element->getUser());
                return true;
            }

            return false;
        });

        return $arrayOfUsers ? $users : $filter;
    }

    /**
     * Get subscribers
     *
     * @param array $access
     * @param boolean $arrayOfUsers
     *
     * @return \Doctrine\Common\Collections\ArrayCollection
     */
    public function getSubscribersAllow($access = array(GroupUser::ACCESS_DEFAULT, GroupUser::ACCESS_ADMIN), $arrayOfUsers = false)
    {
        $users = new ArrayCollection();

        $filter = $this->subscribers->filter(function ($element) use ($access, $users) {
            $check = true;
            if ($this->getAllowShow() == Group::ALLOW_ADMIN && $element->getAccess() != GroupUser::ACCESS_ADMIN) {
                $check = false;
            }
            if ($check && in_array($element->getAccess(), $access)) {
                $users->add($element->getUser());
                return true;
            }

            return false;
        });

        return $arrayOfUsers ? $users : $filter;
    }

    /**
     * Get emailable subscribers
     *
     * @return \Doctrine\Common\Collections\ArrayCollection|\NaturaPass\UserBundle\Entity\User[]
     */
    public function getEmailableSubscribers()
    {
        $users = new ArrayCollection();
        $access = array(GroupUser::ACCESS_DEFAULT, GroupUser::ACCESS_ADMIN);

        $this->subscribers->filter(function ($element) use ($access, $users) {
            if (in_array($element->getAccess(), $access) && $element->getMailable()==1) {
                $users->add($element->getUser());
                return true;
            }

            return false;
        });

        return $users;
    }

    /**
     * Add notification
     *
     * @param \NaturaPass\GroupBundle\Entity\GroupNotification $notification
     * @return Group
     */
    public function addNotification(GroupNotification $notification)
    {
        $this->notifications->add($notification);
        $notification->setGroup($this);

        return $this;
    }

    /**
     * Remove notification
     *
     * @param \NaturaPass\GroupBundle\Entity\GroupNotification $notification
     */
    public function removeNotification(GroupNotification $notification)
    {
        $this->notifications->removeElement($notification);
    }

    /**
     * Get notifications
     *
     * @return \Doctrine\Common\Collections\ArrayCollection|\NaturaPass\GroupBundle\Entity\GroupNotification[]
     */
    public function getNotifications()
    {
        return $this->notifications;
    }

    /**
     * Retourne la préférence des notifications sauvegardé pour l'utilisateur
     *
     * @param string $type
     *
     * @return GroupNotification|null
     */
    public function getNotificationByType($type)
    {
        foreach ($this->notifications as $notification) {
            if ($notification->getType() === $type) {
                return $notification;
            }
        }

        return null;
    }

    /**
     * Retourne la préférence des notifications sauvegardé pour l'utilisateur
     *
     * @param string $id
     *
     */
    public function getNotificationById($id)
    {
        foreach ($this->notifications as $notification) {
            if ($notification->getGroup()->getId() === $id) {
                return $notification;
            }
        }

        return null;
    }

    /**
     * Add email
     *
     * @param \NaturaPass\GroupBundle\Entity\GroupEmail $email
     * @return Group
     */
    public function addEmail(GroupEmail $email)
    {
        $this->emails->add($email);
        $email->setGroup($this);

        return $this;
    }

    /**
     * Remove email
     *
     * @param \NaturaPass\GroupBundle\Entity\GroupEmail $email
     */
    public function removeEmail(GroupEmail $email)
    {
        $this->emails->removeElement($email);
    }

    /**
     * Get emails
     *
     * @return \Doctrine\Common\Collections\ArrayCollection
     */
    public function getEmails()
    {
        return $this->emails;
    }

    /**
     * Retourne la préférence d'email sauvegardé pour l'utilisateur
     *
     * @param string $type
     *
     * @return GroupEmail|null
     */
    public function getEmailByType($type)
    {
        foreach ($this->emails as $email) {
            if ($email->getType() === $type) {
                return $email;
            }
        }

        return null;
    }

    /**
     * Set created
     *
     * @param \DateTime $created
     * @return Group
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
     * @return Group
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
     * Set access
     *
     * @param integer $access
     * @return Group
     */
    public function setAccess($access)
    {
        $this->access = $access;

        return $this;
    }

    /**
     * Get access
     *
     * @return integer
     */
    public function getAccess()
    {
        return $this->access;
    }

    /**
     * Set add
     *
     * @param integer $allowAdd
     * @return Group
     */
    public function setAllowAdd($allowAdd)
    {
        $this->allowAdd = $allowAdd;

        return $this;
    }

    /**
     * Get add
     *
     * @return int
     */
    public function getAllowAdd()
    {
        return $this->allowAdd;
    }

    /**
     * Set show
     *
     * @param integer $allowShow
     * @return Group
     */
    public function setAllowShow($allowShow)
    {
        $this->allowShow = $allowShow;

        return $this;
    }

    /**
     * Get show
     *
     * @return int
     */
    public function getAllowShow()
    {
        return $this->allowShow;
    }


    /**
     * Set add
     *
     * @param integer $allowAddChat
     * @return Group
     */
    public function setAllowAddChat($allowAddChat)
    {
        $this->allowAddChat = $allowAddChat;

        return $this;
    }

    /**
     * Get add
     *
     * @return int
     */
    public function getAllowAddChat()
    {
        return $this->allowAddChat;
    }

    /**
     * Set show
     *
     * @param integer $allowShowChat
     * @return Group
     */
    public function setAllowShowChat($allowShowChat)
    {
        $this->allowShowChat = $allowShowChat;

        return $this;
    }

    /**
     * Get show
     *
     * @return int
     */
    public function getAllowShowChat()
    {
        return $this->allowShowChat;
    }


    /**
     * Set photo
     *
     * @param \NaturaPass\GroupBundle\Entity\GroupMedia $photo
     * @return Group
     */
    public function setPhoto(GroupMedia $photo = null)
    {
        $this->photo = $photo;

        return $this;
    }

    /**
     * Get photo
     *
     * @return \NaturaPass\GroupBundle\Entity\GroupMedia
     */
    public function getPhoto()
    {
        return $this->photo;
    }

    /**
     * Remove publication
     *
     * @param \NaturaPass\PublicationBundle\Entity\Publication $publication
     */
    public function removePublications(Publication $publication)
    {
        $this->publications->removeElement($publication);
    }

    /**
     * Remove publication
     *
     */
    public function removeAllPublications()
    {
        $this->publications = new ArrayCollection();
    }

    /**
     * Get publication
     *
     * @return \Doctrine\Common\Collections\ArrayCollection|\NaturaPass\PublicationBundle\Entity\Publication[]
     */
    public function getPublications()
    {
        return $this->publications;
    }

    /**
     * Remove favorite
     *
     * @param \NaturaPass\PublicationBundle\Entity\Favorite $favorite
     */
    public function removeFavorites(Favorite $favorite)
    {
        $this->favorites->removeElement($favorite);
    }

    /**
     * Remove favorite
     *
     */
    public function removeAllFavorites()
    {
        $this->favorites = new ArrayCollection();
    }

    /**
     * Get favorite
     *
     * @return \Doctrine\Common\Collections\ArrayCollection|\NaturaPass\PublicationBundle\Entity\Favorite[]
     */
    public function getFavorites()
    {
        return $this->favorites;
    }

    /**
     * Remove publication
     *
     * @param \NaturaPass\MainBundle\Entity\Shape $shape
     */
    public function removeShapes(Shape $shape)
    {
        $this->shapes->removeElement($shape);
    }

    /**
     * Remove publication
     *
     */
    public function removeAllShapes()
    {
        $this->shapes = new ArrayCollection();
    }

    /**
     * Get shapes
     *
     * @return \Doctrine\Common\Collections\ArrayCollection|\NaturaPass\MainBundle\Entity\Shape[]
     */
    public function getShapes()
    {
        return $this->shapes;
    }

    /**
     * Add Hunt
     *
     * @param \NaturaPass\LoungeBundle\Entity\Lounge $lounge
     * @return Group
     */
    public function addLounge(Lounge $lounge)
    {
        $this->lounges[] = $lounge;

        return $this;
    }

    /**
     * Remove lounge
     *
     * @param \NaturaPass\LoungeBundle\Entity\Lounge $lounge
     */
    public function removeLounges(Lounge $lounge)
    {
        $this->lounges->removeElement($lounge);
    }

    /**
     * Remove lounges
     *
     */
    public function removeAllLounges()
    {
        $this->lounges = new ArrayCollection();
    }

    /**
     * Get lounges
     *
     * @return \Doctrine\Common\Collections\ArrayCollection|\NaturaPass\LoungeBundle\Entity\Lounge[]
     */
    public function getLounges()
    {
        $iterator = $this->lounges->getIterator();
        $iterator->uasort(function ($a, $b) {
            return ($a->getMeetingDate() < $b->getMeetingDate()) ? -1 : 1;
        });
        $hunts = new ArrayCollection(iterator_to_array($iterator));

        return $hunts;
    }

    /**
     * Get hunts
     *
     * @return ArrayCollection|\NaturaPass\LoungeBundle\Entity\Lounge[]
     */
    public function hasHunt(Lounge $hunt)
    {
        return $this->lounges->filter(function (Lounge $lounge) use ($hunt) {
            return $lounge->getId() == $hunt->getId();
        })->first();
    }

    /**
     * is admin
     *
     * @@param User $user
     *
     * @return integer
     */
    public function isAdmin(User $user)
    {
        return count($this->subscribers->filter(function ($element) use ($user) {
            return $element->getAccess() == GroupUser::ACCESS_ADMIN && $element->getUser() == $user;
        }));
    }

    /**
     * @return \Doctrine\Common\Collections\ArrayCollection|\NaturaPass\UserBundle\Entity\User[]
     */
    public function getAdmins()
    {
        $admins = new ArrayCollection();

        foreach ($this->subscribers as $subscriber) {
            if ($subscriber->getAccess() === GroupUser::ACCESS_ADMIN) {
                $admins->add($subscriber->getUser());
            }
        }

        return $admins;
    }

    public function getAdminsName()
    {
        $admins = array();

        foreach ($this->subscribers as $subscriber) {
            if ($subscriber->getAccess() === GroupUser::ACCESS_ADMIN) {
                $admins[] = $subscriber->getUser()->getFullName();
            }
        }

        return join(', ', $admins);
    }

    /**
     * @param User $user
     * @return string
     */
    public function getAdminsNameWithoutMe(User $user)
    {
        $admins = array();

        foreach ($this->subscribers as $subscriber) {
            if ($subscriber->getAccess() === GroupUser::ACCESS_ADMIN && $user != $subscriber->getUser()) {
                $admins[] = $subscriber->getUser()->getFirstname() . " " . $subscriber->getUser()->getLastname();
            }
        }

        return join(', ', $admins);
    }

    /**
     * @param $user
     * @param array $access
     * @return GroupUser|boolean
     */
    public function isSubscriber($user, $access = array(GroupUser::ACCESS_DEFAULT, GroupUser::ACCESS_ADMIN))
    {
        return $this->subscribers->filter(function (GroupUser $subscriber) use ($user, $access) {
            return $subscriber->getUser() == $user && in_array($subscriber->getAccess(), $access);
        })->first();
    }

    /**
     * @param $user
     * @return bool|GroupUser
     */
    public function getSubscriber($user)
    {
        foreach ($this->subscribers as $subscriber) {
            if ($user instanceof User && $user->getId() == $subscriber->getUser()->getId()) {
                return $subscriber;
            }
        }

        return (object)array();
    }

    /**
     * @param $user
     * @param array $access
     * @return int
     */
    public function isWaitingValidation($user, $access = array(GroupUser::ACCESS_RESTRICTED))
    {
        if ($this->isSubscriber($user, array(GroupUser::ACCESS_ADMIN))) {
            return count($this->subscribers->filter(function ($element) use ($access) {
                return in_array($element->getAccess(), $access);
            }));
        }

        return 0;
    }

    /**
     * @param $friends
     * @return ArrayCollection
     */
    public function getFriendNotIn($friends)
    {
        $members = array();

        foreach ($this->subscribers as $subscriber) {
            if ($subscriber->getAccess() === GroupUser::ACCESS_ADMIN || $subscriber->getAccess() === GroupUser::ACCESS_DEFAULT) {
                $members[] = $subscriber->getUser();
            }
        }
        $array = array_diff($friends->toArray(), $members);


        return new ArrayCollection($array);
    }

    /**
     * Add message
     *
     * @param \NaturaPass\GroupBundle\Entity\GroupMessage $message
     * @return Group
     */
    public function addMessage(GroupMessage $message)
    {
        $this->messages[] = $message;

        return $this;
    }

    /**
     * Remove message
     *
     * @param \NaturaPass\GroupBundle\Entity\GroupMessage $message
     */
    public function removeMessage(GroupMessage $message)
    {
        $this->messages->removeElement($message);
    }

    /**
     * Get messages
     *
     * @return  \Doctrine\Common\Collections\ArrayCollection|\NaturaPass\GroupBundle\Entity\GroupMessage[]
     */
    public function getMessages()
    {
        return $this->messages;
    }

    /**
     * Get last message
     *
     * @return  \NaturaPass\GroupBundle\Entity\GroupMessage
     */
    public function getLastMessage()
    {
        return $this->messages->last();
    }

    public function getLastUpdated()
    {
        $updated = $this->getUpdated();
        return $updated;
    }

    public function checkAllowAdd(User $user)
    {
        return ($this->getAllowAdd() == Group::ALLOW_ADMIN && !$this->isSubscriber($user, array(GroupUser::ACCESS_ADMIN))) ? false : true;
    }


    public function checkAllowAddChat(User $user)
    {
        return ($this->getAllowAddChat() == Group::ALLOW_ADMIN && !$this->isSubscriber($user, array(GroupUser::ACCESS_ADMIN))) ? false : true;
    }
}
