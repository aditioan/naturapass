<?php

namespace NaturaPass\PublicationBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Criteria;
use JMS\Serializer\Annotation as JMS;
use Gedmo\Mapping\Annotation as ORMExtension;

/**
 * PublicationComment
 *
 * @ORM\Table(name="publication_has_comment")
 * @ORM\Entity()
 * @JMS\ExclusionPolicy("all")
 */
class PublicationComment {

    /**
     * @var integer
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     *
     * @JMS\Expose
     * @JMS\Groups({"PublicationCommentLess"})
     */
    protected $id;

    /**
     * @ORM\ManyToOne(targetEntity="NaturaPass\PublicationBundle\Entity\Publication", inversedBy="comments")
     * @ORM\JoinColumn(nullable=false)
     *
     * @JMS\Expose
     * @JMS\Groups({"PublicationCommentDetail"})
     */
    protected $publication;

    /**
     * @ORM\OneToMany(targetEntity="NaturaPass\PublicationBundle\Entity\PublicationCommentAction", mappedBy="comment", cascade={"persist", "remove"}, fetch="EXTRA_LAZY", orphanRemoval=true)
     *
     * @JMS\Expose
     * @JMS\Groups({"PublicationCommentDetail"})
     */
    protected $actions;

    /**
     * @ORM\ManyToOne(targetEntity="NaturaPass\UserBundle\Entity\User")
     * @ORM\JoinColumn(nullable=false)
     *
     * @JMS\Expose
     * @JMS\Groups({"PublicationCommentLess"})
     */
    protected $owner;

    /**
     * @var string
     *
     * @ORM\Column(name="content", type="text")
     *
     * @JMS\Expose
     * @JMS\Groups({"PublicationCommentLess"})
     */
    protected $content;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="created", type="datetime")
     * @ORMExtension\Timestampable(on="create")
     *
     * @JMS\Expose
     * @JMS\Groups({"PublicationCommentLess"})
     */
    protected $created;

    public function __construct() {
        $this->actions = new ArrayCollection();
    }


    /**
     * Retourne toutes les actions effectuées sur un commentaire
     *
     * @param integer|boolean $state
     *
     * @return \NaturaPass\PublicationBundle\Entity\PublicationAction[]
     */
    public function getActions($state = false) {
        if ($state) {
            $actions = new ArrayCollection();

            $this->actions->filter(function($element) use ($actions, $state) {
                    if ($element->getState() === $state) {
                        $actions->add($element);
                        return true;
                    }
                    return false;
                });

            return $actions;
        }

        return $this->actions;
    }

    /**
     *
     *
     * @param $user
     * @param integer $state
     *
     * @return integer
     */
    public function isAction($user, $state) {
        $criteria = Criteria::create();

        $criteria->where($criteria->expr()->eq("state", $state))
            ->andWhere($criteria->expr()->eq("user", $user));

        return count($this->actions->matching($criteria));
    }

    /**
     * Set created
     *
     * @param \DateTime $created
     * @return Publication
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

    public function getId() {
        return $this->id;
    }

    /**
     * Set content
     *
     * @param string $content
     * @return PublicationComment
     */
    public function setContent($content) {
        $this->content = $content;

        return $this;
    }

    /**
     * Get content
     *
     * @return string
     */
    public function getContent() {
        return $this->content;
    }

    /**
     * Set publication
     *
     * @param \NaturaPass\PublicationBundle\Entity\Publication $publication
     * @return PublicationComment
     */
    public function setPublication(\NaturaPass\PublicationBundle\Entity\Publication $publication) {
        $this->publication = $publication;

        return $this;
    }

    /**
     * Get publication
     *
     * @return \NaturaPass\PublicationBundle\Entity\Publication
     */
    public function getPublication() {
        return $this->publication;
    }

    /**
     * Set owner
     *
     * @param \NaturaPass\UserBundle\Entity\User $owner
     * @return PublicationComment
     */
    public function setOwner(\NaturaPass\UserBundle\Entity\User $owner) {
        $this->owner = $owner;

        return $this;
    }

    /**
     * Get owner
     *
     * @return \NaturaPass\UserBundle\Entity\User
     */
    public function getOwner() {
        return $this->owner;
    }

}
