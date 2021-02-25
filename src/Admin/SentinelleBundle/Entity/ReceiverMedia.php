<?php

namespace Admin\SentinelleBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use JMS\Serializer\Annotation as JMS;
use \NaturaPass\MediaBundle\Entity\BaseMedia;
use Gedmo\Mapping\Annotation as ORMExtension;

/**
 * @ORM\Table(name="receiver_has_media")
 * @ORM\Entity()
 * @ORM\HasLifecycleCallbacks
 * @JMS\ExclusionPolicy("all")
 */
class ReceiverMedia extends BaseMedia {

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
                return 'uploads/receivers/images/original/';
            case BaseMedia::TYPE_VIDEO:
                return 'uploads/receivers/videos/original/';
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
