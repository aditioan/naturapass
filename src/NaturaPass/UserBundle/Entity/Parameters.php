<?php

namespace NaturaPass\UserBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation as JMS;
use Gedmo\Mapping\Annotation as ORMExtension;
use NaturaPass\MainBundle\Entity\Sharing;
use \Doctrine\Common\Collections\ArrayCollection;

/**
 * Parameters
 *
 * @ORM\Table(name="Parameters")
 * @ORM\Entity()
 * @ORM\HasLifecycleCallbacks
 * @JMS\ExclusionPolicy("all")
 */
class Parameters
{

    /**
     * @var integer
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     *
     * @JMS\Expose
     * @JMS\Groups({"UserParameters"})
     */
    protected $id;

    /**
     * @ORM\Column(name="help", type="boolean", nullable=false, options={"default": true})
     */
    protected $help = true;

    /**
     * @ORM\Column(name="friends", type="boolean", nullable=false, options={"default": true})
     */
    protected $friends = true;

    /**
     * @ORM\Column(name="sharingFilter", type="integer", nullable=true, options={"default": 3})
     */
    protected $sharingFilter = true;

    /**
     * @ORM\OneToOne(targetEntity="NaturaPass\MainBundle\Entity\Sharing", cascade={"persist", "remove"})
     *
     * @JMS\Expose
     * @JMS\Groups({"UserParametersDetail"})
     */
    protected $publicationSharing;

    /**
     * @ORM\OneToMany(targetEntity="NaturaPass\UserBundle\Entity\ParametersEmail", cascade={"persist", "remove"}, mappedBy="parameters")
     *
     * @JMS\Expose
     * @JMS\Groups({"UserParametersEmails"})
     *
     * @var \NaturaPass\UserBundle\Entity\ParametersEmail[]
     */
    protected $emails;

    /**
     * @ORM\OneToMany(targetEntity="NaturaPass\UserBundle\Entity\ParametersFilter", cascade={"persist", "remove"}, mappedBy="parameters")
     *
     * @JMS\Expose
     * @JMS\Groups({"UserParametersFilter"})
     *
     * @var \NaturaPass\UserBundle\Entity\ParametersFilter[]
     */
    protected $filters;

    /**
     * @ORM\OneToMany(targetEntity="NaturaPass\UserBundle\Entity\ParametersNotification", cascade={"persist", "remove"}, mappedBy="parameters")
     *
     * @JMS\Expose
     * @JMS\Groups({"UserParametersNotifications"})
     *
     * @var \NaturaPass\UserBundle\Entity\ParametersNotification[]
     */
    protected $notifications;

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

    public function __construct()
    {
        $publication = new Sharing();
        $publication->setShare(Sharing::NATURAPASS);

        $this->publicationSharing = $publication;
        $this->notifications = new ArrayCollection();
    }

    /**
     * Retourne la préférence d'email sauvegardé pour l'utilisateur
     *
     * @param string $type
     *
     * @return ParametersEmail|null
     */
    public function getEmailByType($type)
    {
        foreach ($this->emails as $email) {
            if ($email->getEmail()->getType() === $type) {
                return $email;
            }
        }

        return null;
    }

    /**
     * Retourne la préférence des notifications sauvegardé pour l'utilisateur
     *
     * @param string $type
     *
     * @return ParametersNotification|null
     */
    public function getNotificationByType($type, $object_id = 0)
    {
        if ($object_id != 0) {
            foreach ($this->notifications as $notification) {
                if ($notification->getType() === $type && $notification->getObjectID() == 0 && $notification->getWanted() == 0) {
                    return $notification;
                }
            }
        }
        
        foreach ($this->notifications as $notification) {
            if ($notification->getType() === $type && $notification->getObjectID() == $object_id) {
                return $notification;
            }
        }

        return null;
    }

    public function getId()
    {
        return $this->id;
    }

    /**
     *
     * @return \NaturaPass\MainBundle\Entity\Sharing
     */
    public function getPublicationSharing()
    {
        return $this->publicationSharing;
    }

    public function setPublicationSharing(Sharing $publicationSharing)
    {
        $this->publicationSharing = $publicationSharing;
        return $this;
    }

    /**
     * Set created
     *
     * @param \DateTime $created
     * @return Parameters
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
     * @return Parameters
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
     * Add email
     *
     * @param \NaturaPass\UserBundle\Entity\ParametersEmail $email
     *
     * @return \NaturaPass\UserBundle\Entity\Parameters
     */
    public function addEmail(ParametersEmail $email)
    {
        $this->emails[] = $email;

        return $this;
    }

    /**
     * Add filter
     *
     * @param \NaturaPass\UserBundle\Entity\ParametersFilter $filter
     *
     * @return \NaturaPass\UserBundle\Entity\Parameters
     */
    public function addFilter(ParametersFilter $filter)
    {
        $this->filters[] = $filter;

        return $this;
    }

    /**
     * Add email
     *
     * @param \NaturaPass\UserBundle\Entity\ParametersNotification $notification
     *
     * @return \NaturaPass\UserBundle\Entity\Parameters
     */
    public function addNotification(ParametersEmail $notification)
    {
        $this->notifications[] = $notification;

        return $this;
    }

    /**
     * Remove email
     *
     * @param \NaturaPass\UserBundle\Entity\ParametersEmail $email
     */
    public function removeEmail(ParametersEmail $email)
    {
        $this->emails->removeElement($email);
    }

    /**
     * Remove filter
     *
     * @param \NaturaPass\UserBundle\Entity\ParametersFilter $filter
     */
    public function removeFilter(ParametersFilter $filter)
    {
        $this->filters->removeElement($filter);
    }

    /**
     * Remove notification
     *
     * @param \NaturaPass\UserBundle\Entity\ParametersNotification $notification
     */
    public function removeNotification(ParametersEmail $notification)
    {
        $this->notifications->removeElement($notification);
    }

    /**
     * Get emails
     *
     * @return \NaturaPass\UserBundle\Entity\ParametersEmail[]
     */
    public function getEmails()
    {
        return $this->emails;
    }

    /**
     * Get filters
     *
     * @return \NaturaPass\UserBundle\Entity\ParametersFilter[]
     */
    public function getFilters()
    {
        return $this->filters;
    }

    /**
     * Get notification
     *
     * @return \NaturaPass\UserBundle\Entity\ParametersNotification[]
     */
    public function getNotifications()
    {
        return $this->notifications;
    }

    /**
     * @param boolean $help
     *
     * @return \NaturaPass\UserBundle\Entity\Parameters
     */
    public function setHelp($help)
    {
        $this->help = $help;
        return $this;
    }

    /**
     * @return boolean
     */
    public function getHelp()
    {
        return $this->help;
    }

    /**
     * @param boolean $friends
     *
     * @return \NaturaPass\UserBundle\Entity\Parameters
     */
    public function setFriends($friends)
    {
        $this->friends = $friends;

        return $this;
    }

    /**
     * @return boolean
     */
    public function getFriends()
    {
        return $this->friends;
    }

    /**
     * @param integer $sharingFilter
     *
     * @return \NaturaPass\UserBundle\Entity\Parameters
     */
    public function setSharingFilter($sharingFilter)
    {
        $this->sharingFilter = $sharingFilter;
        return $this;
    }

    /**
     * @return integer
     */
    public function getSharingFilter()
    {
        return $this->sharingFilter;
    }

}
