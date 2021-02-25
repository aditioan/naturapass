<?php

namespace NaturaPass\UserBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use NaturaPass\MediaBundle\Entity\BaseMedia;
use NaturaPass\MediaBundle\Entity\Media;
use Symfony\Component\Validator\Constraints as Assert;
use JMS\Serializer\Annotation as JMS;
use Gedmo\Mapping\Annotation as ORMExtension;

/**
 * Media
 *
 * @ORM\Table(name="user_has_media")
 * @ORM\Entity()
 * @ORM\HasLifecycleCallbacks
 * @JMS\ExclusionPolicy("all")
 */
class UserMedia extends BaseMedia {

    const STATE_NOTHING = 200;
    const STATE_PROFILE_PICTURE = 201;

    /**
     * @ORM\ManyToOne(targetEntity="NaturaPass\UserBundle\Entity\User", inversedBy="medias")
     * @ORM\JoinColumn(onDelete="CASCADE")
     *
     * @var \NaturaPass\UserBundle\Entity\User
     */
    protected $owner;

    /**
     * @return string
     */
    public function getAbsolutePath() {
        return $this->getRootUploadDir() . $this->name;
    }

    /**
     * @return bool|string
     */
    public function getUploadDir() {
        switch ($this->type) {
            case Media::TYPE_IMAGE:
                return 'uploads/users/images/original/';
            case Media::TYPE_VIDEO:
                return 'uploads/users/videos/original/';
        }

        return false;
    }

    /**
     * @return string
     */
    protected function getRootUploadDir() {
        return __DIR__ . '/../../../../web/' . $this->getUploadDir();
    }

    /**
     * @return string
     */
    public function getWebPath() {
        return '/' . $this->getUploadDir() . $this->name;
    }

    /**
     * Set state
     *
     * @param integer $state
     * @return Media
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
     * Set owner
     *
     * @param \NaturaPass\UserBundle\Entity\User $owner
     * @return UserMedia
     */
    public function setOwner(User $owner) {
        $this->owner = $owner;

        return $this;
    }

    /**
     * Get owner
     *
     * @return \NaturaPass\UserBundle\Entity\User
     */
    public function getOwner() {
        return $this->owner;
    }

}