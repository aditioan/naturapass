<?php
/**
 * Created by PhpStorm.
 * User: vincentvalot
 * Date: 17/07/14
 * Time: 10:06
 */

namespace NaturaPass\UserBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation as JMS;
use Gedmo\Mapping\Annotation as ORMExtension;

/**
 * Class DeviceDbVersion
 *
 * @ORM\Table(name="device_has_db_version")
 * @ORM\Entity()
 * @ORM\HasLifecycleCallbacks
 * @JMS\ExclusionPolicy("all")
 */
class DeviceDbVersion
{


    /**
     * @var integer
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     *
     * @JMS\Expose
     * @JMS\Groups({"DeviceDbVersionDetail"})
     */
    protected $id;

    /**
     * @var string
     *
     * @ORM\Column(name="version", type="bigint")
     *
     * @JMS\Expose
     * @JMS\Groups({"DeviceDbVersionLess"})
     */
    protected $version;

    /**
     * @var string
     *
     * @ORM\Column(name="sqlite", type="text")
     *
     * @JMS\Expose
     * @JMS\Groups({"DeviceDbVersionLess"})
     */
    protected $sqlite;


    /**
     * @var \DateTime
     *
     * @ORM\Column(name="created", type="datetime")
     * @ORMExtension\Timestampable(on="create")
     *
     * @JMS\Expose
     * @JMS\Groups({"DeviceDbVersionDetail"})
     */
    protected $created;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="updated", type="datetime")
     * @ORMExtension\Timestampable(on="update")
     *
     * @JMS\Expose
     * @JMS\Groups({"DeviceDbVersionDetail"})
     */
    protected $updated;

    public function __construct()
    {
    }

    /**
     * @param \DateTime $created
     *
     * @return DeviceDbVersion
     */
    public function setCreated($created)
    {
        $this->created = $created;

        return $this;
    }

    /**
     * @return \DateTime
     */
    public function getCreated()
    {
        return $this->created;
    }

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param \DateTime $updated
     *
     * @return DeviceDbVersion
     */
    public function setUpdated($updated)
    {
        $this->updated = $updated;

        return $this;
    }

    /**
     * @return \DateTime
     */
    public function getUpdated()
    {
        return $this->updated;
    }

    /**
     * @param bigint $version
     *
     * @return DeviceDbVersion
     */
    public function setVersion($version)
    {
        $this->version = $version;

        return $this;
    }

    /**
     * @return bigint
     */
    public function getVersion()
    {
        return $this->version;
    }

    /**
     * @param string $sqlite
     *
     * @return DeviceDbVersion
     */
    public function setSqlite($sqlite)
    {
        $this->sqlite = $sqlite;

        return $this;
    }

    /**
     * @return string
     */
    public function getSqlite()
    {
        return $this->sqlite;
    }
}