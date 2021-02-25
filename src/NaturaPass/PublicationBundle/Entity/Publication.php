<?php

namespace NaturaPass\PublicationBundle\Entity;

use Api\ApiBundle\Controller\v2\Serialization\CategorySerialization;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use JMS\Serializer\Annotation as JMS;
use Gedmo\Mapping\Annotation as ORMExtension;
use Doctrine\Common\Collections\Criteria;
use NaturaPass\GroupBundle\Entity\Group;
use NaturaPass\LoungeBundle\Entity\Lounge;
use NaturaPass\MainBundle\Entity\Geolocation;
use NaturaPass\MainBundle\Entity\Sharing;
use NaturaPass\UserBundle\Entity\User;

/**
 * Publication
 *
 * @ORM\Table()
 * @ORM\Entity()
 * @ORM\HasLifecycleCallbacks
 * @JMS\ExclusionPolicy("all")
 */
class Publication
{

    /**
     * @var integer
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     *
     * @JMS\Expose
     * @JMS\Groups({"PublicationLess"})
     */
    protected $id;

    /**
     * @ORM\ManyToOne(targetEntity="NaturaPass\UserBundle\Entity\User")
     * @ORM\JoinColumn(nullable=false)
     *
     * @JMS\Expose
     * @JMS\Groups({"PublicationOwner"})
     */
    protected $owner;

    /**
     * @var string
     *
     * @ORM\Column(name="content", type="text")
     *
     * @JMS\Expose
     * @JMS\Groups({"PublicationLess"})
     */
    protected $content;

    /**
     * @var string
     *
     * @ORM\Column(name="legend", type="string", length=255, nullable=true)
     *
     * @JMS\Expose
     * @JMS\Groups({"PublicationLess"})
     */
    protected $legend;

    /**
     * @var string
     *
     * @ORM\Column(name="facebook_id", type="string", length=255, nullable=true)
     *
     * @JMS\Expose
     * @JMS\Groups({"PublicationLess"})
     */
    protected $facebook_id;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="date", type="datetime", nullable=true)
     *
     * @JMS\Expose
     * @JMS\Groups({"PublicationLess"})
     */
    protected $date;

    /**
     * @var boolean
     *
     * @ORM\Column(name="landmark", type="boolean", options={"default" = false})
     *
     * @JMS\Expose
     * @JMS\Groups({"PublicationLess"})
     */
    protected $landmark = false;

    /**
     * @var ArrayCollection
     *
     * @ORM\OneToMany(targetEntity="NaturaPass\PublicationBundle\Entity\PublicationAction", mappedBy="publication", cascade={"persist", "remove"}, orphanRemoval=true, fetch="EXTRA_LAZY")
     *
     * @JMS\Expose
     * @JMS\Groups({"PublicationActions"})
     */
    protected $actions;

    /**
     * @var ArrayCollection
     *
     * @ORM\OneToMany(targetEntity="NaturaPass\PublicationBundle\Entity\PublicationComment", mappedBy="publication", cascade={"persist", "remove"}, orphanRemoval=true, fetch="EXTRA_LAZY")
     *
     * @JMS\Expose
     * @JMS\Groups({"PublicationComments"})
     */
    protected $comments;

    /**
     * @ORM\OneToOne(targetEntity="NaturaPass\PublicationBundle\Entity\PublicationMedia", cascade={"persist", "remove"}, orphanRemoval=true)
     *
     * @JMS\Expose
     * @JMS\Groups({"PublicationMedia"})
     */
    protected $media;

    /**
     * @var ArrayCollection
     *
     * @ORM\ManyToMany(targetEntity="NaturaPass\GroupBundle\Entity\Group", inversedBy="publications", fetch="EXTRA_LAZY");
     * @ORM\JoinTable(name="group_has_publication")
     *
     * @JMS\Expose
     * @JMS\Groups({"PublicationGroup"})
     */
    protected $groups;

    /**
     * @var ArrayCollection
     *
     * @ORM\ManyToMany(targetEntity="NaturaPass\UserBundle\Entity\User", inversedBy="publications", fetch="EXTRA_LAZY");
     * @ORM\JoinTable(name="user_has_publication")
     *
     * @JMS\Expose
     */
    protected $shareuser;

    /**
     * @var ArrayCollection
     *
     * @ORM\ManyToMany(targetEntity="NaturaPass\LoungeBundle\Entity\Lounge", inversedBy="publications", fetch="EXTRA_LAZY");
     * @ORM\JoinTable(name="lounge_has_publication")
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
     * @JMS\Groups({"PublicationLess"})
     */
    protected $created;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="updated", type="datetime")
     * @ORMExtension\Timestampable(on="update")
     *
     * @JMS\Expose
     * @JMS\Groups({"PublicationLess"})
     */
    protected $updated;

    /**
     * @var Geolocation
     *
     * @ORM\OneToOne(targetEntity="NaturaPass\MainBundle\Entity\Geolocation", cascade={"persist", "remove"}, orphanRemoval=true)
     * @ORM\JoinColumn(nullable=true)
     *
     * @JMS\Expose
     * @JMS\Groups({"PublicationDetail"})
     */
    protected $geolocation;

    /**
     * @var ArrayCollection
     *
     * @ORM\OneToMany(targetEntity="NaturaPass\PublicationBundle\Entity\PublicationReport", mappedBy="publication", cascade={"persist", "remove"}, orphanRemoval=true, fetch="EXTRA_LAZY")
     * @ORM\JoinColumn(nullable=true)
     *
     * @JMS\Expose
     * @JMS\Groups({"PublicationDetail"})
     */
    protected $reports;

    /**
     * @ORM\OneToOne(targetEntity="NaturaPass\MainBundle\Entity\Sharing", cascade={"persist", "remove"}, orphanRemoval=true)
     *
     * @JMS\Expose
     * @JMS\Groups({"PublicationDetail"})
     */
    protected $sharing;

    /**
     * @ORM\OneToMany(targetEntity="NaturaPass\ObservationBundle\Entity\Observation", cascade={"persist", "remove"}, orphanRemoval=true, mappedBy="publication", fetch="EXTRA_LAZY")
     *
     * @JMS\Expose
     * @JMS\Groups({"PublicationObservations"})
     */
    protected $observations;

    /**
     * @ORM\ManyToOne(targetEntity="Admin\SentinelleBundle\Entity\Locality", inversedBy="publications")
     * @ORM\JoinColumn(nullable=true, onDelete="SET NULL")
     *
     * @JMS\Expose
     * @JMS\Groups({"PublicationLocality"})
     */
    protected $locality;

    /**
     * @var PublicationColor
     *
     * @ORM\ManyToOne(targetEntity="NaturaPass\PublicationBundle\Entity\PublicationColor",inversedBy="publicationcolor")
     * @ORM\JoinColumn(nullable=true)
     *
     * @JMS\Expose
     * @JMS\Groups({"PublicationDetail"})
     */
    protected $publicationcolor;

    /**
     * @var string
     *
     * @ORM\Column(name="guid", type="text", nullable=true)
     */
    protected $guid;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->groups = new ArrayCollection();
        $this->shareuser = new ArrayCollection();
        $this->hunts = new ArrayCollection();
        $this->comments = new ArrayCollection();
        $this->medias = new ArrayCollection();
        $this->reports = new ArrayCollection();
        $this->actions = new ArrayCollection();
        $this->observations = new ArrayCollection();
    }

    /**
     * @return mixed
     */
    public function getLocality()
    {
        return $this->locality;
    }

    /**
     * @param mixed $locality
     * @return Publication
     */
    public function setLocality($locality)
    {
        $this->locality = $locality;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getObservations()
    {
        return $this->observations;
    }

    /**
     * @param mixed $observations
     * @return Publication
     */
    public function setObservations($observations)
    {
        $this->observations = $observations;

        return $this;
    }

    /**
     * Récupère les actions liées à un utilisateur
     *
     * @param integer|boolean $state
     *
     * @return ArrayCollection|\NaturaPass\PublicationBundle\Entity\PublicationAction[]
     */
    public function getActions($state = false)
    {
        if ($state) {
            $criteria = Criteria::create();

            $criteria->where($criteria->expr()->eq("state", $state));

            return $this->actions->matching($criteria);
        }

        return $this->actions;
    }

    public function isAction($user, $state)
    {
        $criteria = Criteria::create();

        $criteria->where($criteria->expr()->eq("state", $state))
            ->andWhere($criteria->expr()->eq("user", $user));

        return count($this->actions->matching($criteria));
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
     * Set content
     *
     * @param string $content
     * @return Publication
     */
    public function setContent($content)
    {
        $this->content = $content;

        return $this;
    }

    /**
     * Set legend
     *
     * @param string $legend
     * @return Publication
     */
    public function setLegend($legend)
    {
        $this->legend = $legend;

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
     * Get legend
     *
     * @return string
     */
    public function getLegend()
    {
        return $this->legend;
    }

    /**
     * Set date
     *
     * @param \DateTime $date
     * @return Publication
     */
    public function setDate($date)
    {
        $this->date = $date;

        return $this;
    }

    /**
     * Get date
     *
     * @return \DateTime
     */
    public function getDate()
    {
        return $this->date;
    }

    /**
     * Set created
     *
     * @param \DateTime $created
     * @return Publication
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
     * @return Publication
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
     * Set geolocation
     *
     * @param null|Geolocation $geolocation
     * @return Publication
     */
    public function setGeolocation($geolocation)
    {
        $this->geolocation = $geolocation;

        return $this;
    }

    /**
     * Get geolocation
     *
     * @return null|Geolocation
     */
    public function getGeolocation()
    {
        return $this->geolocation;
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
     * @param \NaturaPass\UserBundle\Entity\User $user
     * @return \NaturaPass\PublicationBundle\Entity\PublicationReport
     */
    public function getLastReport(User $user)
    {
        $criteria = Criteria::create();

        $criteria->where($criteria->expr()->eq("user", $user));

        return $this->reports->matching($criteria)->last();
    }

    /**
     * check report
     *
     * @param User $user
     * @return boolean
     */
    public function isReported(User $user)
    {
        return is_object($this->getLastReport($user));
    }

    /**
     * Add report
     *
     * @param \NaturaPass\PublicationBundle\Entity\PublicationReport $report
     * @return Group
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
     */
    public function removeReport(PublicationReport $report)
    {
        $this->reports->removeElement($report);
    }

    /**
     * Add comment
     *
     * @param \NaturaPass\PublicationBundle\Entity\PublicationComment $comment
     * @return Publication
     */
    public function addComment(PublicationComment $comment)
    {
        $this->comments[] = $comment;

        return $this;
    }

    /**
     * Remove comment
     *
     * @param \NaturaPass\PublicationBundle\Entity\PublicationComment $comment
     */
    public function removeComment(PublicationComment $comment)
    {
        $this->comments->removeElement($comment);
    }

    /**
     * Get comments
     *
     * @return ArrayCollection|\NaturaPass\PublicationBundle\Entity\PublicationComment[]
     */
    public function getComments()
    {
        return $this->comments;
    }

    /**
     * Set owner
     *
     * @param \NaturaPass\UserBundle\Entity\User $owner
     * @return Publication
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
     * Add action
     *
     * @param \NaturaPass\PublicationBundle\Entity\PublicationAction $action
     * @return Publication
     */
    public function addAction(PublicationAction $action)
    {
        $this->actions[] = $action;

        return $this;
    }

    /**
     * Remove actions
     *
     * @param \NaturaPass\PublicationBundle\Entity\PublicationAction $action
     *
     * @return Publication
     */
    public function removeAction(PublicationAction $action)
    {
        $this->actions->removeElement($action);

        return $this;
    }

    /**
     * Set sharing
     *
     * @param \NaturaPass\MainBundle\Entity\Sharing $sharing
     * @return Publication
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
     * @param boolean $landmark
     */
    public function setLandmark($landmark)
    {
        $this->landmark = $landmark;
    }

    /**
     * @return boolean
     */
    public function isLandmark()
    {
        return $this->landmark;
    }

    /**
     * @param string $facebook_id
     */
    public function setFacebookId($facebook_id)
    {
        $this->facebook_id = $facebook_id;
    }

    /**
     * @return string
     */
    public function getFacebookId()
    {
        return $this->facebook_id;
    }

    /**
     * Get media
     *
     * @return \NaturaPass\PublicationBundle\Entity\PublicationMedia
     */
    public function getMedia()
    {
        return $this->media;
    }

    /**
     * Set media
     *
     * @param \NaturaPass\PublicationBundle\Entity\PublicationMedia $media
     * @return \NaturaPass\PublicationBundle\Entity\Publication
     */
    public function setMedia(PublicationMedia $media)
    {
        $this->media = $media;
        return $this;
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
     * @return Lounge
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
     * Get hunts
     *
     * @return ArrayCollection|\NaturaPass\ObservationBundle\Entity\Observation[]
     */
    public function hasCategories($aCategory)
    {
        return $this->observations->filter(function (\NaturaPass\ObservationBundle\Entity\Observation $observation) use ($aCategory) {
            return (in_array($observation->getCategory()->getId(), $aCategory));
        })->first();
    }

    public function getLastComment()
    {
        return $this->comments->last();
    }

    public function getFirstWordLastComment()
    {
        $nb_mot = 7;
        $comment = $this->getLastComment()->getContent();
        $tab = explode(' ', $comment, $nb_mot + 1);
        $point = '';
        if (count($tab) >= 7)
            $point = '...';
        unset($tab[$nb_mot]);
        return implode(' ', $tab) . $point;
    }

    public function getNLastOwnerComment($n = 10)
    {
        $nb_comment = count($this->comments->toArray());
        $nb = ($nb_comment < $n) ? $nb_comment : $n;
        $comments = $this->comments->slice($nb_comment - $nb, $nb - 1);

        $owners = new ArrayCollection();

        foreach ($comments as $comment) {
            if (!$owners->contains($comment->getOwner())) {
                $owners->add($comment->getOwner());
            }
        }

        return $owners;
    }


    /**
     * Get landmark
     *
     * @return boolean
     */
    public function getLandmark()
    {
        return $this->landmark;
    }

    /**
     * Add observation
     *
     * @param \NaturaPass\ObservationBundle\Entity\Observation $observation
     *
     * @return Publication
     */
    public function addObservation(\NaturaPass\ObservationBundle\Entity\Observation $observation)
    {
        $this->observations[] = $observation;

        return $this;
    }

    /**
     * Remove observation
     *
     * @param \NaturaPass\ObservationBundle\Entity\Observation $observation
     */
    public function removeObservation(\NaturaPass\ObservationBundle\Entity\Observation $observation)
    {
        $this->observations->removeElement($observation);
    }

    /**
     * Set publicationcolor
     *
     * @param \NaturaPass\MainBundle\Entity\PublicationColor $publicationcolor
     *
     * @return Publication
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

    /**
     * Set guid
     *
     * @param string $guid
     * @return Message
     */
    public function setGuid($guid)
    {
        $this->guid = $guid;
    
        return $this;
    }

    /**
     * Get guid
     *
     * @return string 
     */
    public function getGuid()
    {
        return $this->guid;
    }

    public function getLastUpdated(User $connected)
    {
        $updated = $this->getUpdated();
        if (is_object($this->getOwner())) {
            $friendUpdate = $this->getOwner()->friendshipWithUpdate($connected);
            if ($friendUpdate > $updated) {
                $updated = $friendUpdate;
            }
        }
        if ($this->getObservations()->count()) {
            $observation = $this->getObservations()->first();
            if ($observation->getUpdated() > $updated) {
                $updated = $observation->getUpdated();
            }

            if (!is_null($observation->getCategory()) && $observation->getCategory()->getUpdated() > $updated) {
                $updated = $observation->getCategory()->getUpdated();
            }
        }
        if (is_object($this->getGeolocation())) {
            if ($this->getGeolocation()->getCreated() > $updated) {
                $updated = $this->getGeolocation()->getCreated();
            }
        }
        foreach ($this->getGroups() as $group) {
            if ($updated < $group->getLastUpdated()) {
                $updated = $group->getLastUpdated();
            }
        }
        return $updated;
    }

    public function getSearch()
    {
        $utf8 = array(
            '/[áàâãªä]/u' => 'a',
            '/[ÁÀÂÃÄ]/u' => 'A',
            '/[ÍÌÎÏ]/u' => 'I',
            '/[íìîï]/u' => 'i',
            '/[éèêë]/u' => 'e',
            '/[ÉÈÊË]/u' => 'E',
            '/[óòôõºö]/u' => 'o',
            '/[ÓÒÔÕÖ]/u' => 'O',
            '/[úùûü]/u' => 'u',
            '/[ÚÙÛÜ]/u' => 'U',
            '/ç/' => 'c',
            '/Ç/' => 'C',
            '/ñ/' => 'n',
            '/Ñ/' => 'N',
            '/–/' => '-', // UTF-8 hyphen to "normal" hyphen
            '/[’‘‹›‚]/u' => ' ', // Literally a single quote
            '/[“”«»„]/u' => ' ', // Double quote
            '/ /' => ' ', // nonbreaking space (equiv. to 0x160)
        );

        $return = "";
        if ($this->getLegend()) {
            $return .= $this->getLegend();
        }
        if ($this->getContent()) {
            if ($return != "") {
                $return .= " - ";
            }
            $return .= $this->getContent();
        }
        $observations = $this->getObservations();
        if (count($observations)) {
            if ($return != "") {
                $return .= " - ";
            }
            $observation = $observations[0];
            $tree = "";
            if (!is_null($observation->getCategory())) {
                $tree = $observation->getCategory()->getPath();
            }
            if ($observation->getSpecific() == 1 && !is_null($observation->getAnimal())) {
                $tree .= "/" . $observation->getAnimal()->getName_fr();
            }
            $return .= $tree;
        }
        if ($this->getOwner()) {
            if ($return != "") {
                $return .= " - ";
            }
            $return .= $this->getOwner()->getFullName();
            $return .= " - " . $this->getOwner()->getLastname() . " " . $this->getOwner()->getFirstname();
        }
        return strtolower(preg_replace(array_keys($utf8), array_values($utf8), $return));
    }

    
}
