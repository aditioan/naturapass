<?php

namespace NaturaPass\LoungeBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation as JMS;
use Gedmo\Mapping\Annotation as ORMExtension;
use NaturaPass\MainBundle\Entity\Geolocation;
use NaturaPass\PublicationBundle\Entity\Favorite;
use NaturaPass\UserBundle\Entity\User;
use NaturaPass\GroupBundle\Entity\Group;
use NaturaPass\PublicationBundle\Entity\Publication;

/**
 * Lounge
 *
 * @ORM\Table()
 * @ORM\Entity()
 * @ORM\HasLifecycleCallbacks
 * @JMS\ExclusionPolicy("all")
 */
class Lounge
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
     * @JMS\Groups({"LoungeDetail", "LoungeLess"})
     */
    protected $id;

    /**
     * @var string
     *
     * @ORM\Column(name="name", type="string", length=255)
     *
     * @JMS\Expose
     * @JMS\Groups({"LoungeDetail", "LoungeLess"})
     */
    protected $name;

    /**
     * @var string
     *
     * @ORM\Column(name="invitePersons", type="string", length=255, nullable=true)
     *
     * @JMS\Expose
     * @JMS\Groups({"LoungeDetail", "LoungeLess"})
     */
    protected $invitePersons;

    /**
     * @var string
     *
     * @ORM\Column(name="description", type="text", nullable=true)
     *
     * @JMS\Expose
     * @JMS\Groups({"LoungeDetail", "LoungeLess"})
     */
    protected $description;

    /**
     * @var integer
     *
     * @ORM\Column(name="access", type="integer")
     *
     * @JMS\Expose
     * @JMS\Groups({"LoungeDetail", "LoungeLess"})
     */
    protected $access = Lounge::ACCESS_SEMIPROTECTED;

    /**
     * @var integer
     *
     * @ORM\Column(name="allow_add", type="integer", options={"default" = 1})
     *
     * @JMS\Expose
     * @JMS\Groups({"LoungeDetail"})
     */
    protected $allowAdd = Lounge::ALLOW_ALL_MEMBERS;

    /**
     * @var integer
     *
     * @ORM\Column(name="allow_show", type="integer", options={"default" = 1})
     *
     * @JMS\Expose
     * @JMS\Groups({"LoungeDetail"})
     */
    protected $allowShow = Lounge::ALLOW_ALL_MEMBERS;

    /**
     * @var integer
     *
     * @ORM\Column(name="allow_add_chat", type="integer", options={"default" = 1})
     *
     * @JMS\Expose
     * @JMS\Groups({"LoungeDetail"})
     */
    protected $allowAddChat = Lounge::ALLOW_ALL_MEMBERS;

    /**
     * @var integer
     *
     * @ORM\Column(name="allow_show_chat", type="integer", options={"default" = 1})
     *
     * @JMS\Expose
     * @JMS\Groups({"LoungeDetail"})
     */
    protected $allowShowChat = Lounge::ALLOW_ALL_MEMBERS;

    /**
     * @var boolean
     *
     * @ORM\Column(name="geolocation", type="boolean", options={"default"=false})
     *
     * @JMS\Expose
     * @JMS\Groups({"LoungeDetail", "LoungeLess"})
     */
    protected $geolocation = false;

    /**
     *
     * @var \DateTime
     *
     * @ORM\Column(name="meetingDate", type="datetime", nullable=true)
     *
     * @JMS\Expose
     * @JMS\Groups({"LoungeDetail", "LoungeLess"})
     */
    protected $meetingDate;

    /**
     * @var Geolocation
     *
     * @ORM\ManyToOne(targetEntity="NaturaPass\MainBundle\Entity\Geolocation", cascade={"persist", "remove"})
     *
     * @JMS\Expose
     * @JMS\Groups({"PublicationDetail"})
     */
    protected $meetingAddress;

    /**
     * @ var \DateTime
     *
     * @ORM\Column(name="endDate", type="datetime")
     *
     * @JMS\Expose
     * @JMS\Groups({"LoungeDetail", "LoungeLess"})
     */
    protected $endDate;

    /**
     * @var string
     *
     * @ORMExtension\Slug(fields={"name"}, updatable=false)
     * @ORM\Column(name="loungetag", type="string", length=255)
     *
     * @JMS\Expose
     * @JMS\Groups({"LoungeDetail", "LoungeLess"})
     */
    protected $loungetag;

    /**
     *
     * @var User
     *
     * @ORM\ManyToOne(targetEntity="NaturaPass\UserBundle\Entity\User", inversedBy="loungesOwner")
     * @ORM\JoinColumn(nullable=false)
     *
     * @JMS\Expose
     * @JMS\Groups({"LoungeDetail", "LoungeLess"})
     */
    protected $owner;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="created", type="datetime")
     * @ORMExtension\Timestampable(on="create")
     *
     * @JMS\Expose
     * @JMS\Groups({"LoungeDetail"})
     */
    protected $created;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="updated", type="datetime")
     * @ORMExtension\Timestampable(on="update")
     *
     * @JMS\Expose
     * @JMS\Groups({"LoungeDetail"})
     */
    protected $updated;

    /**
     * @var \Doctrine\Common\Collections\ArrayCollection|\NaturaPass\LoungeBundle\Entity\LoungeMessage[]
     * @ORM\OneToMany(targetEntity="NaturaPass\LoungeBundle\Entity\LoungeMessage", mappedBy="lounge", cascade={"persist", "remove"}, fetch="EXTRA_LAZY", orphanRemoval=true)
     *
     * @JMS\Expose
     * @JMS\Groups({"LoungeMessages"})
     */
    protected $messages;

    /**
     *
     * @var \Doctrine\Common\Collections\ArrayCollection|\NaturaPass\LoungeBundle\Entity\LoungeUser[]
     *
     * @ORM\OneToMany(targetEntity="NaturaPass\LoungeBundle\Entity\LoungeUser", mappedBy="lounge", cascade={"persist", "remove"}, fetch="EXTRA_LAZY", orphanRemoval=true)
     *
     * @JMS\Expose
     * @JMS\Groups({"LoungeSubscribers"})
     */
    protected $subscribers;

    /**
     *
     * @var \Doctrine\Common\Collections\ArrayCollection|\NaturaPass\LoungeBundle\Entity\LoungeNotMember[]
     *
     * @ORM\OneToMany(targetEntity="NaturaPass\LoungeBundle\Entity\LoungeNotMember", mappedBy="lounge", cascade={"persist", "remove"}, fetch="EXTRA_LAZY", orphanRemoval=true)
     *
     * @JMS\Expose
     * @JMS\Groups({"LoungeSubscribersNotMember"})
     */
    protected $subscribersNotMember;

    /**
     *
     * @var \Doctrine\Common\Collections\ArrayCollection|\NaturaPass\LoungeBundle\Entity\LoungeInvitation[]
     *
     * @ORM\OneToMany(targetEntity="NaturaPass\LoungeBundle\Entity\LoungeInvitation", mappedBy="lounge", cascade={"persist", "remove"}, fetch="EXTRA_LAZY", orphanRemoval=true)
     *
     * @JMS\Expose
     * @JMS\Groups({"LoungeInvitation"})
     */
    protected $inviteEmail;

    /**
     * @ORM\OneToOne(targetEntity="NaturaPass\LoungeBundle\Entity\LoungeMedia", cascade={"persist", "remove"}, orphanRemoval=true)
     * @ORM\JoinColumn(nullable=true)
     *
     * @JMS\Expose
     * @JMS\Groups({"LoungePhoto"})
     */
    protected $photo;

    /**
     * @ORM\ManyToMany(targetEntity="NaturaPass\PublicationBundle\Entity\Publication", mappedBy="hunts", fetch="EXTRA_LAZY");
     * @ORM\OrderBy({"created" = "DESC"})
     *
     * @JMS\Expose
     * @JMS\Groups({"LoungePublication"})
     */
    protected $publications;

    /**
     * @ORM\ManyToMany(targetEntity="NaturaPass\PublicationBundle\Entity\Favorite", mappedBy="hunts", fetch="EXTRA_LAZY");
     * @ORM\OrderBy({"created" = "DESC"})
     *
     * @JMS\Expose
     * @JMS\Groups({"LoungePublication"})
     */
    protected $favorites;

    /**
     * @ORM\ManyToMany(targetEntity="NaturaPass\MainBundle\Entity\Shape", mappedBy="hunts", fetch="EXTRA_LAZY");
     * @ORM\OrderBy({"created" = "DESC"})
     *
     * @JMS\Expose
     * @JMS\Groups({"LoungeShape"})
     */
    protected $shapes;

    /**
     * @var ArrayCollection
     *
     * @ORM\ManyToMany(targetEntity="NaturaPass\GroupBundle\Entity\Group", inversedBy="lounges", fetch="EXTRA_LAZY");
     * @ORM\JoinTable(name="group_has_lounge")
     *
     * @JMS\Expose
     * @JMS\Groups({"LoungeGroup"})
     */
    protected $groups;

    /**
     * @param $user
     * @param array $access
     *
     * @return LoungeUser|boolean
     */
    public function isSubscriber($user, $access = array(LoungeUser::ACCESS_DEFAULT, LoungeUser::ACCESS_ADMIN))
    {
        return $this->subscribers->filter(function ($element) use ($user, $access) {
            return $element->getUser()->getId() == $user->getId() && in_array($element->getAccess(), $access);
        })->first();
    }

    /**
     * @param $id
     *
     * @return LoungeNotMember|boolean
     */
    public function isNotMember($id)
    {
        return $this->subscribersNotMember->filter(function ($element) use ($id) {
            return $element->getId() == $id;
        })->first();
    }

    public function isWaitingValidation($user, $access = array(LoungeUser::ACCESS_RESTRICTED))
    {
        if ($this->isSubscriber($user, array(LoungeUser::ACCESS_ADMIN))) {
            return count($this->subscribers->filter(function ($element) use ($access) {
                return in_array($element->getAccess(), $access);
            }));
        }

        return 0;
    }

    public function isSubscriberParticipation($user, $access = array(LoungeUser::ACCESS_DEFAULT, LoungeUser::ACCESS_ADMIN))
    {
        $participation = LoungeUser::PARTICIPATION_NO;
        foreach ($this->subscribers->filter(function ($element) use ($user, $access) {
            return $element->getUser() == $user && in_array($element->getAccess(), $access);
        }) as $subscriber) {
            $participation = $subscriber->getParticipation();
        }
        return $participation;
    }

    public function isSubscriberGeolocation($user, $access = array(LoungeUser::ACCESS_DEFAULT, LoungeUser::ACCESS_ADMIN))
    {
        $geolocation = false;
        foreach ($this->subscribers->filter(function ($element) use ($user, $access) {
            return $element->getUser() == $user && in_array($element->getAccess(), $access);
        }) as $subscriber) {
            $geolocation = $subscriber->getGeolocation();
        }
        return $geolocation;
    }

    public function isSubscriberPublicComment($user, $access = array(LoungeUser::ACCESS_DEFAULT, LoungeUser::ACCESS_ADMIN))
    {
        $public = false;
        foreach ($this->subscribers->filter(function ($element) use ($user, $access) {
            return $element->getUser() == $user && in_array($element->getAccess(), $access);
        }) as $subscriber) {
            $public = $subscriber->getPublicComment();
        }
        return $public;
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
     * Set name
     *
     * @param string $name
     * @return Lounge
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
     * @return Lounge
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
     * Set access
     *
     * @param integer $access
     * @return Lounge
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
     * @return Lounge
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
     * @return Lounge
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
     * @return Lounge
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
     * @return Lounge
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
     * Get endDate
     *
     * @return \DateTime
     */
    public function getEndDate()
    {
        return $this->endDate;
    }

    /**
     * Set endDate
     *
     * @param \DateTime $endDate
     * @return Lounge
     */
    public function setEndDate($endDate)
    {
        $this->endDate = $endDate;
        return $this;
    }

    /**
     * Set owner
     *
     * @param \NaturaPass\UserBundle\Entity\User $owner
     * @return Lounge
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
     * @return Geolocation
     */
    public function getMeetingAddress()
    {
        return $this->meetingAddress;
    }

    /**
     * @param Geolocation $meetingAddress
     * @return $this
     */
    public function setMeetingAddress(Geolocation $meetingAddress)
    {
        $this->meetingAddress = $meetingAddress;
        return $this;
    }

    /**
     * Get meetingDate
     *
     * @return \DateTime
     */
    public function getMeetingDate()
    {
        return $this->meetingDate;
    }

    /**
     * Set meetingDate
     *
     * @param \DateTime $meetingDate
     * @return Lounge
     */
    public function setMeetingDate($meetingDate)
    {
        $this->meetingDate = $meetingDate;
        return $this;
    }

    /**
     * @return string
     */
    public function getLoungetag()
    {
        return $this->loungetag;
    }

    /**
     * @param string $loungetag
     * @return $this
     */
    public function setLoungetag($loungetag)
    {
        $this->loungetag = $loungetag;
        return $this;
    }

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->groups = new ArrayCollection();
        $this->subscribers = new ArrayCollection();
        $this->subscribersNotMember = new ArrayCollection();
        $this->publications = new ArrayCollection();
        $this->favorites = new ArrayCollection();
        $this->shapes = new ArrayCollection();
    }

    /**
     * Add subscribers
     *
     * @param \NaturaPass\LoungeBundle\Entity\LoungeUser $subscribers
     * @return Lounge
     */
    public function addSubscriber(LoungeUser $subscribers)
    {
        $this->subscribers[] = $subscribers;

        return $this;
    }

    /**
     * Add not-member
     *
     * @param \NaturaPass\LoungeBundle\Entity\LoungeNotMember $subscribersNotMember
     * @return Lounge
     */
    public function addSubscriberNotMember(LoungeNotMember $subscribersNotMember)
    {
        $this->subscribersNotMember[] = $subscribersNotMember;

        return $this;
    }

    /**
     * Remove subscribers
     *
     * @param \NaturaPass\LoungeBundle\Entity\LoungeUser $subscribers
     */
    public function removeSubscriber(LoungeUser $subscribers)
    {
        $this->subscribers->removeElement($subscribers);
    }

    /**
     * Remove not-member
     *
     * @param \NaturaPass\LoungeBundle\Entity\LoungeNotMember $subscribersNotMember
     */
    public function removeSubscriberNotMember(LoungeNotMember $subscribersNotMember)
    {
        $this->subscribersNotMember->removeElement($subscribersNotMember);
    }

    /**
     * @param \Doctrine\Common\Collections\ArrayCollection $friends
     * @return \Doctrine\Common\Collections\ArrayCollection|\NaturaPass\UserBundle\Entity\User[]
     */
    public function getFriendNotIn($friends)
    {
        $members = array();

        foreach ($this->subscribers as $subscriber) {
            if ($subscriber->getAccess() === LoungeUser::ACCESS_ADMIN || $subscriber->getAccess() === LoungeUser::ACCESS_DEFAULT) {
                $members[] = $subscriber->getUser();
            }
        }
        $array = array_diff($friends->toArray(), $members);

        return new ArrayCollection($array);
    }

    /**
     * @param User $user
     * @return int
     */
    public function isAdmin(User $user)
    {
        return count($this->subscribers->filter(function ($element) use ($user) {
            return $element->getAccess() === LoungeUser::ACCESS_ADMIN && $element->getUser() == $user;
        }));
    }

    /**
     * @return \Doctrine\Common\Collections\ArrayCollection|\NaturaPass\UserBundle\Entity\User[]
     */
    public function getAdmins()
    {
        $admins = new ArrayCollection();

        foreach ($this->subscribers as $subscriber) {
            if ($subscriber->getAccess() === LoungeUser::ACCESS_ADMIN) {
                $admins->add($subscriber->getUser());
            }
        }

        return $admins;
    }

    /**
     * @return string
     */
    public function getAdminsName()
    {
        $admins = array();

        foreach ($this->subscribers as $subscriber) {
            if ($subscriber->getAccess() === LoungeUser::ACCESS_ADMIN) {
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
            if ($subscriber->getAccess() === LoungeUser::ACCESS_ADMIN && $user != $subscriber->getUser()) {
                $admins[] = $subscriber->getUser()->getFirstname() . " " . $subscriber->getUser()->getLastname();
            }
        }
        return join(', ', $admins);
    }

    /**
     * Get subscribers
     *
     * @param array
     * @param boolean
     *
     * @return LoungeUser[]|ArrayCollection
     */
    public function getSubscribers($access = array(LoungeUser::ACCESS_DEFAULT, LoungeUser::ACCESS_ADMIN), $arrayOfUsers = false)
    {
        $users = new ArrayCollection();

        $filtered = $this->subscribers->filter(function ($element) use ($access, $users) {
            if (in_array($element->getAccess(), $access)) {
                $users->add($element->getUser());
                return true;
            }

            return false;
        });

        return $arrayOfUsers ? $users : $filtered;
    }

    /**
     * Get subscribers
     *
     * @param array
     * @param boolean
     *
     * @return LoungeUser[]|ArrayCollection
     */
    public function getNbParticipants()
    {
        $nb = 0;
        foreach ($this->subscribers as $loungeuser) {
            if ($loungeuser->getParticipation() == LoungeUser::PARTICIPATION_YES) {
                $nb++;
            }
        }
        foreach ($this->subscribersNotMember as $loungenotuser) {
            if ($loungenotuser->getParticipation() == LoungeUser::PARTICIPATION_YES) {
                $nb++;
            }
        }
        return $nb;
    }

    /**
     * Get not-members
     *
     * @param array|boolean $arrayOfUsers
     *
     * @return ArrayCollection
     */
    public function getSubscribersNotMember($arrayOfUsers = false)
    {

        if ($arrayOfUsers) {
            $users = new ArrayCollection();

            $this->subscribersNotMember->filter(
                function (LoungeNotMember $element) use ($users) {
                    $users->add($element->getId());
                    return true;
                }
            );

            return $users;
        }

        return $this->subscribersNotMember;
    }

    /**
     * Get subscribers
     *
     * @param User $user
     *
     * @return boolean|integer
     */
    public function getSubscriberAccess(User $user)
    {
        foreach ($this->subscribers as $subscriber) {
            if ($user == $subscriber->getUser()) {
                return $subscriber->getAccess();
            }
        }

        return false;
    }

    /**
     * Set geolocation
     *
     * @param boolean $geolocation
     * @return Lounge
     */
    public function setGeolocation($geolocation)
    {
        $this->geolocation = $geolocation;

        return $this;
    }

    /**
     * Get geolocation
     *
     * @return boolean
     */
    public function getGeolocation()
    {
        return $this->geolocation;
    }

    /**
     * Add message
     *
     * @param \NaturaPass\LoungeBundle\Entity\LoungeMessage $message
     * @return Lounge
     */
    public function addMessage(LoungeMessage $message)
    {
        $this->messages[] = $message;

        return $this;
    }

    /**
     * Remove message
     *
     * @param \NaturaPass\LoungeBundle\Entity\LoungeMessage $message
     */
    public function removeMessage(LoungeMessage $message)
    {
        $this->messages->removeElement($message);
    }

    /**
     * Get messages
     *
     * @return  \Doctrine\Common\Collections\ArrayCollection|\NaturaPass\LoungeBundle\Entity\LoungeMessage[]
     */
    public function getMessages()
    {
        return $this->messages;
    }

    /**
     * Get last message
     *
     * @return  \NaturaPass\LoungeBundle\Entity\LoungeMessage
     */
    public function getLastMessage()
    {
        return $this->messages->last();
    }

    /**
     * Set created
     *
     * @param \DateTime $created
     * @return Lounge
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
     * @return Lounge
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
     * @param \NaturaPass\LoungeBundle\Entity\LoungeMedia $photo
     * @return Lounge
     */
    public function setPhoto(LoungeMedia $photo = null)
    {
        $this->photo = $photo;

        return $this;
    }

    /**
     * Get photo
     *
     * @return \NaturaPass\LoungeBundle\Entity\LoungeMedia
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

//    /**
//     * Remove shape
//     *
//     * @param \NaturaPass\MainBundle\Entity\Shape $shape
//     */
//    public function removeShapes(Shape $shape)
//    {
//        $this->shapes->removeElement($shape);
//    }

    /**
     * Remove shapes
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
     * Get Groups
     *
     * @return ArrayCollection|\NaturaPass\GroupBundle\Entity\Group[]
     */
    public function hasGroup(Group $group)
    {
        return $this->groups->filter(function (Group $g) use ($group) {
            return $g->getId() == $group->getId();
        })->first();
    }

    /**
     * Add group
     *
     * @param \NaturaPass\GroupBundle\Entity\Group $group
     * @return Group
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
        $this->groups = new ArrayCollection();
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

    public function isLiveActive(User $connected)
    {
        $currentDate = new \DateTime();
        $loungeUser = $this->isSubscriber($connected);
        if (!is_null($loungeUser) && !is_null($this->getMeetingDate()) && !is_null($this->getEndDate()) && $this->getMeetingDate()->sub(new \DateInterval('P2D')) <= $currentDate && $this->getEndDate() >= $currentDate) {
            $this->getMeetingDate()->add(new \DateInterval('P2D'));
            return true;
        }
        return false;
    }

    public function getLastUpdated()
    {
        $updated = $this->getUpdated();
        return $updated;
    }

    public function checkAllowAdd(User $user)
    {
        return ($this->getAllowAdd() == Lounge::ALLOW_ADMIN && !$this->isSubscriber($user, array(LoungeUser::ACCESS_ADMIN))) ? false : true;
    }

    public function checkAllowAddChat(User $user)
    {
        return ($this->getAllowAddChat() == Lounge::ALLOW_ADMIN && !$this->isSubscriber($user, array(LoungeUser::ACCESS_ADMIN))) ? false : true;
    }

    /**
     * Get emailable subscribers
     *
     * @return \Doctrine\Common\Collections\ArrayCollection|\NaturaPass\UserBundle\Entity\User[]
     */
    public function getEmailableSubscribers()
    {
        $users = new ArrayCollection();
        $access = array(LoungeUser::ACCESS_DEFAULT, LoungeUser::ACCESS_ADMIN);

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
     * Set invitepersons
     *
     * @param string $invitepersons
     * @return Lounge
     */
    public function setInvitepersons($invitepersons)
    {
        $this->invitePersons = $invitepersons;

        return $this;
    }

    /**
     * Get invitepersons
     *
     * @return string
     */
    public function getInvitepersons()
    {
        return $this->invitePersons;
    }
}
