<?php

namespace NaturaPass\PublicationBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation as JMS;
use Gedmo\Mapping\Annotation as ORMExtension;

/**
 * PublicationReport
 *
 * @ORM\Table(name="`publication_has_report`")
 * @ORM\Entity()
 * @ORM\HasLifecycleCallbacks
 * @JMS\ExclusionPolicy("all")
 */
class PublicationReport {

    /**
     * @var integer
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     *
     * @JMS\Expose
     * @JMS\Groups({"PublicationReportLess"})
     */
    protected $id;

    /**
     * @ORM\ManyToOne(targetEntity="NaturaPass\UserBundle\Entity\User", inversedBy="reports")
     * @ORM\JoinColumn(nullable=false, onDelete="CASCADE")
     *
     * @JMS\Expose
     * @JMS\Groups({"PublicationReportLess"})
     */
    protected $user;

    /**
     * @ORM\ManyToOne(targetEntity="NaturaPass\PublicationBundle\Entity\Publication", inversedBy="reports")
     * @ORM\JoinColumn(nullable=false)
     *
     * @JMS\Expose
     * @JMS\Groups({"PublicationReportLess"})
     */
    protected $publication;

    /**
     * @ORM\Column(name="explanation", type="text", nullable=true)
     *
     * @JMS\Expose
     * @JMS\Groups({"PublicationReportDetail"})
     *
     */
    protected $explanation;

    /**
     * @var boolean
     *
     * @ORM\Column(name="checked", type="boolean", options={"default": false})
     *
     * @JMS\Expose
     * @JMS\Groups({"PublicationReportLess"})
     */
    protected $checked = false;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="created", type="datetime")
     * @ORMExtension\Timestampable(on="create")
     *
     * @JMS\Expose
     * @JMS\Groups({"PublicationReportLess"})
     */
    protected $created;

    /**
     * Get id
     *
     * @return integer
     */
    public function getId() {
        return $this->id;
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
     * Get publication
     *
     * @return \NaturaPass\PublicationBundle\Entity\Publication
     */
    public function getPublication() {
        return $this->publication;
    }

    /**
     * Get created
     *
     * @return \DateTime
     */
    public function getCreated() {
        return $this->created;
    }

    /**
     * Set user
     *
     * @param \NaturaPass\UserBundle\Entity\User $user
     * @return PublicationReport
     */
    public function setUser($user) {
        $this->user = $user;
        return $this;
    }

    /**
     * Set publication
     *
     * @param \NaturaPass\PublicationBundle\Entity\Publication $publication
     * @return PublicationReport
     */
    public function setPublication($publication) {
        $this->publication = $publication;
        return $this;
    }

    /**
     * Set created
     *
     * @param \DateTime $created
     * @return PublicationReport
     */
    public function setCreated(\DateTime $created) {
        $this->created = $created;
        return $this;
    }

    /**
     * @param boolean $checked
     *
     * @return $this
     */
    public function setChecked($checked)
    {
        $this->checked = $checked;
        return $this;
    }

    /**
     * @return boolean
     */
    public function getChecked()
    {
        return $this->checked;
    }

    /**
     * @param mixed $explanation
     *
     * @return $this
     */
    public function setExplanation($explanation)
    {
        $this->explanation = $explanation;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getExplanation()
    {
        return $this->explanation;
    }
}
