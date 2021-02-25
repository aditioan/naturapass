<?php

namespace NaturaPass\PublicationBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as ORMExtension;

/**
 * PublicationAction
 *
 * @ORM\Table(name="publication_comment_has_action")
 * @ORM\Entity()
 */
class PublicationCommentAction {

    const STATE_LIKE = 100;
    const STATE_UNLIKE = 101;

    /**
     * @ORM\Id
     * @ORM\ManyToOne(targetEntity="NaturaPass\PublicationBundle\Entity\PublicationComment", inversedBy="actions")
     * @ORM\JoinColumn(nullable=false)
     */
    protected $comment;

    /**
     * @ORM\Id
     * @ORM\ManyToOne(targetEntity="NaturaPass\UserBundle\Entity\User")
     * @ORM\JoinColumn(nullable=false)
     */
    protected $user;

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
     */
    protected $updated;

    /**
     * Set publication
     *
     * @param \NaturaPass\PublicationBundle\Entity\PublicationComment $comment
     * @return PublicationCommentAction
     */
    public function setComment(\NaturaPass\PublicationBundle\Entity\PublicationComment $comment) {
        $this->comment = $comment;

        return $this;
    }

    /**
     * Get publication
     *
     * @return \NaturaPass\PublicationBundle\Entity\PublicationComment
     */
    public function getComment() {
        return $this->comment;
    }

    /**
     * Set user
     *
     * @param \NaturaPass\UserBundle\Entity\User $user
     * @return PublicationCommentAction
     */
    public function setUser(\NaturaPass\UserBundle\Entity\User $user) {
        $this->user = $user;

        return $this;
    }

    /**
     * Get user
     *
     * @return \NaturaPass\UserBundle\Entity\User
     */
    public function getUser() {
        return $this->user;
    }

    /**
     * Set state
     *
     * @param integer $state
     * @return PublicationCommentAction
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
     * @return PublicationCommentAction
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

}
