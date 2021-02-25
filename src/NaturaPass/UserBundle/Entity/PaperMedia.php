<?php

namespace NaturaPass\UserBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use NaturaPass\MediaBundle\Entity\BaseMedia;
use NaturaPass\MediaBundle\Entity\BaseMediaPdf;
use NaturaPass\MediaBundle\Entity\Media;
use Symfony\Component\Validator\Constraints as Assert;
use JMS\Serializer\Annotation as JMS;
use Gedmo\Mapping\Annotation as ORMExtension;

/**
 * DogMedia
 *
 * @ORM\Table(name="paper_has_media")
 * @ORM\Entity()
 * @ORM\HasLifecycleCallbacks
 * @JMS\ExclusionPolicy("all")
 */
class PaperMedia extends BaseMediaPdf
{


    /**
     * @ORM\ManyToOne(targetEntity="NaturaPass\UserBundle\Entity\PaperParameter", inversedBy="medias")
     * @ORM\JoinColumn(nullable=false)
     */
    protected $paper;
    protected $width;
    protected $height;

    public function __construct()
    {
//        parent::__construct();
    }

    public function getAbsolutePath()
    {
        return $this->getRootUploadDir() . $this->name;
    }

    public function getWebPath()
    {
        return '/' . $this->getUploadDir() . $this->name;
    }

    public function getUploadDir()
    {
        switch ($this->type) {
            case BaseMediaPdf::TYPE_IMAGE:
                return 'uploads/papers/images/original/';
            case BaseMediaPdf::TYPE_VIDEO:
                return 'uploads/papers/videos/original/';
            case BaseMediaPdf::TYPE_PDF:
                return 'uploads/papers/pdfs/original/';
        }

        return false;
    }

    public function getWidth($folder = 'resize')
    {
        if ($this->getType() == self::TYPE_IMAGE && file_exists($this->getAbsolutePath())) {
            $image = new \Imagick(str_replace('original', $folder, BaseMedia::removeFilePathExtension($this->getAbsolutePath())) . ".jpeg");
            $d = $image->getImageGeometry();

            return $d['width'];
        }
        return false;
    }

    public function getHeight($folder = 'resize')
    {
        if ($this->getType() == self::TYPE_IMAGE && file_exists($this->getAbsolutePath())) {
            $image = new \Imagick(str_replace('original', $folder, BaseMedia::removeFilePathExtension($this->getAbsolutePath())) . ".jpeg");
            $d = $image->getImageGeometry();

            return $d['height'];
        }
        return false;
    }

    protected function getRootUploadDir()
    {
        return __DIR__ . '/../../../../web/' . $this->getUploadDir();
    }

    public function __clone()
    {
        if ($this->id) {
            $this->id = null;
            $this->setGeolocation(null);
            $this->setSharing(null);
        }
    }

    /**
     * Set paper
     *
     * @param \NaturaPass\UserBundle\Entity\PaperParameter $paper
     * @return DogMedia
     */
    public function setPaper(PaperParameter $paper)
    {
        $this->paper = $paper;

        return $this;
    }

    /**
     * Get paper
     *
     * @return \NaturaPass\UserBundle\Entity\PaperParameter
     */
    public function getPaper()
    {
        return $this->paper;
    }
}
