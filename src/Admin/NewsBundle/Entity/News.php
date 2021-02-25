<?php

namespace Admin\NewsBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation as JMS;
use Gedmo\Mapping\Annotation as ORMExtension;

/**
 * News
 *
 * @ORM\Table(name="admin_news")
 * @ORM\Entity
 */
class News {

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
    private $id;

    /**
     * @var integer
     * @ORM\Column(name="active", type="boolean")
     *
     * @JMS\Expose
     * @JMS\Groups({"NewsDetail", "NewsLess"})
     */
    private $active = false;

    /**
     * @var \DateTime
     * @ORM\Column(name="date", type="datetime")
     *
     * @JMS\Expose
     * @JMS\Groups({"NewsDetail", "NewsLess"})
     */
    private $date;

    /**
     * @var string
     *
     * @ORM\Column(name="title", type="string", length=255)
     *
     * @JMS\Expose
     * @JMS\Groups({"NewsDetail", "NewsLess"})
     */
    private $title;

    /**
     * @var string
     *
     * @ORM\Column(name="link", type="string", length=255, nullable=true)
     *
     * @JMS\Expose
     * @JMS\Groups({"NewsDetail", "NewsLess"})
     */
    private $link;

    /**
     * @var string
     *
     * @ORM\Column(name="content", type="text")
     *
     * @JMS\Expose
     * @JMS\Groups({"NewsDetail", "NewsLess"})
     */
    private $content;

    /**
     * @var NewsMedia
     *
     * @ORM\OneToOne(targetEntity="Admin\NewsBundle\Entity\NewsMedia", cascade={"persist", "remove"}, orphanRemoval=true)
     * @ORM\JoinColumn(nullable=true)
     *
     * @JMS\Expose
     * @JMS\Groups({"NewsPhoto"})
     */
    protected $photo;

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
     * Get id
     *
     * @return integer
     */
    public function getId() {
        return $this->id;
    }

    /**
     * Set title
     *
     * @param string $title
     * @return News
     */
    public function setTitle($title) {
        $this->title = $title;

        return $this;
    }

    /**
     * Get title
     *
     * @return string
     */
    public function getTitle() {
        return $this->title;
    }

    /**
     * Set link
     *
     * @param string $link
     * @return News
     */
    public function setLink($link) {
        $this->link = $link;

        return $this;
    }

    /**
     * Get link
     *
     * @return string
     */
    public function getLink() {
        return $this->link;
    }

    /**
     * Set content
     *
     * @param string $content
     * @return News
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
     * Set photo
     *
     * @param \Admin\NewsBundle\Entity\NewsMedia $photo
     * @return News
     */
    public function setPhoto(NewsMedia $photo = null) {
        $this->photo = $photo;

        return $this;
    }

    /**
     * Get photo
     *
     * @return \Admin\NewsBundle\Entity\NewsMedia
     */
    public function getPhoto() {
        return $this->photo;
    }

    /**
     * @param int $active
     *
     * @return $this
     */
    public function setActive($active) {
        $this->active = $active;

        return $this;
    }

    /**
     * @return int
     */
    public function getActive() {
        return $this->active;
    }

    /**
     * @param \DateTime $date
     *
     * @return $this
     */
    public function setDate($date) {
        $this->date = $date;

        return $this;
    }

    /**
     * @return \DateTime
     */
    public function getDate() {
        return $this->date;
    }

    /**
     * Set updated
     *
     * @param \DateTime $updated
     * @return News
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

    /**
     * Set created
     *
     * @param \DateTime $created
     * @return News
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

}
