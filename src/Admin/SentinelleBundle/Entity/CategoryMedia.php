<?php

namespace Admin\SentinelleBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use NaturaPass\MediaBundle\Entity\BaseMedia;
use NaturaPass\MediaBundle\Entity\Media;
use Symfony\Component\Validator\Constraints as Assert;
use JMS\Serializer\Annotation as JMS;
use Gedmo\Mapping\Annotation as ORMExtension;

/**
 * Media
 *
 * @ORM\Table(name="category_has_media")
 * @ORM\Entity()
 * @ORM\HasLifecycleCallbacks
 * @JMS\ExclusionPolicy("all")
 */
class CategoryMedia extends BaseMedia {

    public function getAbsolutePath() {
        return $this->getRootUploadDir() . $this->name;
    }

    public function getWebPath() {
        return '/' . $this->getUploadDir() . $this->name;
    }

    public function getUploadDir() {
        switch ($this->type) {
            case Media::TYPE_IMAGE:
                return 'uploads/category/images/original/';
            case Media::TYPE_VIDEO:
                return 'uploads/category/videos/original/';
        }

        return false;
    }

    protected function getRootUploadDir() {
        return __DIR__ . '/../../../../web/' . $this->getUploadDir();
    }

}
