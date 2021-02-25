<?php

namespace NaturaPass\PublicationBundle\Entity;

use Admin\SentinelleBundle\Entity\Card;
use Admin\SentinelleBundle\Entity\Category;
use Admin\AnimalBundle\Entity\Animal;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation as JMS;
use Gedmo\Mapping\Annotation as ORMExtension;
use NaturaPass\GroupBundle\Entity\Group;
use NaturaPass\LoungeBundle\Entity\Lounge;
use NaturaPass\MainBundle\Entity\Sharing;
use NaturaPass\PublicationBundle\Entity\PublicationColor;
use NaturaPass\UserBundle\Entity\User;

/**
 * Favorite
 *
 * @ORM\Table()
 * @ORM\Entity()
 *
 * @JMS\ExclusionPolicy("all")
 */
class Favorite
{

    /**
     * @var integer
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     *
     * @JMS\Expose
     * @JMS\Groups({"FavoriteDetail", "FavoriteLess", "FavoriteID"})
     */
    protected $id;

    /**
     *
     * @var User
     *
     * @ORM\ManyToOne(targetEntity="NaturaPass\UserBundle\Entity\User", inversedBy="favoritesOwner")
     * @ORM\JoinColumn(nullable=false)
     *
     * @JMS\Expose
     * @JMS\Groups({"FavoriteOwner"})
     */
    protected $owner;

    /**
     * @var string
     *
     * @ORM\Column(name="name", type="string", length=255)
     *
     * @JMS\Expose
     * @JMS\Groups({"FavoriteDetail", "FavoriteLess"})
     */
    protected $name;

    /**
     * @var string
     *
     * @ORM\Column(name="legend", type="string", length=255, nullable=true)
     *
     * @JMS\Expose
     * @JMS\Groups({"FavoriteLess"})
     */
    protected $legend;

    /**
     * @var PublicationColor
     *
     * @ORM\ManyToOne(targetEntity="NaturaPass\PublicationBundle\Entity\PublicationColor",inversedBy="favorite")
     * @ORM\JoinColumn(nullable=true)
     *
     * @JMS\Expose
     * @JMS\Groups({"PublicationDetail"})
     */
    protected $publicationcolor;

    /**
     * @ORM\ManyToOne(targetEntity="Admin\SentinelleBundle\Entity\Category", inversedBy="favorites")
     *
     * @JMS\Expose
     * @JMS\Groups({"FavoriteCategoryDetail"})
     */
    protected $category;

    /**
     * @ORM\ManyToOne(targetEntity="Admin\SentinelleBundle\Entity\Card")
     * @ORM\JoinColumn(nullable=true)
     *
     * @JMS\Expose
     * @JMS\Groups({"FavoriteCategoryDetail"})
     */
    protected $card;

    /**
     * @ORM\ManyToOne(targetEntity="Admin\AnimalBundle\Entity\Animal")
     *
     * @JMS\Expose
     * @JMS\Groups({"FavoriteCategoryDetail"})
     */
    protected $animal;

    /**
     * @var integer
     *
     * @ORM\Column(name="specific_category", type="integer")
     *
     * @JMS\Expose
     * @JMS\Groups({"FavoriteCategoryDetail"})
     */
    protected $specific = 0;

    /**
     * @ORM\OneToMany(targetEntity="NaturaPass\PublicationBundle\Entity\FavoriteAttachment", mappedBy="favorite", cascade={"persist", "remove"}, fetch="EXTRA_LAZY", orphanRemoval=true)
     *
     * @JMS\Expose
     * @JMS\Groups({"FavoriteAttachementDetail"})
     */
    protected $attachments;

    /**
     * @ORM\OneToOne(targetEntity="NaturaPass\MainBundle\Entity\Sharing", cascade={"persist", "remove"}, orphanRemoval=true)
     *
     * @JMS\Expose
     * @JMS\Groups({"FavoriteDetail"})
     */
    protected $sharing;

    /**
     * @var ArrayCollection
     *
     * @ORM\ManyToMany(targetEntity="NaturaPass\GroupBundle\Entity\Group", inversedBy="favorites", fetch="EXTRA_LAZY");
     * @ORM\JoinTable(name="group_has_favorite")
     *
     * @JMS\Expose
     * @JMS\Groups({"PublicationGroup"})
     */
    protected $groups;

    /**
     * @var ArrayCollection
     *
     * @ORM\ManyToMany(targetEntity="NaturaPass\UserBundle\Entity\User", inversedBy="favorites", fetch="EXTRA_LAZY");
     * @ORM\JoinTable(name="user_has_favorite")
     *
     * @JMS\Expose
     */
    protected $shareuser;

    /**
     * @var ArrayCollection
     *
     * @ORM\ManyToMany(targetEntity="NaturaPass\LoungeBundle\Entity\Lounge", inversedBy="favorites", fetch="EXTRA_LAZY");
     * @ORM\JoinTable(name="lounge_has_favorite")
     *
     * @JMS\Expose
     * @JMS\Groups({"PublicationLounge"})
     */
    protected $hunts;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="created", type="datetime")
     * @ORMExtension\Timestampable(on="create")
     *
     * @JMS\Expose
     * @JMS\Groups({"FavoriteDetail"})
     */
    protected $created;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="updated", type="datetime")
     * @ORMExtension\Timestampable(on="update")
     *
     * @JMS\Expose
     * @JMS\Groups({"FavoriteDetail"})
     */
    protected $updated;

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
     * @return Favorite
     */
    public function setSpecific($specific)
    {
        $this->specific = $specific;

        return $this;
    }

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->attachments = new ArrayCollection();
        $this->groups = new ArrayCollection();
        $this->shareuser = new ArrayCollection();
        $this->hunts = new ArrayCollection();
    }

    /**
     * Get legend
     *
     * @return string
     */
    public function getLegend()
    {
        return $this->legend;
    }

    /**
     * Set legend
     *
     * @param string $legend
     * @return Favorite
     */
    public function setLegend($legend)
    {
        $this->legend = $legend;

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
     * Set name
     *
     * @param string $name
     * @return Favorite
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * Set owner
     *
     * @param \NaturaPass\UserBundle\Entity\User $owner
     * @return Favorite
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
     * Set created
     *
     * @param \DateTime $created
     * @return Favorite
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
     * @return Favorite
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
     * @return Favorite
     */
    public function setCategory(Category $category)
    {
        $this->category = $category;

        return $this;
    }

    /**
     * @return Card
     */
    public function getCard()
    {
        return $this->card;
    }

    /**
     * @param Card $card
     * @return Favorite
     */
    public function setCard(Card $card)
    {
        $this->card = $card;

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
     * @return Favorite
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
     * @param FavoriteAttachment $attachment
     *
     * @return Favorite
     */
    public function addAttachment(FavoriteAttachment $attachment)
    {
        if (!$this->attachments->contains($attachment)) {
            $attachment->setFavorite($this);

            $this->attachments->add($attachment);
        }

        return $this;
    }

    /**
     * remove attachment
     *
     * @param FavoriteAttachment $attachment
     *
     * @return Favorite
     */
    public function removeAttachment(FavoriteAttachment $attachment)
    {
        $this->attachments->removeElement($attachment);

        return $this;
    }

    /**
     * @param FavoriteAttachment[] $attachments
     * @return Favorite
     */
    public function setAttachments($attachments)
    {
        foreach ($attachments as $attachment) {
            $attachment->setFavorite($this);
        }

        $this->attachments = $attachments;

        return $this;
    }

    /**
     * Add group
     *
     * @param \NaturaPass\GroupBundle\Entity\Group $group
     * @return Favorite
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

    /**
     * Add shareuser
     *
     * @param \NaturaPass\UserBundle\Entity\User $shareuser
     * @return User
     */
    public function addShareuser(User $shareuser)
    {
        $this->shareuser[] = $shareuser;
        return $this;
    }

    /**
     * Remove shareuser
     *
     * @param \NaturaPass\UserBundle\Entity\User $shareuser
     */
    public function removeShareuser(User $shareuser)
    {
        $this->shareuser->removeElement($shareuser);
    }

    /**
     * Remove users
     *
     */
    public function removeAllShareusers()
    {
        $this->shareuser = new ArrayCollection();
    }

    /**
     * Get user
     *
     * @return ArrayCollection|\NaturaPass\UserBundle\Entity\User[]
     */
    public function getShareusers()
    {
        return $this->shareuser;
    }

    /**
     * Add hunt
     *
     * @param \NaturaPass\LoungeBundle\Entity\Lounge $hunt
     * @return Favorite
     */
    public function addHunt(Lounge $hunt)
    {
        $this->hunts[] = $hunt;

        return $this;
    }

    /**
     * Remove hunt
     *
     * @param \NaturaPass\LoungeBundle\Entity\Lounge $hunt
     */
    public function removeHunt(Lounge $hunt)
    {
        $this->hunts->removeElement($hunt);
    }

    /**
     * Remove hunts
     *
     */
    public function removeAllHunts()
    {
        $this->hunts = new ArrayCollection();
    }

    /**
     * Get hunts
     *
     * @return ArrayCollection|\NaturaPass\LoungeBundle\Entity\Lounge[]
     */
    public function getHunts()
    {
        return $this->hunts;
    }

    /**
     * Get hunts
     *
     * @return ArrayCollection|\NaturaPass\LoungeBundle\Entity\Lounge[]
     */
    public function hasHunt(Lounge $hunt)
    {
        return $this->hunts->filter(function (Lounge $lounge) use ($hunt) {
            return $lounge->getId() == $hunt->getId();
        })->first();
    }

    /**
     * Set sharing
     *
     * @param \NaturaPass\MainBundle\Entity\Sharing $sharing
     * @return Favorite
     */
    public function setSharing(Sharing $sharing)
    {
        $this->sharing = $sharing;

        return $this;
    }

    /**
     * Get sharing
     *
     * @return \NaturaPass\MainBundle\Entity\Sharing
     */
    public function getSharing()
    {
        return $this->sharing;
    }

    /**
     * Set publicationcolor
     *
     * @param \NaturaPass\MainBundle\Entity\PublicationColor $publicationcolor
     *
     * @return Favorite
     */
    public function setPublicationcolor(PublicationColor $publicationcolor = null)
    {
        $this->publicationcolor = $publicationcolor;

        return $this;
    }

    /**
     * Get publicationcolor
     *
     * @return \NaturaPass\MainBundle\Entity\PublicationColor
     */
    public function getPublicationcolor()
    {
        return $this->publicationcolor;
    }

    public function getLastUpdated()
    {
        $updated = $this->getUpdated();
        return $updated;
    }

}

