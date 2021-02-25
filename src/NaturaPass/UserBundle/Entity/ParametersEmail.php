<?php
namespace NaturaPass\UserBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation as JMS;
use Gedmo\Mapping\Annotation as ORMExtension;

/**
 * Parameters
 *
 * @ORM\Table(name="parameters_has_email")
 * @ORM\Entity()
 * @ORM\HasLifecycleCallbacks
 * @JMS\ExclusionPolicy("all")
 */
class ParametersEmail {

    /**
     * @var Parameters
     *
     * @ORM\ManyToOne(targetEntity="NaturaPass\UserBundle\Entity\Parameters", inversedBy="emails")
     * @ORM\Id
     */
    protected $parameters;

    /**
     * @ORM\ManyToOne(targetEntity="NaturaPass\EmailBundle\Entity\EmailModel")
     * @ORM\Id
     */
    protected $email;

    /**
     * @var boolean
     * @ORM\Column(name="wanted", type="boolean")
     */
    protected $wanted;

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
     * Set wanted
     *
     * @param boolean $wanted
     * @return ParametersEmail
     */
    public function setWanted($wanted)
    {
        $this->wanted = $wanted;
    
        return $this;
    }

    /**
     * Get wanted
     *
     * @return boolean 
     */
    public function getWanted()
    {
        return $this->wanted;
    }

    /**
     * Set created
     *
     * @param \DateTime $created
     * @return ParametersEmail
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
     * @return ParametersEmail
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
     * Set parameters
     *
     * @param \NaturaPass\UserBundle\Entity\Parameters $parameters
     * @return ParametersEmail
     */
    public function setParameters(\NaturaPass\UserBundle\Entity\Parameters $parameters)
    {
        $this->parameters = $parameters;
    
        return $this;
    }

    /**
     * Get parameters
     *
     * @return \NaturaPass\UserBundle\Entity\Parameters 
     */
    public function getParameters()
    {
        return $this->parameters;
    }

    /**
     * Set email
     *
     * @param \NaturaPass\EmailBundle\Entity\EmailModel $email
     * @return ParametersEmail
     */
    public function setEmail(\NaturaPass\EmailBundle\Entity\EmailModel $email)
    {
        $this->email = $email;
    
        return $this;
    }

    /**
     * Get email
     *
     * @return \NaturaPass\EmailBundle\Entity\EmailModel 
     */
    public function getEmail()
    {
        return $this->email;
    }
}