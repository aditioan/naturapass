<?php
/**
 * User: VietLH
 * Date: 27/12/16
 * Time: 13:46
 */

namespace NaturaPass\UserBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation as JMS;
use Gedmo\Mapping\Annotation as ORMExtension;
use Doctrine\Common\Collections\ArrayCollection;

/**
 * Class LoadFlag
 *
 * @ORM\Table(name="load_flag")
 * @ORM\Entity()
 * @ORM\HasLifecycleCallbacks
 * @JMS\ExclusionPolicy("all")
 */
class LoadFlag
{


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
     * @var string
     *
     * @ORM\Column(name="pub", type="text", nullable=true)
     *
     * @JMS\Expose
     */
    protected $pub;

    public function __construct()
    {
        $this->pub = new ArrayCollection();
    }

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param string $pub
     *
     * @return LoadFlag
     */
    public function setPub($pub)
    {
        $this->pub = $pub;

        return $this;
    }

    /**
     * @return \Doctrine\Common\Collections\ArrayCollection|\NaturaPass\UserBundle\Entity\LoadFlag[]
     */
    public function getPub()
    {
        return $this->pub;
    }
}