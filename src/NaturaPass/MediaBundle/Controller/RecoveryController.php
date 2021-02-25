<?php

namespace NaturaPass\MediaBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use NaturaPass\PublicationBundle\Entity\PublicationMedia;
use NaturaPass\MediaBundle\Entity\BaseMedia;
use FFMpeg;

class RecoveryController extends Controller {

    const TYPE_IMAGE = PublicationMedia::TYPE_IMAGE;
    const TYPE_VIDEO = PublicationMedia::TYPE_VIDEO;

    public $message = "";

    public function publicationAction() {
        $repository = $this->getDoctrine()
            ->getRepository('NaturaPassMediaBundle:PublicationMedia');
        $publicationMedias = $repository->findAll();

        foreach ($publicationMedias as $publicationMedia) {

            $o = $publicationMedia->getAbsolutePath();
            $r = str_replace('original', 'resize', $o);
            $t = str_replace('original', 'thumb', $o);
            $this->delFile($r);
            $this->delFile($t);

            if (file_exists($o)) {
                $this->message .= '<br>Publication ' . $publicationMedia->getId();
                echo '<br>Publication ' . $publicationMedia->getId();
                $this->createFile($o, $publicationMedia->getType());
            }
        }

        return $this->render('NaturaPassMediaBundle:Default:index.html.twig', array(
                'message' => $this->message
        ));
    }

    public function userAction() {
        $repository = $this->getDoctrine()
            ->getRepository('NaturaPassUserBundle:UserMedia');
        $userMedias = $repository->findAll();

        foreach ($userMedias as $userMedia) {

            $o = $userMedia->getAbsolutePath();
            $t = str_replace('original', 'thumb', $o);
            $this->delFile($t);

            if (file_exists($o)) {
                $this->message .= '<br>User ' . $userMedia->getId();
                echo '<br>User ' . $userMedia->getId();
                $this->createFile($o, $userMedia->getType());
            }
        }

        return $this->render('NaturaPassMediaBundle:Default:index.html.twig', array(
                'message' => $this->message
        ));
    }

    public function LoungeAction() {
        $repository = $this->getDoctrine()
            ->getRepository('NaturaPassLoungeBundle:LoungeMedia');
        $loungeMedias = $repository->findAll();

        foreach ($loungeMedias as $loungeMedia) {

            $o = $loungeMedia->getAbsolutePath();
            $t = str_replace('original', 'thumb', $o);
            $this->delFile($t);

            if (file_exists($o)) {
                $this->message .= '<br>User ' . $loungeMedia->getId();
                echo '<br>User ' . $loungeMedia->getId();
                $this->createFile($o, $loungeMedia->getType());
            }
        }

        return $this->render('NaturaPassMediaBundle:Default:index.html.twig', array(
                'message' => $this->message
        ));
    }

    public function delFile($file) {
        if (file_exists($file)) {
            unlink($file);
        }
    }

    public function createFile($file, $type) {
        $pathNoExtension = BaseMedia::removeFilePathExtension($file);

        if ($type == self::TYPE_IMAGE) {
            $image = new \Imagick($file);
        } else if ($type == self::TYPE_VIDEO) {
            $ffmpeg = FFMpeg\FFMpeg::create(array(
                    'ffmpeg.binaries' => '/usr/local/bin/ffmpeg',
                    'ffprobe.binaries' => '/usr/local/bin/ffprobe',
            ));

            $video = $ffmpeg->open($file);

            $tmpImgFile = str_replace('original', 'tmp', $pathNoExtension) . ".jpeg";

            $frame = $video->frame(FFMpeg\Coordinate\TimeCode::fromSeconds(1));
            $frame->save($tmpImgFile);

            $image = new \Imagick($tmpImgFile);
        }

        $image->setimagecompression(\Imagick::COMPRESSION_JPEG);
        $image->setcompressionquality(60);

        if ($type == self::TYPE_IMAGE) {
            $d = $image->getImageGeometry();
            if ($d['width'] > 578 || $d['height'] > 578) {
                $image->adaptiveResizeImage(578, 578, 1);
            }
            $image->writeimage(str_replace('original', 'resize', $pathNoExtension) . ".jpeg");

            $image->cropThumbnailImage(140, 140);
            $image->writeimage(str_replace('original', 'thumb', $pathNoExtension) . ".jpeg");
        } else if ($type == self::TYPE_VIDEO) {
            $image->resizeimage(578, 0, \Imagick::FILTER_CATROM, 1);
            $image->writeimage(str_replace('original', 'resize', $pathNoExtension) . ".jpeg");

            $image->cropThumbnailImage(140, 140);
            $image->writeimage(str_replace('original', 'thumb', $pathNoExtension) . ".jpeg");

            unlink($tmpImgFile);
        }

        $this->message .= '<br>traité';
        echo '<br>traité';
    }

}
