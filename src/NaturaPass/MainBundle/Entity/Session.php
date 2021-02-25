<?php

namespace NaturaPass\MainBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Sharing
 *
 * @ORM\Table()
 * @ORM\Entity()
 */
class Session {

    /**
     * @var string
     *
     * @ORM\Column(name="session_id", type="string", length=255)
     * @ORM\Id
     */
    protected $id;

    /**
     * @var string
     *
     * @ORM\Column(name="session_value", type="blob")
     */
    protected $value;

    /**
     * @var integer
     *
     * @ORM\Column(name="session_time", type="integer")
     */
    protected $time;

    /**
     * @var integer
     *
     * @ORM\Column(name="sess_lifetime", type="integer")
     */
    protected $lifetime;

    /**
     * @param $id
     * @return $this
     */
    public function setId($id)
    {
        $this->id = $id;

        return $this;
    }

    /**
     * @return string
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param int $time
     *
     * @return $this
     */
    public function setTime($time)
    {
        $this->time = $time;

        return $this;
    }

    /**
     * @return int
     */
    public function getTime()
    {
        return $this->time;
    }

    /**
     * @param mixed $value
     *
     * @return $this
     */
    public function setValue($value)
    {
        $this->value = $value;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getValue()
    {
        return $this->value;
    }
}