<?php

namespace Admin\SentinelleBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation as JMS;
use Gedmo\Mapping\Annotation as Gedmo;
use Gedmo\Mapping\Annotation as ORMExtension;
use Admin\SentinelleBundle\Entity\Repository\CategoryRepository;
use NaturaPass\GroupBundle\Entity\Group;
use NaturaPass\UserBundle\Entity\User;

/**
 * Category
 *
 * @Gedmo\Tree(type="nested")
 * @ORM\Table(name="category")
 * @ORM\Entity(repositoryClass="Admin\SentinelleBundle\Entity\Repository\CategoryRepository")
 * @JMS\ExclusionPolicy("all")
 */
class Category
{

    const VISIBLE_ON = 1;
    const VISIBLE_OFF = 0;
    const TYPE_ALL = 1;
    const DEFAULT_MEDIA = '/img/interface/default-observation.jpg';

    /**
     * @var integer
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     *
     * @JMS\Expose
     * @JMS\Groups({"CategoryDetail", "CategoryLess", "CategoryID"})
     */
    protected $id;

    /**
     * @var string
     *
     * @ORM\Column(name="name", type="string", length=255)
     *
     * @JMS\Expose
     * @JMS\Groups({"CategoryDetail", "CategoryLess"})
     */
    protected $name;

    /**
     * @Gedmo\TreeLeft
     * @ORM\Column(name="lft", type="integer")
     *
     * @JMS\Expose
     * @JMS\Groups({"CategoryTree"})
     */
    private $lft;

    /**
     * @Gedmo\TreeRight
     * @ORM\Column(name="rgt", type="integer")
     *
     * @JMS\Expose
     * @JMS\Groups({"CategoryTree"})
     */
    protected $rgt;

    /**
     * @var string
     *
     * @ORM\Column(length=3000, nullable=true)
     */
    private $path;

    /**
     * @Gedmo\TreeParent
     * @ORM\ManyToOne(targetEntity="Category", inversedBy="children")
     * @ORM\JoinColumn(name="parent_id", referencedColumnName="id", onDelete="CASCADE")
     */
    private $parent;

    /**
     * @Gedmo\TreeRoot
     * @ORM\Column(type="integer", nullable=true)
     */
    private $root;

    /**
     * @var integer
     *
     * @ORM\Column(name="visible", type="integer")
     *
     * @JMS\Expose
     * @JMS\Groups({"CategoryDetail", "CategoryLess"})
     */
    protected $visible = self::VISIBLE_ON;

    /**
     * @Gedmo\TreeLevel
     * @ORM\Column(name="lvl", type="integer")
     *
     * @JMS\Expose
     * @JMS\Groups({"CategoryTree"})
     */
    private $lvl;

    /**
     * @ORM\OneToMany(targetEntity="Category", mappedBy="parent")
     * @ORM\OrderBy({"lft" = "ASC"})
     */
    private $children;

    /**
     * @var integer
     *
     * @ORM\Column(name="type", type="integer")
     *
     * @JMS\Expose
     * @JMS\Groups({"CategoryDetail"})
     */
    protected $type = self::TYPE_ALL;

    /**
     * @var integer
     *
     * @ORM\Column(name="search", type="integer")
     *
     * @JMS\Expose
     * @JMS\Groups({"CategoryDetail"})
     */
    protected $search = 0;

    /**
     * @var array
     */
    protected $roles;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="created", type="datetime")
     * @ORMExtension\Timestampable(on="create")
     *
     * @JMS\Expose
     * @JMS\Groups({"CategoryDetail"})
     */
    protected $created;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="updated", type="datetime")
     * @ORMExtension\Timestampable(on="update")
     *
     * @JMS\Expose
     * @JMS\Groups({"CategoryDetail"})
     */
    protected $updated;

    /**
     * @ORM\OneToMany(targetEntity="Admin\SentinelleBundle\Entity\CardCategoryZone", mappedBy="category", cascade={"persist", "remove"}, fetch="EXTRA_LAZY", orphanRemoval=true)
     * @ORM\JoinColumn(onDelete="CASCADE",nullable=true)
     */
    protected $cardszone;

    /**
     * @ORM\ManyToOne(targetEntity="Admin\SentinelleBundle\Entity\Card", inversedBy="categories")
     * @ORM\JoinColumn(nullable=true, onDelete="SET NULL")
     *
     */
    protected $card;

    /**
     * @ORM\OneToMany(targetEntity="NaturaPass\ObservationBundle\Entity\Observation", mappedBy="category", cascade={"persist", "remove"}, fetch="EXTRA_LAZY", orphanRemoval=true)
     *
     * @JMS\Expose
     * @JMS\Groups({"ObservationDetail"})
     */
    protected $observations;

    /**
     * @ORM\OneToMany(targetEntity="NaturaPass\PublicationBundle\Entity\Favorite", mappedBy="category", cascade={"persist", "remove"}, fetch="EXTRA_LAZY", orphanRemoval=true)
     *
     * @JMS\Expose
     * @JMS\Groups({"ObservationDetail"})
     */
    protected $favorites;

    /**
     * @var \Doctrine\Common\Collections\ArrayCollection|\Admin\SentinelleBundle\Entity\ReceiverRight[]
     *
     * @ORM\OneToMany(targetEntity="Admin\SentinelleBundle\Entity\ReceiverRight", mappedBy="category", cascade={"persist", "remove"}, fetch="EXTRA_LAZY", orphanRemoval=true)
     *
     * @JMS\Expose
     * @JMS\Groups({"ZoneDetail"})
     */
    protected $receiverrights;

    /**
     * @ORM\OneToOne(targetEntity="Admin\SentinelleBundle\Entity\CategoryMedia", cascade={"persist", "remove"}, orphanRemoval=true)
     *
     * @JMS\Expose
     * @JMS\Groups({"CategoryDetail"})
     */
    protected $media;

    /**
     * @var ArrayCollection
     *
     * @ORM\ManyToMany(targetEntity="NaturaPass\GroupBundle\Entity\Group", inversedBy="categories", fetch="EXTRA_LAZY");
     * @ORM\JoinTable(name="category_has_group")
     *
     * @JMS\Expose
     * @JMS\Groups({"CategoryDetail"})
     */
    protected $groups;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->groups = new ArrayCollection();
        $this->roles = array();
        $this->children = new ArrayCollection();
        $this->observation_categories = new ArrayCollection();
        $this->receiverrights = new ArrayCollection();
        $this->cardszone = new ArrayCollection();
        $this->favorites = new ArrayCollection();
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
     * @return Category
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
     * Get visible
     *
     * @return integer
     */
    function getVisible()
    {
        return $this->visible;
    }

    /**
     * Get search
     *
     * @return integer
     */
    function getSearch()
    {
        return $this->search;
    }

    /**
     * Set created
     *
     * @param \DateTime $created
     * @return Category
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
     * @return Category
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
     * Get lft
     *
     * @return integer
     */
    function getLft()
    {
        return $this->lft;
    }

    /**
     * Get rgt
     *
     * @return integer
     */
    function getRgt()
    {

        return $this->rgt;
    }

    /**
     * Set visible
     *
     * @param integer $visible
     * @return Category
     */
    function setVisible($visible)
    {
        $this->visible = $visible;

        return $this;
    }

    /**
     * Set search
     *
     * @param integer $search
     * @return Category
     */
    function setSearch($search)
    {
        $this->search = $search;

        return $this;
    }

    /**
     * Get parent
     *
     * @return Category
     */
    function getParent()
    {
        return $this->parent;
    }

    /**
     * Get root
     *
     * @return integer
     */
    function getRoot()
    {
        return $this->root;
    }

    /**
     * Get lvl
     *
     * @return integer
     */
    function getLvl()
    {
        return $this->lvl;
    }

    /**
     * Get children
     *
     * @return Category[]|ArrayCollection
     */
    function getChildren()
    {
        return $this->children;
    }

    /**
     * Get type
     *
     * @return integer
     */
    function getType()
    {
        return $this->type;
    }

    /**
     * Set lft
     *
     * @param integer $lft
     * @return Category
     */
    function setLft($lft)
    {
        $this->lft = $lft;

        return $this;
    }

    /**
     * Set rgt
     *
     * @param integer $rgt
     * @return Category
     */
    function setRgt($rgt)
    {
        $this->rgt = $rgt;

        return $this;
    }

    /**
     * Set parent
     *
     * @param integer $parent
     * @return Category
     */
    function setParent($parent)
    {
        $this->parent = $parent;

        return $this;
    }

    /**
     * Set root
     *
     * @param integer $root
     * @return Category
     */
    function setRoot($root)
    {
        $this->root = $root;

        return $this;
    }

    /**
     * Set lvl
     *
     * @param integer $lvl
     * @return Category
     */
    function setLvl($lvl)
    {
        $this->lvl = $lvl;

        return $this;
    }

    /**
     * Set children
     *
     * @param integer $children
     * @return Category
     */
    function setChildren($children)
    {
        $this->children = $children;

        return $this;
    }

    /**
     * Set type
     *
     * @param integer $type
     * @return Category
     */
    function setType($type)
    {
        $this->type = $type;

        return $this;
    }

    public function setRoles(array $roles)
    {
        $this->roles = array();

        foreach ($roles as $role) {
            $this->addRole($role);
        }

        return $this;
    }

    public function __toString()
    {
        return $this->getName();
    }

    public function removeRole($role)
    {
        if (false !== $key = array_search(strtoupper($role), $this->roles, true)) {
            unset($this->roles[$key]);
            $this->roles = array_values($this->roles);
        }

        return $this;
    }

    /**
     * Never use this to check if this user has access to a nything!
     *
     * Use the SecurityContext, or an implementation of AccessDecisionManager
     * instead, e.g.
     *
     *         $securityContext->isGranted('ROLE_USER');
     *
     * @param string $role
     *
     * @return boolean
     */
    public function hasRole($role)
    {
        return in_array(
            strtoupper($role), $this->getRoles(), true);
    }

    /**
     * Returns the user roles
     *
     * @return array The roles
     */
    public function getRoles()
    {

        $roles = $this->roles;

        foreach ($this->getGroups() as $group) {
            $roles = array_merge($roles, $group->getRoles());
        }

        // we need to make sure to have at least one role
        $roles[] = static::ROLE_DEFAULT;

        return array_unique($roles);
    }

    /**
     * Get CardCategoryZone
     * @param \Admin\SentinelleBundle\Entity\Zone $zone
     *
     * @return \Admin\SentinelleBundle\Entity\CardCategoryZone[]
     */
    public function getCardszone(Zone $zone)
    {
        return $this->cardszone->filter(function ($element) use ($zone) {
            return $element->getZone()->getId() === $zone->getId();
        });
    }

    /**
     * Add CardCategoryZone
     *
     * @param \Admin\SentinelleBundle\Entity\CardCategoryZone $card
     * @return Category
     */
    public function addCardzone(CardCategoryZone $card)
    {
        $this->cardszone[] = $card;

        return $this;
    }

    /**
     * Remove subscribers
     *
     * @param \Admin\SentinelleBundle\Entity\CardCategoryZone $card
     */
    public function removeCardzone(CardCategoryZone $card)
    {
        $this->cardszone->removeElement($card);
    }

    /**
     * Get Card
     *
     * @return \Admin\SentinelleBundle\Entity\Card
     */
    public function getCard()
    {
        return $this->card;
    }

    /**
     * Set Card
     *
     * @return Category
     */
    public function setCard($card)
    {
        $this->card = $card;

        return $this;
    }

    /**
     * Get Observation_categories
     *
     * @param array
     * @param boolean
     *
     * @return ArrayCollection
     */
    public function getObservation_categories()
    {
        return $this->observation_categories;
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
     * @param \Admin\SentinelleBundle\Entity\ReceiverRight $zonewithoutright
     * @return Category
     */
    public function addReceiverright(ReceiverRight $zonewithoutright)
    {
        $this->receiverrights[] = $zonewithoutright;

        return $this;
    }

    /**
     * Remove ReceiverRight
     *
     * @param \Admin\SentinelleBundle\Entity\ReceiverRight $zonewithoutright
     */
    public function removeReceiverright(ReceiverRight $zonewithoutright)
    {
        $this->receiverrights->removeElement($zonewithoutright);
    }

    /**
     * check if category can be show to the receiver
     *
     * @param \Admin\SentinelleBundle\Entity\Receiver $receiver
     * @return boolean
     */
    public function hasReceiverright(Receiver $receiver)
    {
        if ($this->receiverrights->isEmpty()) {
            return false;
        } else {
            $receiverrights = $this->receiverrights->filter(function ($receiverright) use ($receiver) {
                return $receiverright->getReceiver()->getId() == $receiver->getId();
            });
            return !$receiverrights->isEmpty();
        }
    }

    /**
     * Get media
     *
     * @return \Admin\SentinelleBundle\Entity\CategoryMedia
     */
    public function getMedia()
    {
        return $this->media;
    }

    /**
     * Set media
     *
     * @param \Admin\SentinelleBundle\Entity\CategoryMedia $media
     * @return \Admin\SentinelleBundle\Entity\Category
     */
    public function setMedia(CategoryMedia $media)
    {
        $this->media = $media;
        return $this;
    }

    /**
     * Return a picto of the branch
     * @return CategoryMedia
     */
    public function getPicto()
    {
        if (!is_null($this->getMedia())) {
            return $this->getMedia();
        } else if (!is_null($this->getParent())) {
            return $this->getParent()->getPicto();
        }
        return null;
    }

    /**
     * Add group
     *
     * @param \NaturaPass\GroupBundle\Entity\Group $group
     * @return \Admin\SentinelleBundle\Entity\Category
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

    public function userGroupAuthorised(User $user)
    {
        if ($this->groups->isEmpty()) {
            return true;
        } else {
            $groups = $this->groups->filter(function ($group) use ($user) {
                return in_array($group->getId(), $user->getGroupsId());
            });
            return ($groups->isEmpty()) ? false : true;
        }
    }

    public function isCategoryToEpos()
    {
        $check = false;
        $name_category = utf8_encode("Dommages/Dégats");
        if ($this->getName() === $name_category) {
            $check = true;
        } else {
            if (!is_null($this->getParent())) {
                $check = $this->getParent()->isCategoryToEpos();
            }
        }
        return $check;
    }

    public function getAnimalTree($manager)
    {
        $animals = $manager->getRepository('AdminAnimalBundle:Animal')->findBy(
            array('name_fr' => $this->getName()),
            array('name_fr' => 'ASC')
        );
        if (count($animals)) {
            return $animals[0];
        } else {
            if (!is_null($this->getParent())) {
                return $this->getParent()->getAnimalTree($manager);
            }
        }
        return null;
    }

    public function checkRightToSee(User $connected)
    {
        $right = true;
        if ($this->getVisible() == Category::VISIBLE_OFF) {
            $right = false;
        }
        if ($right && $this->getGroups()->count()) {
            $hasGroup = false;
            foreach ($this->getGroups() as $groupRight) {
                if (!$hasGroup) {
                    foreach ($connected->getGroups() as $group) {
                        if (!$hasGroup && $group->getId() == $groupRight->getId()) {
                            $hasGroup = true;
                        }
                    }
                }
            }
            if (!$hasGroup) {
                $right = false;
            }
        }
        if ($right && !is_null($this->getParent())) {
            $right = $this->getParent()->checkRightToSee($connected);
        }

        return $right;
    }

    public function setPath($path)
    {
        $this->path = $path;
    }

    public function getPath()
    {
        return $this->path;
    }

}
