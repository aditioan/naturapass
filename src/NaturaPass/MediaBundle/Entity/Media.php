<?php

namespace NaturaPass\MediaBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use NaturaPass\MainBundle\Entity\Geolocation;
use Symfony\Component\Validator\Constraints as Assert;
use JMS\Serializer\Annotation as JMS;
use Gedmo\Mapping\Annotation as ORMExtension;

/**
 * Media
 *
 * @ORM\MappedSuperclass()
 * @ORM\HasLifecycleCallbacks
 * @JMS\ExclusionPolicy("all")
 */
abstract class Media extends BaseMedia
{

    /**
     * @var string
     *
     * @ORM\Column(name="legend", type="string", length=255, nullable=true)
     *
     * @JMS\Expose
     * @JMS\Groups({"MediaDetail", "MediaLess"})
     */
    protected $legend;

    /**
     * @ORM\OneToOne(targetEntity="NaturaPass\MainBundle\Entity\Geolocation", cascade={"persist", "remove"})
     * @ORM\JoinColumn(nullable=true)
     *
     * @JMS\Expose
     * @JMS\Groups({"MediaDetail"})
     */
    protected $geolocation;

    /**
     * @var \Doctrine\Common\Collections\ArrayCollection
     *
     * @ORM\ManyToMany(targetEntity="NaturaPass\MediaBundle\Entity\Tag", cascade={"persist"})
     *
     * @JMS\Expose
     * @JMS\Groups({"MediaDetail"})
     */
    protected $tags;

    /**
     * @ORM\OneToOne(targetEntity="NaturaPass\MainBundle\Entity\Sharing", cascade={"persist", "remove"})
     * @ORM\JoinColumn(nullable=true)
     */
    protected $sharing;

    public function __construct()
    {
        $this->tags = new ArrayCollection();
    }

    /**
     * @ORM\PrePersist()
     * @ORM\PreUpdate()
     */
    public function preUpload()
    {
        parent::preUpload();
        if (null !== $this->file) {
            $this->readGPSinfoEXIF();
        }
    }

    protected function decodeEXIF(&$exif)
    {
        foreach ($exif as $key => $value) {
            if (is_array($value)) {
                $this->decodeEXIF($exif[$key]);
            } else {
                $exif[$key] = utf8_encode($value);
            }
        }
    }

    /**
     *
     * Lis les données EXIF d'une image et affecte au média une géolocalisation
     */
    public function readGPSinfoEXIF()
    {
        if (in_array($this->file->guessExtension(), array('jpeg', 'jpg')) && !$this->geolocation instanceof \NaturaPass\MainBundle\Entity\Geolocation) {
            $exif = @exif_read_data($this->file->getRealPath(), 0, true);
            $this->decodeEXIF($exif);

            $this->setExif($exif);

            if (is_array($exif) && isset($exif['GPS']['GPSLatitude']) && isset($exif['GPS']['GPSLongitude'])) {
                $lat = $exif['GPS']['GPSLatitude'];
                list($num, $dec) = explode('/', $lat[0]);
                $lat_s = ($dec != 0) ? ($num / $dec) : 0;
                list($num, $dec) = explode('/', $lat[1]);
                $lat_m = ($dec != 0) ? ($num / $dec) : 0;
                list($num, $dec) = explode('/', $lat[2]);
                $lat_v = ($dec != 0) ? ($num / $dec) : 0;

                $lon = $exif['GPS']['GPSLongitude'];
                list($num, $dec) = explode('/', $lon[0]);
                $lon_s = ($dec != 0) ? ($num / $dec) : 0;
                list($num, $dec) = explode('/', $lon[1]);
                $lon_m = ($dec != 0) ? ($num / $dec) : 0;
                list($num, $dec) = explode('/', $lon[2]);
                $lon_v = ($dec != 0) ? ($num / $dec) : 0;

                $geolocation = new Geolocation();

                $geolocation->setLatitude($lat_s + $lat_m / 60.0 + $lat_v / 3600.0)
                    ->setLongitude($lon_s + $lon_m / 60.0 + $lon_v / 3600.0);

                $this->geolocation = $geolocation;
            }
        }
    }

    public function setFile($file)
    {
        $this->file = $file;

        $this->readGPSinfoEXIF();

        return $this;
    }

    /**
     * Set geolocation
     *
     * @param Geolocation $geolocation
     *
     * @return Media
     */
    public function setGeolocation(Geolocation $geolocation)
    {
        $this->geolocation = $geolocation;

        return $this;
    }

    /**
     * Get geolocation
     *
     * @return \NaturaPass\MainBundle\Entity\Geolocation
     */
    public function getGeolocation()
    {
        return $this->geolocation;
    }

    /**
     * Set legend
     *
     * @param string $legend
     * @return Media
     */
    public function setLegend($legend)
    {
        $this->legend = $legend;

        return $this;
    }

    /**
     * Get legend
     *
     * @return string
     */
    public function getLegend()
    {
        return $this->legend;
    }

    /**
     * Add tag
     *
     * @param \NaturaPass\MediaBundle\Entity\Tag $tag
     * @return Media
     */
    public function addTag(Tag $tag)
    {
        $this->tags[] = $tag;

        return $this;
    }

    /**
     * Remove tag
     *
     * @param \NaturaPass\MediaBundle\Entity\Tag $tag
     */
    public function removeTag(\NaturaPass\MediaBundle\Entity\Tag $tag)
    {
        $this->tags->removeElement($tag);
    }

    /**
     * Set tags
     *
     * @param \Doctrine\Common\Collections\ArrayCollection
     * @return \Doctrine\Common\Collections\ArrayCollection
     */
    public function setTags($tags)
    {
        $this->tags = $tags;

        return $this;
    }

    /**
     * Get tags
     *
     * @return \Doctrine\Common\Collections\ArrayCollection
     */
    public function getTags()
    {
        return $this->tags;
    }

    public function getSharing()
    {
        return $this->sharing;
    }

    public function setSharing($sharing)
    {
        $this->sharing = $sharing;
        return $this;
    }

}
