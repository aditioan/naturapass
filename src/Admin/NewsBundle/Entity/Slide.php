<?php
/**
 * Created by PhpStorm.
 * User: vincentvalot
 * Date: 22/07/14
 * Time: 08:18
 */

namespace Admin\NewsBundle\Entity;


use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation as JMS;
use Gedmo\Mapping\Annotation as ORMExtension;

/**
 * News
 *
 * @ORM\Table(name="admin_slide")
 * @ORM\Entity
 */
class Slide {
    /**
     * @var integer
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     *
     * @JMS\Expose
     * @JMS\Groups({"NewsDetail", "NewsLess"})
     */
    protected $id;

    /**
     * @var integer
     * @ORM\Column(name="sort", type="integer", options={"default"=1})
     *
     * @JMS\Expose
     * @JMS\Groups({"NewsDetail", "NewsLess"})
     */
    protected $sort = 1;

    /**
     * @var boolean
     * @ORM\Column(name="active", type="boolean")
     *
     * @JMS\Expose
     * @JMS\Groups({"NewsDetail", "NewsLess"})
     */
    protected $active = false;

    /**
     * @ORM\OneToOne(targetEntity="Admin\NewsBundle\Entity\SlideMedia", cascade={"persist", "remove"})
     *
     * @JMS\Expose
     * @JMS\Groups({"NewsDetail", "NewsLess"})
     */
    protected $media;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="created", type="datetime")
     * @ORMExtension\Timestampable(on="create")
     *
     * @JMS\Expose
     * @JMS\Groups({"NewsDetail", "NewsLess"})
     */
    protected $created;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="updated", type="datetime")
     * @ORMExtension\Timestampable(on="update")
     *
     * @JMS\Expose
     * @JMS\Groups({"NewsDetail", "NewsLess"})
     */
    protected $updated;

    /**
     * @param int $sort
     *
     * @return Slide
     */
    public function setSort($sort)
    {
        $this->sort = $sort;

        return $this;
    }

    /**
     * @return int
     */
    public function getSort()
    {
        return $this->sort;
    }

    /**
     * @param boolean $active
     *
     * @return Slide
     */
    public function setActive($active)
    {
        $this->active = $active;

        return $this;
    }

    /**
     * @return boolean
     */
    public function getActive()
    {
        return $this->active;
    }

    /**
     * @param \DateTime $created
     *
     * @return Slide
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
     * @param int $id
     *
     * @return Slide
     */
    public function setId($id)
    {
        $this->id = $id;

        return $this;
    }

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param mixed $media
     *
     * @return Slide
     */
    public function setMedia($media)
    {
        $this->media = $media;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getMedia()
    {
        return $this->media;
    }

    /**
     * @param \DateTime $updated
     *
     * @return Slide
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


} 