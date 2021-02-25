<?php

namespace NaturaPass\MediaBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Validator\Constraints as Assert;
use JMS\Serializer\Annotation as JMS;
use Gedmo\Mapping\Annotation as ORMExtension;
use FFMpeg;

/**
 * Media
 *
 * @ORM\MappedSuperclass()
 * @ORM\HasLifecycleCallbacks
 * @JMS\ExclusionPolicy("all")
 */
abstract class BaseMediaPdf extends BaseMedia
{

    const TYPE_PDF = 102;

    /**
     * @ORM\PrePersist()
     * @ORM\PreUpdate()
     */
    public function preUpload()
    {
        if ($this->file instanceof File) {
            $matches = array();

            $mime = $this->file->getMimeType();
            if ($mime == 'application/octet-stream') {
                $mime = $this->file->getClientMimeType();
            }

            if (preg_match('#([a-z]*)/(.*)#', $mime, $matches)) {
                switch ($matches[1]) {
                    case 'image':
                        $this->type = self::TYPE_IMAGE;
                        $this->name = sha1(uniqid(mt_rand(), true)) . '.jpeg';
                        break;

                    case 'video':
                        $this->type = self::TYPE_VIDEO;
                        $this->name = sha1(uniqid(mt_rand(), true)) . '.' . $this->file->guessExtension();
                        break;
                    case 'application':
                        if ($matches[2] == "pdf") {
                            $this->type = self::TYPE_PDF;
                            $this->name = sha1(uniqid(mt_rand(), true)) . '.' . $this->file->guessExtension();
                            break;
                        }
                }
            }

            $this->path = '/' . $this->getUploadDir() . $this->name;
        }
    }

    /**
     * @ORM\PostPersist()
     * @ORM\PostUpdate()
     */
    public function upload()
    {
        parent::upload();
        if ($this->type === self::TYPE_PDF) {
            $pathNoExtension = self::removeFilePathExtension($this->getRootUploadDir() . $this->name);
            if (!@copy($this->getRootUploadDir() . $this->name, str_replace('original', 'resize', $pathNoExtension) . ".pdf")) {
                throw new FileException(sprintf(
                        'Could not move the file "%s" to "%s"', $this->getRootUploadDir() . $this->name, str_replace('original', 'mp4', $pathNoExtension) . ".pdf"
                ));
            }
        }
    }

    public function getUploadDir()
    {
        switch ($this->type) {
            case self::TYPE_IMAGE:
                return 'uploads/images/original/';
            case self::TYPE_VIDEO:
                return 'uploads/videos/original/';
            case self::TYPE_PDF:
                return 'uploads/pdfs/original/';
        }

        return false;
    }

}
