<?php

namespace Admin\NewsBundle\Entity;

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
abstract class BaseMediaNews {

    const TYPE_IMAGE = 100;
    const TYPE_VIDEO = 101;
    const STATE_UNACTIVE = 0;
    const STATE_ACTIVE = 1;

    /**
     * @var integer
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     *
     * @JMS\Expose
     * @JMS\Groups({"MediaDetail", "MediaLess", "MediaLessThanLess"})
     */
    protected $id;

    /**
     * @var string
     *
     * @ORM\Column(name="name", type="string", length=255)
     *
     * @JMS\Expose
     * @JMS\Groups({"MediaDetail", "MediaLess"})
     */
    protected $name;

    /**
     * @var integer
     *
     * @ORM\Column(name="type", type="integer")
     *
     * @JMS\Expose
     * @JMS\Groups({"MediaDetail", "MediaLess"})
     */
    protected $type;

    /**
     * @var integer
     *
     * @ORM\Column(name="state", type="integer")
     *
     * @JMS\Expose
     * @JMS\Groups({"MediaDetail", "MediaLess"})
     */
    protected $state = self::STATE_ACTIVE;

    /**
     * @var string
     *
     * @ORM\Column(name="path", type="string", length=255)
     * @JMS\Accessor(getter="getPath")
     *
     * @JMS\Expose
     * @JMS\Groups({"MediaLess", "MediaLessThanLess", "MediaDetail"})
     */
    protected $path;

    /**
     * @var File $file
     *
     * @JMS\Expose
     * @JMS\Groups({"MediaLess", "MediaDetail"})
     * @Assert\File(maxSize="6000000")
     */
    protected $file;

    /**
     * @ORM\Column(name="exif", type="text", nullable=true)
     */
    protected $exif;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="created", type="datetime")
     * @ORMExtension\Timestampable(on="create")
     *
     * @JMS\Expose
     * @JMS\Groups({"MediaDetail", "MediaLess"})
     */
    protected $created;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="updated", type="datetime")
     * @ORMExtension\Timestampable(on="update")
     *
     * @JMS\Expose
     * @JMS\Groups({"MediaDetail", "MediaLess"})
     */
    protected $updated;


    public static function _unlink($path) {
        if (is_file($path)) {
            unlink($path);
        }

        if (is_file(str_replace('original', 'resize', $path))) {
            unlink(str_replace('original', 'resize', $path));
        }

        if (is_file(str_replace('original', 'thumb', $path))) {
            unlink(str_replace('original', 'thumb', $path));
        }
    }

    /**
     * @ORM\PrePersist()
     * @ORM\PreUpdate()
     */
    public function preUpload() {
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
                }
            }

            $this->path = '/' . $this->getUploadDir() . $this->name;
        }
    }

    /**
     * @ORM\PostPersist()
     * @ORM\PostUpdate()
     */
    public function upload() {
        if ($this->file === null) {
            return;
        }

        $tmpPath = $this->file->getPathname();
        if (!@copy($tmpPath, $this->getRootUploadDir() . $this->name)) {
            throw new FileException(sprintf(
                'Could not move the file "%s" to "%s"', $tmpPath, $this->getRootUploadDir() . $this->name
            ));
        }

        // on recupere le chemin absolu sans l'extension
        $pathNoExtension = self::removeFilePathExtension($this->getRootUploadDir() . $this->name);

        if ($this->type === self::TYPE_IMAGE) {
            $image = new \Imagick($this->getRootUploadDir() . $this->name);

            $image->setimagecompression(\Imagick::COMPRESSION_JPEG);
            $image->setcompressionquality(60);

            $d = $image->getImageGeometry();
            $w = $d['width'];
            $h = $d['height'];

                $image->adaptiveResizeImage(954, 352, 1);
            $image->writeimage(str_replace('original', 'resize', $pathNoExtension) . ".jpeg");

            $image->writeimage(str_replace('original', 'thumb', $pathNoExtension) . ".jpeg");
        } else {
            if ($this->type === self::TYPE_VIDEO) {

                // TODO: adapter les options suivant les capactites du serveur
                $ffmpeg = FFMpeg\FFMpeg::create(
                    array(
                        'ffmpeg.binaries' => is_file('/usr/local/bin/ffmpeg') && is_executable('/usr/local/bin/ffmpeg') ? '/usr/local/bin/ffmpeg' : 'ffmpeg',
                        'ffprobe.binaries' => is_file('/usr/local/bin/ffprobe') && is_executable('/usr/local/bin/ffprobe') ? '/usr/local/bin/ffprobe' : 'ffprobe'
                        //     'timeout'          => 3600,
                        //     'ffmpeg.threads'   => 12,
                    )
                );

                // initisalisation FFMPEG avec la video originale
                $video = $ffmpeg->open($this->getRootUploadDir() . $this->name);

                $tmpImgFile = str_replace('original', 'tmp', $pathNoExtension) . ".jpeg";

                $duration = $video->getFormat()->get('duration');
                $frame = $video->frame(FFMpeg\Coordinate\TimeCode::fromSeconds($duration / 10));
                $frame->save($tmpImgFile);
                $image = new \Imagick($tmpImgFile);

                $first = $video->getStreams()
                    ->videos()                      // filters video streams
                    ->first();

                if ($first->has('tags')) {
                    $tags = $first->get('tags');

                    if (isset($tags['rotate'])) {
                        $image->rotateimage(new \ImagickPixel(), $tags['rotate']);

                        switch ($tags['rotate']) {
                            case 90:
                                $video->addFilter(new FFMpeg\Filters\Video\RotateFilter(FFMpeg\Filters\Video\RotateFilter::ROTATE_90));
                                $video->addFilter(new FFMpeg\Filters\Video\ResizeFilter(new FFMpeg\Coordinate\Dimension(1, 578), FFMpeg\Filters\Video\ResizeFilter::RESIZEMODE_SCALE_WIDTH));
                                break;
                            case 180:
                                $video->addFilter(new FFMpeg\Filters\Video\RotateFilter(FFMpeg\Filters\Video\RotateFilter::ROTATE_180));
                                break;
                            case 270:
                                $video->addFilter(new FFMpeg\Filters\Video\RotateFilter(FFMpeg\Filters\Video\RotateFilter::ROTATE_270));
                                $video->addFilter(new FFMpeg\Filters\Video\ResizeFilter(new FFMpeg\Coordinate\Dimension(1, 578), FFMpeg\Filters\Video\ResizeFilter::RESIZEMODE_SCALE_WIDTH));
                                break;
                            default:
                                $video->addFilter(new FFMpeg\Filters\Video\ResizeFilter(new FFMpeg\Coordinate\Dimension(578, 1), FFMpeg\Filters\Video\ResizeFilter::RESIZEMODE_SCALE_HEIGHT));
                                break;
                        }
                    }
                }

                $image->setimagecompression(\Imagick::COMPRESSION_JPEG);
                $image->setcompressionquality(75);

                // Poster (578 pixels de large)
                $image->resizeimage(578, 0, \Imagick::FILTER_CATROM, 1);
                $image->writeimage(str_replace('original', 'resize', $pathNoExtension) . ".jpeg");

                // 140
                $image->cropThumbnailImage(140, 140);
                $image->writeimage(str_replace('original', 'thumb', $pathNoExtension) . ".jpeg");

                // on efface l'image du dossier temporaire
                unlink($tmpImgFile);

                // enrgistrment au format MP4, WEBM et FLV
                $video->save(new FFMpeg\Format\Video\X264(), str_replace('original', 'mp4', $pathNoExtension) . ".mp4");
                $video->save(new FFMpeg\Format\Video\WebM(), str_replace('original', 'webm', $pathNoExtension) . ".webm");
                $video->save(new FFMpeg\Format\Video\Ogg(), str_replace('original', 'ogv', $pathNoExtension) . ".ogv");
            }
        }


        if (is_file($tmpPath) && is_writable($tmpPath) && !preg_match('#web/tests/#', $tmpPath) && !preg_match('#web/img/avatars#', $tmpPath)) {
            @unlink($tmpPath);
        }

        unset($this->file);
    }

    // Miniatures
    public function getResize() {
        return str_replace('original', 'resize', self::removeFilePathExtension($this->getWebPath()) . ".jpeg");
    }

    public function getThumb() {
        return str_replace('original', 'thumb', self::removeFilePathExtension($this->getWebPath()) . ".jpeg");
    }

    // Videos
    public function getMp4() {
        if ($this->type == self::TYPE_VIDEO) {
            return str_replace('original', 'mp4', self::removeFilePathExtension($this->getWebPath()) . ".mp4");
        }

        return false;
    }

    public function getWebm() {
        if ($this->type == self::TYPE_VIDEO) {
            return str_replace('original', 'webm', self::removeFilePathExtension($this->getWebPath()) . ".webm");
        }

        return false;
    }

    public function getOgv() {
        if ($this->type == self::TYPE_VIDEO) {
            return str_replace('original', 'ogv', self::removeFilePathExtension($this->getWebPath()) . ".ogv");
        }

        return false;
    }

    public function getFlv() {
        if ($this->type == self::TYPE_VIDEO) {
            return str_replace('original', 'flv', self::removeFilePathExtension($this->getWebPath()) . ".flv");
        }

        return false;
    }

    // Uutils
    public function getAbsolutePath() {
        return $this->getRootUploadDir() . $this->path;
    }

    public function getWebPath() {
        return $this->getUploadDir() . $this->path;
    }

    public function getUploadDir() {
        switch ($this->type) {
            case self::TYPE_IMAGE:
                return 'uploads/images/original/';
            case self::TYPE_VIDEO:
                return 'uploads/videos/original/';
        }

        return false;
    }

    protected function getRootUploadDir() {
        return __DIR__ . '/../../../../web/' . $this->getUploadDir();
    }

    public static function removeFilePathExtension($path) {
        return preg_replace("/\\.[^.\\s]{2,4}$/", "", $path);
//        return preg_replace("/\\.[.]{2,5}$/", "", $path);
    }

    /**
     * @ORM\PreRemove()
     */
    public function removeUpload() {
        if ($file = $this->getRootUploadDir() . $this->name) {
            if (is_file($file))
                unlink($file);
            if (is_file(str_replace('original', 'thumb', $file)))
                unlink(str_replace('original', 'thumb', $file));
            if (is_file(str_replace('original', 'resize', $file)))
                unlink(str_replace('original', 'resize', $file));
        }
    }

    /**
     * Get id
     *
     * @return integer
     */
    public function getId() {
        return $this->id;
    }

    public function getName() {
        return $this->name;
    }

    public function setName($name) {
        $this->name = $name;

        return $this;
    }

    /**
     * Set type
     *
     * @param integer $type
     * @return Media
     */
    public function setType($type) {
        $this->type = $type;

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
     * Set type
     *
     * @param integer $state
     *
     * @return Media
     */
    public function setState($state) {
        $this->state = $state;

        return $this;
    }

    /**
     * Get type
     *
     * @return integer
     */
    public function getType() {
        return $this->type;
    }

    /**
     * Set path
     *
     * @param string $path
     * @return Media
     */
    public function setPath($path) {
        $this->path = $path;

        return $this;
    }

    /**
     * Get path
     *
     * @return string
     */
    public function getPath() {
        return str_replace('original', 'resize', $this->path);
    }

    public function getFile() {
        return $this->file;
    }

    public function setFile($file) {
        $this->file = $file;

        return $this;
    }

    public function getCreated() {
        return $this->created;
    }

    public function getUpdated() {
        return $this->updated;
    }

    public function setCreated(\DateTime $created) {
        $this->created = $created;

        return $this;
    }

    public function setUpdated(\DateTime $updated) {
        $this->updated = $updated;

        return $this;
    }

    public function getExif() {
        return is_array($this->exif) ? $this->exif : unserialize($this->exif);
    }

    public function setExif($exif) {
        $this->exif = is_array($exif) ? serialize($exif) : $exif;
    }

}
