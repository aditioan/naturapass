<?php

namespace NaturaPass\UserBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use \Doctrine\Common\Collections\ArrayCollection;
use \Doctrine\Common\Collections\Criteria;
use JMS\Serializer\Annotation as JMS;
use Gedmo\Mapping\Annotation as ORMExtension;
use FOS\UserBundle\Entity\User as BaseUser;
use NaturaPass\GroupBundle\Entity\GroupUser;
use NaturaPass\LoungeBundle\Entity\LoungeUser;
use NaturaPass\MainBundle\Entity\Geolocation;
use NaturaPass\MainBundle\Entity\Shape;
use NaturaPass\NotificationBundle\Entity\NotificationReceiver;
use NaturaPass\PublicationBundle\Entity\Favorite;
use NaturaPass\PublicationBundle\Entity\Publication;
use NaturaPass\PublicationBundle\Entity\PublicationReport;
use Admin\GameBundle\Entity\Game;
use Admin\SentinelleBundle\Entity\Receiver;
use Symfony\Component\Validator\Constraints\DateTime;

/**
 * User
 *
 * @ORM\Table()
 * @ORM\Entity()
 * @ORM\HasLifecycleCallbacks
 *
 * @JMS\ExclusionPolicy("all")
 */
class User extends BaseUser
{

    const USERFRIENDWAY_BOTH = 1;
    const USERFRIENDWAY_USERTOFRIEND = 2;
    const USERFRIENDWAY_FRIENDTOUSER = 3;
    const COURTESY_UNDEFINED = 0;
    const COURTESY_MISTER = 1;
    const COURTESY_MADAM = 2;
    const DEFAULT_AVATAR = '/img/interface/default-avatar.jpg';

    /**
     * @var integer
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     *
     * @JMS\Expose
     * @JMS\Groups({"UserDetail", "UserLess"})
     */
    protected $id;

    /**
     * @var string
     *
     * @ORM\Column(name="facebook_id", type="string", length=255, nullable=true, unique=true)
     */
    protected $facebook_id;

    /**
     * @var integer
     *
     * @ORM\Column(name="courtesy", type="integer")
     *
     * @JMS\Expose
     * @JMS\Groups({"UserDetail"})
     */
    protected $courtesy = self::COURTESY_UNDEFINED;

    /**
     * @var string
     *
     * @ORM\Column(name="firstname", type="string", length=255)
     *
     * @JMS\Expose
     * @JMS\Groups({"UserDetail", "UserLess"})
     */
    protected $firstname;

    /**
     * @var string
     *
     * @ORM\Column(name="lastname", type="string", length=255)
     *
     * @JMS\Expose
     * @JMS\Groups({"UserDetail", "UserLess"})
     */
    protected $lastname;

    /**
     * @var string
     *
     * @ORMExtension\Slug(fields={"firstname", "lastname"}, updatable=false)
     * @ORM\Column(name="usertag", type="string", length=255, unique=true)
     *
     * @JMS\Expose
     * @JMS\Groups({"UserDetail", "UserLess"})
     */
    protected $usertag;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="birthday", type="datetime", nullable=true)
     * @JMS\Expose
     * @JMS\Groups({"UserDetail"})
     */
    protected $birthday;

    /**
     * @var string
     *
     * @ORM\Column(name="hometown", type="string", length=255, nullable=true)
     *
     * @JMS\Expose
     * @JMS\Groups({"UserDetail", "UserLess"})
     */
    protected $hometown;

    /**
     * @var string
     *
     * @ORM\Column(name="location", type="string", length=255, nullable=true)
     *
     * @JMS\Expose
     * @JMS\Groups({"UserDetail", "UserLess"})
     */
    protected $location;

    /**
     * @var string
     *
     * @ORM\Column(type="datetime", nullable=true)
     *
     * @JMS\Expose
     * @JMS\Groups({"UserDetail", "UserLess"})
     */
    private $last_activity;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="created", type="datetime")
     * @ORMExtension\Timestampable(on="create")
     *
     * @JMS\Expose
     * @JMS\Groups({"UserDetail"})
     */
    protected $created;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="updated", type="datetime")
     * @ORMExtension\Timestampable(on="update")
     *
     * @JMS\Expose
     * @JMS\Groups({"UserDetail"})
     */
    protected $updated;

    /**
     * @var UserFriend[]|ArrayCollection $userFriends
     *
     * @ORM\OneToMany(targetEntity="NaturaPass\UserBundle\Entity\UserFriend", mappedBy="user", fetch="EXTRA_LAZY", orphanRemoval=true)
     *
     * @JMS\Expose
     * @JMS\Groups({"UserFriends"})
     */
    protected $userFriends;

    /**
     * @var UserFriend[]|ArrayCollection $friendsUser
     *
     * @ORM\OneToMany(targetEntity="NaturaPass\UserBundle\Entity\UserFriend", mappedBy="friend", fetch="EXTRA_LAZY", orphanRemoval=true)
     *
     * @JMS\Expose
     * @JMS\Groups({"UserFriends"})
     */
    protected $friendsUser;

    /**
     * @ORM\OneToMany(targetEntity="NaturaPass\LoungeBundle\Entity\LoungeUser", mappedBy="user", fetch="EXTRA_LAZY", orphanRemoval=true)
     *
     * @JMS\Expose
     * @JMS\Groups({"UserLoungeSubscribes"})
     */
    protected $loungeSubscribes;

    /**
     * @ORM\OneToMany(targetEntity="NaturaPass\GroupBundle\Entity\GroupUser", mappedBy="user", fetch="EXTRA_LAZY", orphanRemoval=true)
     *
     * @JMS\Expose
     * @JMS\Groups({"UserGroupSubscribes"})
     *
     * @ORM\JoinTable(name="group_has_user")
     */
    protected $groupSubscribes;

    /**
     * @ORM\OneToMany(targetEntity="NaturaPass\GroupBundle\Entity\Group", mappedBy="owner", fetch="EXTRA_LAZY")
     *
     * @JMS\Expose
     * @JMS\Groups({"UserGroupsOwner"})
     */
    protected $groupsOwner;

    /**
     * @ORM\OneToMany(targetEntity="NaturaPass\LoungeBundle\Entity\Lounge", mappedBy="owner", fetch="EXTRA_LAZY")
     *
     * @JMS\Expose
     * @JMS\Groups({"UserLoungesOwner"})
     */
    protected $loungesOwner;

    /**
     * @ORM\OneToMany(targetEntity="NaturaPass\PublicationBundle\Entity\Favorite", mappedBy="owner", fetch="EXTRA_LAZY")
     *
     * @JMS\Expose
     * @JMS\Groups({"UserFavoritesOwner"})
     */
    protected $favoritesOwner;

    /**
     * @ORM\OneToMany(targetEntity="NaturaPass\UserBundle\Entity\UserMedia", mappedBy="owner", cascade={"persist"}, fetch="EXTRA_LAZY")
     *
     * @JMS\Expose
     * @JMS\Groups({"UserMedias"})
     */
    protected $medias;

    /**
     * @ORM\OneToOne(targetEntity="NaturaPass\UserBundle\Entity\Parameters", cascade={"persist", "remove"}, fetch="EXTRA_LAZY", orphanRemoval=true)
     * @ORM\JoinColumn(nullable=true)
     *
     * @JMS\Expose
     * @JMS\Groups({"UserParameters"})
     */
    protected $parameters;

    /**
     * @ORM\OneToMany(targetEntity="NaturaPass\UserBundle\Entity\DogParameter", mappedBy="owner", cascade={"persist"}, fetch="EXTRA_LAZY")
     *
     * @JMS\Expose
     * @JMS\Groups({"UserMedias"})
     */
    protected $dogs;

    /**
     * @ORM\OneToMany(targetEntity="NaturaPass\UserBundle\Entity\PaperParameter", mappedBy="owner", cascade={"persist"}, fetch="EXTRA_LAZY")
     *
     * @JMS\Expose
     * @JMS\Groups({"UserMedias"})
     */
    protected $papers;

    /**
     * @ORM\OneToMany(targetEntity="NaturaPass\UserBundle\Entity\HuntCityParameter", mappedBy="owner", cascade={"persist"}, fetch="EXTRA_LAZY")
     *
     * @JMS\Expose
     * @JMS\Groups({"UserMedias"})
     */
    protected $huntcities;

    /**
     * @ORM\OneToMany(targetEntity="NaturaPass\UserBundle\Entity\HuntCountryParameter", mappedBy="owner", cascade={"persist"}, fetch="EXTRA_LAZY")
     *
     * @JMS\Expose
     * @JMS\Groups({"UserMedias"})
     */
    protected $huntcountries;

    /**
     * @ORM\OneToMany(targetEntity="NaturaPass\UserBundle\Entity\HuntTypeParameter", mappedBy="owner", cascade={"persist"}, fetch="EXTRA_LAZY")
     *
     * @JMS\Expose
     * @JMS\Groups({"UserMedias"})
     */
    protected $hunttypes;

    /**
     * @var \Doctrine\Common\Collections\Collection
     *
     * @ORM\ManyToMany(targetEntity="NaturaPass\MainBundle\Entity\Geolocation", cascade={"persist", "remove"}, fetch="EXTRA_LAZY", orphanRemoval=true)
     * @ORM\JoinTable(name="user_has_geolocation")
     * @ORM\OrderBy({"created" = "DESC"})
     *
     * @JMS\Expose
     * @JMS\Groups({"UserGeolocations"})
     */
    protected $geolocations;

    /**
     * @ORM\OneToMany(targetEntity="NaturaPass\NotificationBundle\Entity\NotificationReceiver", mappedBy="receiver", fetch="EXTRA_LAZY", orphanRemoval=true)
     * @ORM\OrderBy({"updated" = "DESC", "state" = "ASC"})
     *
     * @JMS\Expose
     * @JMS\Groups({"UserNotifications"})
     */
    protected $notifications;

    /**
     * @var \Doctrine\Common\Collections\ArrayCollection|\NaturaPass\PublicationBundle\Entity\PublicationReport[]
     *
     * @ORM\OneToMany(targetEntity="NaturaPass\PublicationBundle\Entity\PublicationReport", mappedBy="user", cascade={"persist"}, fetch="EXTRA_LAZY", orphanRemoval=true)
     * @ORM\JoinColumn(nullable=true)
     *
     * @JMS\Expose
     * @JMS\Groups({"UserReports"})
     */
    protected $reports;

    /**
     * @var \Doctrine\Common\Collections\ArrayCollection|\NaturaPass\UserBundle\Entity\UserDevice[]
     *
     * @ORM\OneToMany(targetEntity="NaturaPass\UserBundle\Entity\UserDevice", mappedBy="owner", cascade={"persist"}, fetch="EXTRA_LAZY", orphanRemoval=true)
     *
     * @JMS\Expose
     * @JMS\Groups({"UserDevices"})
     */
    protected $devices;

    /**
     * @var \Doctrine\Common\Collections\ArrayCollection|\NaturaPass\UserBundle\Entity\UserMap[]
     *
     * @ORM\OneToMany(targetEntity="NaturaPass\UserBundle\Entity\UserMap", mappedBy="owner", cascade={"persist"}, fetch="EXTRA_LAZY", orphanRemoval=true)
     *
     * @JMS\Expose
     * @JMS\Groups({"UserMap"})
     */
    protected $maps;

    /**
     * @var \Doctrine\Common\Collections\ArrayCollection|\NaturaPass\UserBundle\Entity\UserAddress[]
     *
     * @ORM\OneToMany(targetEntity="NaturaPass\UserBundle\Entity\UserAddress", mappedBy="owner", cascade={"persist"}, fetch="EXTRA_LAZY", orphanRemoval=true)
     *
     * @JMS\Expose
     * @JMS\Groups({"UserAddresses"})
     */
    protected $addresses;

    /**
     * @var ArrayCollection
     *
     * @ORM\ManyToMany(targetEntity="NaturaPass\MessageBundle\Entity\Conversation", mappedBy="participants", fetch="EXTRA_LAZY")
     * @ORM\JoinTable(name="conversation_has_participant")
     * @ORM\OrderBy({"updated" = "DESC"})
     */
    protected $conversations;

    /**
     * @ORM\ManyToMany(targetEntity="Admin\SentinelleBundle\Entity\Receiver", mappedBy="users", fetch="EXTRA_LAZY");
     * @ORM\OrderBy({"name" = "ASC"})
     *
     * @JMS\Expose
     * @JMS\Groups({"UserAddresses"})
     */
    protected $receivers;

    /**
     * @var \Doctrine\Common\Collections\ArrayCollection|\NaturaPass\MainBundle\Entity\Shape[]
     *
     * @ORM\OneToMany(targetEntity="NaturaPass\MainBundle\Entity\Shape", mappedBy="owner", cascade={"persist", "remove"}, fetch="EXTRA_LAZY")
     *
     * @JMS\Expose
     * @JMS\Groups({"UserShapes"})
     */
    protected $shapes;

    /**
     * @var \Doctrine\Common\Collections\Collection
     *
     * @ORM\ManyToMany(targetEntity="NaturaPass\UserBundle\Entity\User",cascade={"persist", "remove"})
     * @ORM\JoinTable(name="user_is_locked",
     *      joinColumns={@ORM\JoinColumn(name="owner_id", referencedColumnName="id")},
     *      inverseJoinColumns={@ORM\JoinColumn(name="user_id", referencedColumnName="id")}
     *      )
     *
     * @ORM\OrderBy({"created" = "DESC"})
     *
     * @JMS\Expose
     * @JMS\Groups({"UserGeolocations"})
     */
    protected $locks;
     /**
     * @ORM\ManyToMany(targetEntity="NaturaPass\PublicationBundle\Entity\Publication", mappedBy="shareuser", fetch="EXTRA_LAZY");
     * @ORM\OrderBy({"created" = "DESC"})
     *
     * @JMS\Expose
     */
    protected $publications;

    /**
     * @ORM\ManyToMany(targetEntity="NaturaPass\PublicationBundle\Entity\Favorite", mappedBy="shareuser", fetch="EXTRA_LAZY");
     * @ORM\OrderBy({"created" = "DESC"})
     *
     * @JMS\Expose
     */
    protected $favorites;

    /**
     * @var string
     *
     * @ORM\Column(name="groupsarray", type="string", length=255, nullable=true)
     *
     * @JMS\Expose
     * @JMS\Groups({"UserDetail", "UserLess"})
     */
    protected $groupsarray;

    /**
     * @var string
     *
     * @ORM\Column(name="huntsarray", type="string", length=255, nullable=true)
     *
     * @JMS\Expose
     * @JMS\Groups({"UserDetail", "UserLess"})
     */
    protected $huntsarray;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->userFriends = new ArrayCollection();
        $this->friendsUser = new ArrayCollection();
        $this->publications = new ArrayCollection();
        $this->favorites = new ArrayCollection();
        $this->parameters = new Parameters();
        $this->parameters->setSharingFilter(3);
        $this->courtesy = self::COURTESY_UNDEFINED;
        $this->medias = new ArrayCollection();
        $this->reports = new ArrayCollection();
        $this->devices = new ArrayCollection();
        $this->maps = new ArrayCollection();
        $this->receivers = new ArrayCollection();
        $this->shapes = new ArrayCollection();
        $this->favoritesOwner = new ArrayCollection();
        $this->dogs = new ArrayCollection();
        $this->papers = new ArrayCollection();
        $this->huntcities = new ArrayCollection();
        $this->huntcountries = new ArrayCollection();
        $this->hunttypes = new ArrayCollection();
        $this->locks = new ArrayCollection();

        parent::__construct();
    }

    public function getLocked()
    {
        return $this->locked;
    }


    /**
     * @param string $facebook_id
     * @return User
     */
    public function setFacebook_id($facebook_id)
    {
        $this->facebook_id = $facebook_id;
        return $this;
    }

    /**
     * @return string
     */
    public function getFacebook_Id()
    {
        return $this->facebook_id;
    }

    public function setFacebookData($data, $registration = false)
    {
        $this->setFacebookId($data['id']);

        if ($registration) {
            $this->setEmail($data['email'])
                ->setUsername($data['email'])
                ->setFirstname($data['first_name'])
                ->setLastname($data['last_name'])
                ->addRole('ROLE_PENDING')
                ->setEnabled(true);

            !isset($data['birthday']) ?: $this->setBirthday(\DateTime::createFromFormat('m/d/Y', $data['birthday']));
            !isset($data['hometown']) ?: $this->setHometown($data['hometown']['name']);
            !isset($data['location']) ?: $this->setLocation($data['location']['name']);
            !isset($data['gender']) ?: $this->setCourtesy($data['gender'] == 'male' ? self::COURTESY_MISTER : self::COURTESY_MADAM);
        } else {
            if (isset($data['birthday']) && !$this->getBirthday()) {
                $this->setBirthday(\DateTime::createFromFormat('m/d/Y', $data['birthday']));
            }
            if (isset($data['hometown']) && !$this->getHometown()) {
                $this->setHometown($data['hometown']['name']);
            }
            if (isset($data['location']) && !$this->getLocation()) {
                $this->setLocation($data['location']['name']);
            }
            if (isset($data['gender']) && !$this->getCourtesy()) {
                $this->setCourtesy($data['gender'] == 'male' ? self::COURTESY_MISTER : self::COURTESY_MADAM);
            }
        }

        if (!$this->getPassword()) {
            $this->setPassword(md5(uniqid(rand(), true)));
        }

        $this->addRole('ROLE_FACEBOOK');

        return $this;
    }

    /**
     * Get notifications
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getNotifications()
    {
        $array = new ArrayCollection();
        foreach ($this->notifications as $notif) {
            if ($notif->getState() == \NaturaPass\NotificationBundle\Entity\NotificationReceiver::STATE_UNREAD && $notif->getNotification()->getVisible()) {
                $array->add($notif);
            }
        }
        return $array;
    }

    /**
     * Get notifications
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getAllNotifications()
    {
        $array = new ArrayCollection();
        foreach ($this->notifications as $notif) {
            if (!is_null($notif->getNotification()) && $notif->getNotification()->getVisible()) {
                $array->add($notif);
            }
        }
        return $array;
    }

    /**
     * Recherche un ami dans la liste d'amis
     *
     * @param \NaturaPass\UserBundle\Entity\User $user
     * @param array $allowedStates
     * @param integer $way
     *
     * @return mixed
     */
    public function hasFriendshipWith(User $user, array $allowedStates, $way = self::USERFRIENDWAY_BOTH)
    {
        if ($way == self::USERFRIENDWAY_BOTH || $way == self::USERFRIENDWAY_USERTOFRIEND) {
            foreach ($this->userFriends as $friend) {
                if ($friend->getFriend()->getId() == $user->getId() && in_array($friend->getState(), $allowedStates)) {
                    return array(self::USERFRIENDWAY_USERTOFRIEND, $friend);
                }
            }
        }

        if ($way == self::USERFRIENDWAY_BOTH || $way == self::USERFRIENDWAY_FRIENDTOUSER) {
            foreach ($this->friendsUser as $friend) {
                if ($friend->getUser()->getId() == $user->getId() && in_array($friend->getState(), $allowedStates)) {
                    return array(self::USERFRIENDWAY_FRIENDTOUSER, $friend);
                }
            }
        }

        return false;
    }

    /**
     * Recherche un ami dans la liste d'amis
     *
     * @param \NaturaPass\UserBundle\Entity\User $user
     * @param array $allowedStates
     * @param integer $way
     *
     * @return mixed
     */
    public function friendshipWithUpdate(User $user, array $allowedStates = array(UserFriend::ASKED, UserFriend::CONFIRMED, UserFriend::REJECTED), $way = self::USERFRIENDWAY_BOTH)
    {
        if ($way == self::USERFRIENDWAY_BOTH || $way == self::USERFRIENDWAY_USERTOFRIEND) {
            foreach ($this->userFriends as $friend) {
                if ($friend->getFriend()->getId() == $user->getId() && in_array($friend->getState(), $allowedStates)) {
                    return $friend->getUpdated();
                }
            }
        }

        if ($way == self::USERFRIENDWAY_BOTH || $way == self::USERFRIENDWAY_FRIENDTOUSER) {
            foreach ($this->friendsUser as $friend) {
                if ($friend->getUser()->getId() == $user->getId() && in_array($friend->getState(), $allowedStates)) {
                    return $friend->getUpdated();
                }
            }
        }

        return false;
    }

    /**
     * Retourne la dernière géolocalisation entrée pour l'utilisateur
     *
     * @return \NaturaPass\MainBundle\Entity\Geolocation|boolean
     */
    public function getLastGeolocation()
    {
        if (count($this->geolocations)) {
            return $this->geolocations->first();
        }

        return false;
    }

    /**
     * @return bool|UserMedia
     */
    public function getProfilePicture()
    {
        foreach ($this->medias as $media) {
            if ($media->getState() == \NaturaPass\UserBundle\Entity\UserMedia::STATE_PROFILE_PICTURE) {
                return $media;
            }
        }

        return false;
    }

    public function getFullName()
    {
        return $this->firstname . ' ' . $this->lastname;
    }

    /**
     * Retourne tous les amis d'un utilisateur
     *
     * @return User[]|ArrayCollection
     */
    public function getFriends($type = UserFriend::TYPE_BOTH, $state = UserFriend::CONFIRMED, $way = User::USERFRIENDWAY_BOTH)
    {
        $friends = new ArrayCollection();

        if ($way === self::USERFRIENDWAY_BOTH || $way === self::USERFRIENDWAY_USERTOFRIEND) {
            $this->userFriends->filter(function (UserFriend $element) use ($state, $friends) {
                if ($element->getState() === $state) {
                    $friends->set($element->getFriend()->getUsertag(), $element->getFriend());
                    return true;
                }
                return false;
            });
        }

        if ($way === self::USERFRIENDWAY_BOTH || $way === self::USERFRIENDWAY_FRIENDTOUSER) {
            $this->friendsUser->filter(function (UserFriend $element) use ($state, $friends) {
                if ($element->getState() === $state) {
                    $friends->set($element->getUser()->getUsertag(), $element->getUser());
                    return true;
                }
                return false;
            });
        }
        return $friends;
    }

    /**
     * Retourne tous les amis d'un utilisateur
     *
     * @return User[]|ArrayCollection
     */
    public function getFriendsId($type = UserFriend::TYPE_BOTH, $state = UserFriend::CONFIRMED, $way = User::USERFRIENDWAY_BOTH)
    {
        $friends = array();

        if ($way === self::USERFRIENDWAY_BOTH || $way === self::USERFRIENDWAY_USERTOFRIEND) {
            $this->userFriends->filter(function (UserFriend $element) use ($state, $friends) {
                if ($element->getState() === $state) {
                    $friends[] = $element->getUser()->getId();
//                    $friends->set($element->getUser()->getUsertag(), $element->getFriend());
                    return true;
                }
                return false;
            });
        }

        if ($way === self::USERFRIENDWAY_BOTH || $way === self::USERFRIENDWAY_FRIENDTOUSER) {
            $this->friendsUser->filter(function (UserFriend $element) use ($state, $friends) {
                if ($element->getState() === $state) {
                    $friends[] = $element->getUser()->getId();
//                    $friends->set($element->getUser()->getUsertag(), $element->getUser());
                    return true;
                }
                return false;
            });
        }

        return $friends;
    }

    /**
     * Retourne tous les amis en communs avec l'utilisateur passé en paramètre
     *
     * @param \NaturaPass\UserBundle\Entity\User $user
     * @return \Doctrine\Common\Collections\ArrayCollection
     */
    public function getMutualFriendsWith(User $user)
    {
        $user_friends = $user->getFriends();
        $own_friends = $this->getFriends();

        $mutualFriends = new ArrayCollection(array_intersect_key($own_friends->toArray(), $user_friends->toArray()));

        if ($mutualFriends->containsKey($user->getUsertag())) {
            $mutualFriends->remove($user->getUsertag());
        }

        if ($mutualFriends->containsKey($this->usertag)) {
            $mutualFriends->remove($this->usertag);
        }

        return $mutualFriends;
    }

    /**
     * Retourne une liste de personnes povant intéresser l'utilisateur
     *
     * @return \Doctrine\Common\Collections\ArrayCollection
     */
    public function getPropositionFriends()
    {
        $arrayFriends = new ArrayCollection();
        return $arrayFriends;
    }

    /**
     * Retourne le statut et le type entre deux utilisateurs s'ils sont amis, ou qu'une demande a été envoyé, sinon retourne false
     *
     * @param \NaturaPass\UserBundle\Entity\User $user
     * @param array $array
     *
     * @return \Doctrine\Common\Collections\ArrayCollection
     */
    public function getStateFriendsWith(User $user, $array = array(UserFriend::CONFIRMED))
    {
        $statut = false;
        $hasFriendWidth = $this->hasFriendshipWith($user, $array);

        if ($hasFriendWidth) {
            $userFriend = $hasFriendWidth[1];
            $statut = array('state' => $userFriend->getState(), 'type' => $userFriend->getType());
        }

        return $statut;
    }

    /**
     * Retourne le nombre d'amis de l'utilisateur
     *
     * @return integer
     */
    public function getNbFriends()
    {
        $criteria = Criteria::create()
            ->where(Criteria::expr()->eq("state", UserFriend::CONFIRMED));

        return count($this->userFriends->matching($criteria)) + count($this->friendsUser->matching($criteria));
    }

    /**
     * @param array $access
     * @return ArrayCollection
     */
    public function getAllGroups($access = array(GroupUser::ACCESS_DEFAULT, GroupUser::ACCESS_ADMIN))
    {
        $groups = new ArrayCollection();
        $this->groupSubscribes->filter(function ($element) use ($groups, $access) {
            if (in_array($element->getAccess(), $access)) {
                if (!$groups->contains($element->getGroup())) {
                    $groups->add($element->getGroup());
                }
                return true;
            }
            return false;
        });

        $iterator = $groups->getIterator();
        $iterator->uasort(function ($a, $b) {
            return ($a->getCreated() > $b->getCreated()) ? -1 : 1;
        });
        $groups = new ArrayCollection(iterator_to_array($iterator));

        return $groups;
    }

    /**
     * @param array $access
     * @return ArrayCollection
     */
    public function getAllHunts($access = array(LoungeUser::ACCESS_DEFAULT, LoungeUser::ACCESS_ADMIN))
    {
        $hunts = new ArrayCollection();
        $this->loungeSubscribes->filter(function ($element) use ($hunts, $access) {
            if (in_array($element->getAccess(), $access)) {
                if (!$hunts->contains($element->getLounge())) {
                    $hunts->add($element->getLounge());
                }
                return true;
            }
            return false;
        });

        $iterator = $hunts->getIterator();
        $iterator->uasort(function ($a, $b) {
            return ($a->getMeetingDate() > $b->getMeetingDate()) ? -1 : 1;
        });
        $hunts = new ArrayCollection(iterator_to_array($iterator));

        return $hunts;
    }

    /**
     * @param array $access
     * @return ArrayCollection
     */
    public function getAllHuntsLives($access = array(LoungeUser::ACCESS_DEFAULT, LoungeUser::ACCESS_ADMIN))
    {
        $hunts = new ArrayCollection();
        $currentDate = new \DateTime();
        $this->loungeSubscribes->filter(function ($element) use ($hunts, $access, $currentDate) {
            if (in_array($element->getAccess(), $access)) {
                if (!$hunts->contains($element->getLounge()) && !is_null($element->getLounge()->getMeetingDate()) && !is_null($element->getLounge()->getEndDate())) {
                    if ($element->getLounge()->isLiveActive($this)) {
                        $hunts->add($element->getLounge());
                    }
                }
                return true;
            }
            return false;
        });

        $iterator = $hunts->getIterator();
        $iterator->uasort(function ($a, $b) {
            return ($a->getMeetingDate() > $b->getMeetingDate()) ? -1 : 1;
        });
        $hunts = new ArrayCollection(iterator_to_array($iterator));

        return $hunts;
    }

    /**
     * Retourne l'ensemble des groupe excepté le groupe passé en paramètre
     *
     * @param \NaturaPass\GroupBundle\Entity\Group $group
     * @param array $access
     *
     * @return \Doctrine\Common\Collections\ArrayCollection
     */
    public function getAllGroupsNotIn($group, $access = array(GroupUser::ACCESS_DEFAULT, GroupUser::ACCESS_ADMIN))
    {
        $groups = new ArrayCollection();
        $this->groupSubscribes->filter(function ($element) use ($groups, $group, $access) {
            if (in_array($element->getAccess(), $access) && $group != $element->getGroup()) {
                if (!$groups->contains($element->getGroup())) {
                    $groups->add($element->getGroup());
                }
                return true;
            }
            return false;
        });

        $iterator = $groups->getIterator();
        $iterator->uasort(function ($a, $b) {
            return ($a->getCreated() > $b->getCreated()) ? -1 : 1;
        });
        $groups = new ArrayCollection(iterator_to_array($iterator));

        return $groups;
    }

    /**
     * @param array $access
     * @return ArrayCollection
     */
    public function getLounges($access = array(LoungeUser::ACCESS_DEFAULT, LoungeUser::ACCESS_ADMIN))
    {
        $lounges = new ArrayCollection();
        $this->loungeSubscribes->filter(function ($element) use ($lounges, $access) {
            if (in_array($element->getAccess(), $access)) {
                if ($element->getLounge()->getEndDate() > new \DateTime()) {
                    $lounges->add($element->getLounge());
                }
                return true;
            }
            return false;
        });
        $iterator = $lounges->getIterator();
        $iterator->uasort(function ($a, $b) {
            return ($a->getMeetingDate() < $b->getMeetingDate()) ? -1 : 1;
        });
        $lounges = new ArrayCollection(iterator_to_array($iterator));

        return $lounges;
    }

    /**
     * @param array $access
     * @return ArrayCollection
     */
    public function getOldLounges($access = array(LoungeUser::ACCESS_DEFAULT, LoungeUser::ACCESS_ADMIN))
    {

        $lounges = new ArrayCollection();
        $this->loungeSubscribes->filter(function ($element) use ($lounges, $access) {
            if (in_array($element->getAccess(), $access)) {
                if ($element->getLounge()->getEndDate() <= new \DateTime()) {
                    $lounges->add($element->getLounge());
                }
                return true;
            }
            return false;
        });
        $iterator = $lounges->getIterator();
        $iterator->uasort(function ($a, $b) {
            return ($a->getMeetingDate() > $b->getMeetingDate()) ? -1 : 1;
        });
        $lounges = new ArrayCollection(iterator_to_array($iterator));
//        foreach ($this->loungeSubscribes as $loungeUser) {
//            if ($loungeUser->getLounge()->getEndDate() > new \DateTime()) {
//                $lounges->add($loungeUser->getLounge());
//            }
//        }

        return $lounges;
    }

    /**
     * @return \Doctrine\Common\Collections\Collection
     *
     */
    public function getLoungesUserWaiting()
    {

        $count = 0;

        foreach ($this->loungeSubscribes as $loungeUser) {
            if ($loungeUser->getLounge()->getEndDate() > new \DateTime()) {
                $count += $loungeUser->getLounge()->isWaitingValidation($this);
            }
        }

        return $count;
    }

    /**
     * @return \Doctrine\Common\Collections\Collection
     *
     */
    public function getGroupsUserWaiting()
    {

        $count = 0;

        foreach ($this->groupSubscribes as $groupUser) {
            $count += $groupUser->getGroup()->isWaitingValidation($this);
        }

        return $count;
    }

    /**
     * @param string $facebook_id
     * @return User
     */
    public function setFacebookId($facebook_id)
    {
        $this->facebook_id = $facebook_id;
        return $this;
    }

    /**
     * @return string
     */
    public function getFacebookId()
    {
        return $this->facebook_id;
    }

    /**
     * Set courtesy
     *
     * @param integer $courtesy
     * @return User
     */
    public function setCourtesy($courtesy)
    {
        $this->courtesy = $courtesy;

        return $this;
    }

    /**
     * Get courtesy
     *
     * @return integer
     */
    public function getCourtesy()
    {
        return $this->courtesy;
    }

    /**
     * Set firstname
     *
     * @param string $firstname
     * @return User
     */
    public function setFirstname($firstname)
    {
        $this->firstname = $firstname;

        return $this;
    }

    /**
     * Get firstname
     *
     * @return string
     */
    public function getFirstname()
    {
        return $this->firstname;
    }

    /**
     * Set lastname
     *
     * @param string $lastname
     * @return User
     */
    public function setLastname($lastname)
    {
        $this->lastname = $lastname;

        return $this;
    }

    /**
     * Get lastname
     *
     * @return string
     */
    public function getLastname()
    {
        return $this->lastname;
    }

    /**
     * Set birthday
     *
     * @param \DateTime $birthday
     * @return User
     */
    public function setBirthday($birthday)
    {
        $this->birthday = $birthday;

        return $this;
    }

    /**
     * Get birthday
     *
     * @return \DateTime
     */
    public function getBirthday()
    {
        return $this->birthday;
    }

    /**
     * Set created
     *
     * @param \DateTime $created
     * @return User
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
     * @return User
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

    public function setEmail($email)
    {
        parent::setEmail($email);
        $this->setUsername($email);

        return $this;
    }

    /**
     * Get groupsOwner
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getGroupsOwner()
    {
        return $this->groupsOwner;
    }

    /**
     * Return all group's id of the user
     */
    public function getGroupsId()
    {
        $access = array(GroupUser::ACCESS_DEFAULT, GroupUser::ACCESS_ADMIN);
        $arrayReturn = array();
        foreach ($this->groupSubscribes as $group) {
            if (in_array($group->getAccess(), $access)) {
                $arrayReturn[] = $group->getGroup()->getId();
            }
        }
        return $arrayReturn;
    }

    /**
     * Add loungesOwner
     *
     * @param \NaturaPass\LoungeBundle\Entity\Lounge $loungesOwner
     * @return User
     */
    public function addLoungesOwner(\NaturaPass\LoungeBundle\Entity\Lounge $loungesOwner)
    {
        $this->loungesOwner[] = $loungesOwner;

        return $this;
    }

    /**
     * Remove loungesOwner
     *
     * @param \NaturaPass\LoungeBundle\Entity\Lounge $loungesOwner
     */
    public function removeLoungesOwner(\NaturaPass\LoungeBundle\Entity\Lounge $loungesOwner)
    {
        $this->loungesOwner->removeElement($loungesOwner);
    }

    /**
     * Get loungesOwner
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getLoungesOwner()
    {
        return $this->loungesOwner;
    }

    /**
     * Add favoritesOwner
     *
     * @param \NaturaPass\ObservationBundle\Entity\Favorite $favorite
     * @return User
     */
    public function addFavoritesOwner(Favorite $favorite)
    {
        $this->favoritesOwner[] = $favorite;

        return $this;
    }

    /**
     * Remove favoritesOwner
     *
     * @param \NaturaPass\ObservationBundle\Entity\Favorite $favorite
     */
    public function removeFavoritesOwner(Favorite $favorite)
    {
        $this->favoritesOwner->removeElement($favorite);
    }

    /**
     * Get favoritesOwner
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getFavoritesOwner()
    {
        return $this->favoritesOwner;
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
     * Add loungeSubscribes
     *
     * @param \NaturaPass\LoungeBundle\Entity\LoungeUser $loungeSubscribes
     * @return User
     */
    public function addLoungeSuscribe(\NaturaPass\LoungeBundle\Entity\LoungeUser $loungeSubscribes)
    {
        $this->loungeSubscribes[] = $loungeSubscribes;

        return $this;
    }

    /**
     * Remove loungeSubscribes
     *
     * @param \NaturaPass\LoungeBundle\Entity\LoungeUser $loungeSubscribes
     */
    public function removeLoungeSuscribe(\NaturaPass\LoungeBundle\Entity\LoungeUser $loungeSubscribes)
    {
        $this->loungeSubscribes->removeElement($loungeSubscribes);
    }

    /**
     * Get loungeSubscribes
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getLoungeSubscribes()
    {
        return $this->loungeSubscribes;
    }

    /**
     * @param array $access
     * @return mixed
     */
    public function getGroupSubscribes($access = array(GroupUser::ACCESS_DEFAULT, GroupUser::ACCESS_ADMIN))
    {
        return $this->groupSubscribes->filter(function ($element) use ($access) {
            return in_array($element->getAccess(), $access);
        });
    }

    /**
     * Add userFriends
     *
     * @param \NaturaPass\UserBundle\Entity\UserFriend $userFriends
     * @return User
     */
    public function addUserFriend(\NaturaPass\UserBundle\Entity\UserFriend $userFriends)
    {
        $this->userFriends[] = $userFriends;

        return $this;
    }

    /**
     * Remove userFriends
     *
     * @param \NaturaPass\UserBundle\Entity\UserFriend $userFriends
     */
    public function removeUserFriend(\NaturaPass\UserBundle\Entity\UserFriend $userFriends)
    {
        $this->userFriends->removeElement($userFriends);
    }

    /**
     * Get userFriends
     *
     * @return \NaturaPass\UserBundle\Entity\UserFriend[]
     */
    public function getUserFriends()
    {
        return $this->userFriends;
    }

    /**
     * Add friendsUser
     *
     * @param \NaturaPass\UserBundle\Entity\UserFriend $friendsUser
     * @return User
     */
    public function addFriendsUser(\NaturaPass\UserBundle\Entity\UserFriend $friendsUser)
    {
        $this->friendsUser[] = $friendsUser;

        return $this;
    }

    /**
     * Remove friendsUser
     *
     * @param \NaturaPass\UserBundle\Entity\UserFriend $friendsUser
     */
    public function removeFriendsUser(\NaturaPass\UserBundle\Entity\UserFriend $friendsUser)
    {
        $this->friendsUser->removeElement($friendsUser);
    }

    /**
     * Get friendsUser
     *
     * @return \NaturaPass\UserBundle\Entity\UserFriend[]
     */
    public function getFriendsUser()
    {
        return $this->friendsUser;
    }

    /**
     * @return string
     */
    public function getUsertag()
    {
        return $this->usertag;
    }

    /**
     * @param $usertag
     *
     * @return User
     */
    public function setUsertag($usertag)
    {
        $this->usertag = $usertag;
        return $this;
    }

    /**
     * Add geolocation
     *
     * @param \NaturaPass\MainBundle\Entity\Geolocation $geolocation
     * @return User
     */
    public function addGeolocation(Geolocation $geolocation)
    {
        $this->geolocations[] = $geolocation;

        return $this;
    }

    /**
     * Remove geolocation
     *
     * @param \NaturaPass\MainBundle\Entity\Geolocation $geolocation
     */
    public function removeGeolocation(Geolocation $geolocation)
    {
        $this->geolocations->removeElement($geolocation);
    }

    /**
     * Get geolocations
     *
     * @return \NaturaPass\MainBundle\Entity\Geolocation[]
     */
    public function getGeolocations()
    {
        return $this->geolocations;
    }

    /**
     * Add media
     *
     * @param \NaturaPass\UserBundle\Entity\UserMedia $media
     *
     * @return User
     */
    public function addMedia(UserMedia $media)
    {
        $this->medias[] = $media;

        return $this;
    }

    /**
     * Remove media
     *
     * @param \NaturaPass\UserBundle\Entity\UserMedia $media
     */
    public function removeMedia(UserMedia $media)
    {
        $this->medias->removeElement($media);
    }

    /**
     * Get medias
     *
     * @return \NaturaPass\UserBundle\Entity\UserMedia[]
     */
    public function getMedias()
    {
        return $this->medias;
    }

    /**
     * Add notification
     *
     * @param \NaturaPass\NotificationBundle\Entity\NotificationReceiver $notification
     * @return User
     */
    public function addNotification(NotificationReceiver $notification)
    {
        $this->notifications[] = $notification;

        return $this;
    }

    /**
     * Remove notification
     *
     * @param \NaturaPass\NotificationBundle\Entity\NotificationReceiver $notification
     */
    public function removeNotification(NotificationReceiver $notification)
    {
        $this->notifications->removeElement($notification);
    }

    /**
     * @return Parameters
     */
    public function getParameters()
    {
        return $this->parameters;
    }

    /**
     * @param Parameters $parameters
     *
     * @return User
     */
    public function setParameters(Parameters $parameters)
    {
        $this->parameters = $parameters;
        return $this;
    }

    /**
     * Get addresses
     *
     * @return ArrayCollection|\NaturaPass\UserBundle\Entity\UserAddress[]
     */
    public function getAddresses()
    {
        return $this->addresses;
    }

    /**
     * Retourne l'adresse favorite de l'utilisateur
     */
    public function getFavoriteAddress()
    {
        $criteria = Criteria::create();

        $criteria->where(Criteria::expr()->eq('favorite', true));

        $addresses = $this->addresses->matching($criteria);

        return $addresses->first();
    }

    /**
     * Add address
     *
     * @param \NaturaPass\UserBundle\Entity\UserAddress $address
     *
     * @return User
     */
    public function addAddress(UserAddress $address)
    {
        $this->addresses[] = $address;

        return $this;
    }

    /**
     * Remove address
     *
     * @param \NaturaPass\UserBundle\Entity\UserAddress $address
     *
     * @return $this
     */
    public function removeAddress(UserAddress $address)
    {
        $this->addresses->removeElement($address);

        return $this;
    }

    /**
     * Get reports
     *
     * @return \NaturaPass\PublicationBundle\Entity\PublicationReport[]
     */
    public function getReports()
    {
        return $this->reports;
    }

    /**
     * Get last report
     *
     * @param \NaturaPass\PublicationBundle\Entity\Publication $publication
     *
     * @return \NaturaPass\PublicationBundle\Entity\PublicationReport
     */
    public function getLastReport(Publication $publication)
    {
        $reports = new ArrayCollection();
        foreach ($this->reports as $report) {
            if ($report->getUser() === $this && $report->getPublication() === $publication) {
                $reports->add($report);
            }
        }
        return $reports->last();
    }

    /**
     * Add report
     *
     * @param \NaturaPass\PublicationBundle\Entity\PublicationReport $report
     *
     * @return User
     */
    public function addReport(PublicationReport $report)
    {
        $this->reports[] = $report;

        return $this;
    }

    /**
     * Remove report
     *
     * @param \NaturaPass\PublicationBundle\Entity\PublicationReport $report
     *
     * @return $this
     */
    public function removeReport(PublicationReport $report)
    {
        $this->reports->removeElement($report);

        return $this;
    }

    /**
     * @param string $hometown
     *
     * @return $this
     */
    public function setHometown($hometown)
    {
        $this->hometown = $hometown;
        return $this;
    }

    /**
     * @return string
     */
    public function getHometown()
    {
        return $this->hometown;
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
     * @param string $location
     *
     * @return $this
     */
    public function setLocation($location)
    {
        $this->location = $location;
        return $this;
    }

    /**
     * @return string
     */
    public function getLocation()
    {
        return $this->location;
    }

    /**
     * Add device
     *
     * @param Device $device
     * @return User
     */
    public function addDevice(Device $device)
    {
        $this->devices->add($device);

        return $this;
    }

    /**
     * Remove device
     *
     * @param Device $device
     */
    public function removeDevice(Device $device)
    {
        $this->devices->removeElement($device);
    }

    /**
     * Get devides
     *
     * @return \NaturaPass\UserBundle\Entity\UserDevice[]|ArrayCollection
     */
    public function getDevices()
    {
        return $this->devices;
    }

    /**
     * Add conversations
     *
     * @param \NaturaPass\MessageBundle\Entity\Conversation $conversations
     * @return User
     */
    public function addConversation(\NaturaPass\MessageBundle\Entity\Conversation $conversations)
    {
        $this->conversations[] = $conversations;

        return $this;
    }

    /**
     * Remove conversations
     *
     * @param \NaturaPass\MessageBundle\Entity\Conversation $conversations
     */
    public function removeConversation(\NaturaPass\MessageBundle\Entity\Conversation $conversations)
    {
        $this->conversations->removeElement($conversations);
    }

    /**
     * Get conversations
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getConversations()
    {
        return $this->conversations;
    }

    /**
     * Set last_activity
     *
     * @param \DateTime $lastActivity
     * @return User
     */
    public function setLastActivity($lastActivity)
    {
        $this->last_activity = $lastActivity;

        return $this;
    }

    /**
     * Get last_activity
     *
     * @return \DateTime
     */
    public function getLastActivity()
    {
        return $this->last_activity;
    }

    /**
     * Get maps
     *
     * @return \NaturaPass\UserBundle\Entity\UserMap[]|ArrayCollection
     */
    public function getMaps()
    {
        return $this->maps;
    }

    /**
     * Add map
     *
     * @param UserMap $map
     * @return User
     */
    public function addMap(UserMap $map)
    {
        $this->maps->add($map);

        return $this;
    }

    /**
     * Remove map
     *
     * @param UserMap $map
     */
    public function removeMap(UserMap $map)
    {
        $this->maps->removeElement($map);
    }

    /**
     * Get maps
     *
     * @return \Admin\SentinelleBundle\Entity\Receiver[]|ArrayCollection
     */
    public function getReceivers()
    {
        return $this->receivers;
    }

    /**
     * Add Receiver
     *
     * @param Receiver $receiver
     * @return User
     */
    public function addReceiver(Receiver $receiver)
    {
        $this->receivers->add($receiver);

        return $this;
    }

    /**
     * Remove Receiver
     *
     * @param Receiver $receiver
     */
    public function removeReceiver(Receiver $receiver)
    {
        $this->receivers->removeElement($receiver);
    }

    /**
     *  get All games
     */
    public function getAllConcoursOpen()
    {
        return Game::getAllConcoursOpen();
    }

    /**
     * Is online (if the last activity was within the last 5 minutes)
     *
     * @return boolean
     */
    public function isOnline()
    {
        $now = new \DateTime();
        $now->modify('-5 minutes');
        return $this->getLastActivity() > $now;
    }


    /**
     * Return first Receiver
     *
     * @return Receiver
     */
    public function getFirstReceiver()
    {
        if ($this->receivers->count() > 0) {
            return $this->receivers[0];
        }
        return null;
    }

    /**
     * Add shapes
     *
     * @param \NaturaPass\UserBundle\Entity\User $user
     * @return User
     */
    public function addLock(User $user)
    {
        if (!$this->locks->contains($user)) {
            $this->locks->add($user);
        }
        return $this;
    }

    /**
     * Remove user lock
     *
     * @param \NaturaPass\UserBundle\Entity\User $user
     */
    public function removeLock(User $user)
    {
        $this->locks->removeElement($user);
    }

    /**
     * Get users locked
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getLocks()
    {
        return $this->locks;
    }

    /**
     * Add shapes
     *
     * @param \NaturaPass\MainBundle\Entity\Shape $shapes
     * @return User
     */
    public function addShape(Shape $shape)
    {
        $this->shapes->add($shape);
        return $this;
    }

    /**
     * Remove shapes
     *
     * @param \NaturaPass\MainBundle\Entity\Shape $shape
     */
    public function removeShape(Shape $shape)
    {
        $this->shapes->removeElement($shape);
    }

    /**
     * Get shapes
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getShapes()
    {
        return $this->shapes;
    }

    /**
     * Add dogs
     *
     * @param \NaturaPass\UserBundle\Entity\DogParameter $dog
     * @return User
     */
    public function addDog(DogParameter $dog)
    {
        $this->dogs->add($dog);
        return $this;
    }

    /**
     * Remove dogs
     *
     * @param \NaturaPass\UserBundle\Entity\DogParameter $dog
     */
    public function removeDog(DogParameter $dog)
    {
        $this->dogs->removeElement($dog);
    }

    /**
     * Get dogs
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getDogs()
    {
        return $this->dogs;
    }

    /**
     * Add papers
     *
     * @param \NaturaPass\UserBundle\Entity\PaperParameter $paper
     * @return User
     */
    public function addPaper(PaperParameter $paper)
    {
        $this->papers->add($paper);
        return $this;
    }

    /**
     * Remove papers
     *
     * @param \NaturaPass\UserBundle\Entity\PaperParameter $paper
     */
    public function removePaper(PaperParameter $paper)
    {
        $this->papers->removeElement($paper);
    }

    /**
     * Get papers
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getPapers()
    {
        return $this->papers;
    }

    /**
     * Add huntcity
     *
     * @param \NaturaPass\UserBundle\Entity\HuntCityParameter $huntcity
     * @return User
     */
    public function addHuntCity(HuntCityParameter $huntcity)
    {
        $this->huntcities->add($huntcity);
        return $this;
    }

    /**
     * Remove huntcity
     *
     * @param \NaturaPass\UserBundle\Entity\HuntCityParameter $huntcity
     */
    public function removeHuntCity(HuntCityParameter $huntcity)
    {
        $this->huntcities->removeElement($huntcity);
    }

    /**
     * Get huntcities
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getHuntCities()
    {
        return $this->huntcities;
    }

    /**
     * Add huntCountry
     *
     * @param \NaturaPass\UserBundle\Entity\HuntCountryParameter $huntCountry
     * @return User
     */
    public function addHuntCountry(HuntCountryParameter $huntCountry)
    {
        $this->huntcountries->add($huntCountry);
        return $this;
    }

    /**
     * Remove huntCountry
     *
     * @param \NaturaPass\UserBundle\Entity\HuntCountryParameter $huntCountry
     */
    public function removeHuntCountry(HuntCountryParameter $huntCountry)
    {
        $this->huntcountries->removeElement($huntCountry);
    }

    /**
     * Get huntCountry
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getHuntCountries()
    {
        return $this->huntcountries;
    }

    /**
     * Add hunttypes
     *
     * @param \NaturaPass\UserBundle\Entity\HuntTypeParameter $hunttype
     * @return User
     */
    public function addHunttype(HuntTypeParameter $hunttype)
    {
        $this->hunttypes->add($hunttype);
        return $this;
    }

    /**
     * Remove hunttypes
     *
     * @param \NaturaPass\UserBundle\Entity\HuntTypeParameter $hunttype
     */
    public function removeHunttype(HuntTypeParameter $hunttype)
    {
        $this->hunttypes->removeElement($hunttype);
    }

    /**
     * Get hunttypes
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getHunttypes()
    {
        return $this->hunttypes;
    }

    /**
     * Set groupsarray
     *
     * @param string $groupsarray
     * @return User
     */
    public function setGroupsarray($groupsarray)
    {
        $this->groupsarray = $groupsarray;

        return $this;
    }

    /**
     * Get groupsarray
     *
     * @return string
     */
    public function getGroupsarray()
    {
        return $this->groupsarray;
    }

    /**
     * Set huntsarray
     *
     * @param string $huntsarray
     * @return User
     */
    public function setHuntsarray($huntsarray)
    {
        $this->huntsarray = $huntsarray;

        return $this;
    }

    /**
     * Get huntsarray
     *
     * @return string
     */
    public function getHuntsarray()
    {
        return $this->huntsarray;
    }
}
    