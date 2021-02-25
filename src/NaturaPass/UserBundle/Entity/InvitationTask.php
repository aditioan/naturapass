<?php

namespace NaturaPass\UserBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use JMS\Serializer\Annotation as JMS;

class InvitationTask {

    /**
     * @var integer
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     *
     * @JMS\Expose
     */
    protected $id;

    /**
     * @ORM\OneToMany(targetEntity="Invitation", cascade={"persist"})
     *
     */
    protected $invitations;

    public function __construct() {
        $this->invitations = new ArrayCollection();
    }

    public function getInvitations() {
        return $this->invitations;
    }

    public function setInvitations(ArrayCollection $invitations) {
        /* foreach ($invitations as $invitation) {
          $invitation->addInvitationTask($this);
          } */

        $this->invitations = $invitations;
    }

}
