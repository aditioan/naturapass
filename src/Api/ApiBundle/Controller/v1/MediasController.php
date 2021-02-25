<?php

namespace Api\ApiBundle\Controller\v1;

use Doctrine\ORM\Query\AST\Join;
use NaturaPass\MainBundle\Entity\Geolocation;
use NaturaPass\PublicationBundle\Entity\PublicationMedia;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\HttpFoundation\Request;
use FOS\RestBundle\Controller\Annotations\View;
use FOS\RestBundle\Util\Codes;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Doctrine\ORM\Query\Expr;

/**
 * Description of MediasController
 *
 * @author vincentvalot
 */
class MediasController extends ApiRestController
{

    /**
     * Recherche les tags correspondants à un nom spécifié
     *
     * GET /medias/tags?name=chasse&limit=10&page=0
     *
     * @param Request $request
     * @return \FOS\RestBundle\View\View
     */
    public function getMediasTagsAction(Request $request)
    {
        $name = $request->query->get('name', '');
        $limit = $request->query->get('limit', 10);
        $page = $request->query->get('page', 0);
        $qb = $this->getDoctrine()->getManager()->createQueryBuilder();
        $pending = $qb->select('t')
            ->from('NaturaPassMediaBundle:Tag', 't')
            ->where('t.name LIKE :name')
            ->setParameter('name', $name . '%')
            ->orderBy('t.name', 'ASC')
            ->setMaxResults($limit)
            ->setFirstResult(($page - 1) * $limit)
            ->getQuery()
            ->getResult();
        $tags = array();
        foreach ($pending as $tag) {
            $tags[] = array(
                'id' => $tag->getName(),
                'text' => $tag->getName()
            );
        }
        return $this->view(array('tags' => $tags), Codes::HTTP_OK);
    }

    /**
     * Ajoute un média
     *
     * POST /medias
     *
     * @param Request $request
     * @return \FOS\RestBundle\View\View
     *
     * @View()
     */
    public function postUploadPublicationMediaAction(Request $request)
    {
        $this->authorize();
        if (is_object($request->files->get('publication[media][file]', NULL, true))) {
            $file = $request->files->get('publication[media][file]', NULL, true);
        }
        if (is_object($request->files->get('publication_edit[media][file]', NULL, true))) {
            $file = $request->files->get('publication_edit[media][file]', NULL, true);
        }
        if (isset($file) && !is_null($file) && $file) {
            $path = __DIR__ . '/../../../../../web/uploads/publications/tmp/' . $file->getFilename();
            if (!file_exists($path)) {
                $file->move(__DIR__ . '/../../../../../web/uploads/publications/tmp/', $file->getFilename());
            }
            $this->get('session')->set('upload_handler/publication.upload', $path);
            $media = new PublicationMedia();
            $media->setFile(new File($path));
            if (in_array($media->getFile()->guessExtension(), array('jpeg', 'jpg'))) {
                $exif = @exif_read_data($path, 0, true);
                $this->decodeEXIF($exif);
                if (is_array($exif) && isset($exif['IFD0'])) {
                    if (isset($exif['IFD0']['DateTime'])) {
                        $date = new \DateTime($exif['IFD0']['DateTime']);
                        $date->format('Y-m-d\TH:i:sP');
                    } else {
                        $date = false;
                    }
                    return $this->view(array(
                        'geolocation' => $media->getGeolocation(),
                        'date' => $date
                    ), Codes::HTTP_OK);
                } else {
                    return $this->view($this->success(), Codes::HTTP_OK);
                }
            }
            return $this->view($this->success(), Codes::HTTP_OK);
        }
        throw new HttpException(Codes::HTTP_BAD_REQUEST, $this->message('errors.media.upload'));
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
     * Retourne tous les medias localisé dans une zone précise
     *
     * GET /medias/map?swLat=46.030580621651566&swLng=4.899201278686519&neLat=46.343846624129334&neLng=5.805573348999019&sharing=4&reset=1
     *
     * Coordonnées des points Nord-Est et Sud-Ouest aux extrémités de la map
     * Reset permet de réinitialiser les zones chargées
     *
     * @param Request $request
     * @return \FOS\RestBundle\View\View
     *
     * @View(serializerGroups={"PublicationOwner", "PublicationMedia", "MediaDetail", "UserLess", "GeolocationDetail", "TagLess"})
     */
    public function getMediasMapAction(Request $request)
    {
        $this->authorize();
        $swLat = $request->query->get('swLat', false);
        $swLng = $request->query->get('swLng', false);
        $neLat = $request->query->get('neLat', false);
        $neLng = $request->query->get('neLng', false);
        $sharing = $request->query->get('sharing', false);
        if (!$swLat && !$swLng && !$neLat && !$neLng && !$sharing) {
            throw new HttpException(Codes::HTTP_BAD_REQUEST);
        }
        if ($request->query->has('reset')) {
            $this->get('session')->remove('naturapass_map/positions_loaded');
        }
        $qb = $this->getSharingQueryBuilder('NaturaPassPublicationBundle:Publication', 'p', $sharing);
        $qb->join('NaturaPassPublicationBundle:PublicationMedia', 'pm', Expr\Join::WITH, 'pm = p.media')
            ->join('NaturaPassMainBundle:Geolocation', 'g', Expr\Join::WITH, 'g = pm.geolocation')
            ->andWhere($qb->expr()->andx(
                $qb->expr()->between('g.latitude', $swLat, $neLat), $qb->expr()->between('g.longitude', $swLng, $neLng)
            ));
        $alreadyLoaded = $this->get('session')->get('naturapass_map/positions_loaded');
        if (is_array($alreadyLoaded)) {
            foreach ($alreadyLoaded as $rectangle) {
                list($sw, $ne) = $rectangle;
                $qb->andWhere($qb->expr()->andx(
                    $qb->expr()->not(
                        $qb->expr()->andx(
                            $qb->expr()->between('g.latitude', $sw->getLatitude(), $ne->getLatitude()), $qb->expr()->between('g.longitude', $sw->getLongitude(), $ne->getLongitude())
                        )
                    )
                ));
            }
        }
        $northEast = new Geolocation();
        $northEast->setLatitude($neLat)
            ->setLongitude($neLng);
        $southWest = new Geolocation();
        $southWest->setLatitude($swLat)
            ->setLongitude($swLng);
        $medias = $qb->setMaxResults(500)
            ->getQuery()
            ->getResult();
        $alreadyLoaded[] = array($southWest, $northEast);
        $this->get('session')->set('naturapass_map/positions_loaded', $alreadyLoaded);
        return $this->view(array('medias' => $medias), Codes::HTTP_OK);
    }

}
