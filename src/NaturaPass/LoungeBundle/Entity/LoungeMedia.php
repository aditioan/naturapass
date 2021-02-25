<?php

namespace NaturaPass\LoungeBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Query\Expr\Base;
use NaturaPass\MediaBundle\Entity\BaseMedia;
use Symfony\Component\Validator\Constraints as Assert;
use JMS\Serializer\Annotation as JMS;
use Gedmo\Mapping\Annotation as ORMExtension;

/**
 * @ORM\Table(name="lounge_has_media")
 * @ORM\Entity()
 * @ORM\HasLifecycleCallbacks
 * @JMS\ExclusionPolicy("all")
 */
class LoungeMedia extends \NaturaPass\MediaBundle\Entity\BaseMedia {

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
            case BaseMedia::TYPE_IMAGE:
                return 'uploads/lounges/images/original/';
            case BaseMedia::TYPE_VIDEO:
                return 'uploads/lounges/videos/original/';
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
}
