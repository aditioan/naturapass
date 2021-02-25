<?php
/**
 * Created by PhpStorm.
 * User: vincentvalot
 * Date: 22/07/14
 * Time: 08:21
 */

namespace Admin\NewsBundle\Entity;


use NaturaPass\MediaBundle\Entity\BaseMedia;

use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation as JMS;
use Gedmo\Mapping\Annotation as ORMExtension;

/**
 * News
 *
 * @ORM\Table(name="admin_slide_has_media")
 * @ORM\Entity
 */
class SlideMedia extends BaseMedia {

    public function getAbsolutePath() {
        return $this->getRootUploadDir() . $this->name;
    }

    public function getWebPath() {
        return '/' . $this->getUploadDir() . $this->name;
    }

    public function getUploadDir() {
        switch ($this->type) {
            case BaseMedia::TYPE_IMAGE:
                return 'img/img_slide/';
            case BaseMedia::TYPE_VIDEO:
                return 'img/img_slide/';
        }

        return false;
    }
} 