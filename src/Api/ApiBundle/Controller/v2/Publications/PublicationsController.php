<?php

/**
 * Created by PhpStorm.
 * User: vincentvalot
 * Date: 21/07/15
 * Time: 09:58
 */

namespace Api\ApiBundle\Controller\v2\Publications;

use Api\ApiBundle\Controller\v2\ApiRestController;
use Api\ApiBundle\Controller\v2\Serialization\PublicationSerialization;
use Api\ApiBundle\Controller\v2\Serialization\SqliteSerialization;
use Doctrine\ORM\QueryBuilder;
use FOS\RestBundle\Util\Codes;
use NaturaPass\LoungeBundle\Entity\LoungeUser;
use NaturaPass\MainBundle\Entity\Geolocation;
use NaturaPass\GraphBundle\Entity\Edge;
use NaturaPass\MainBundle\Entity\Sharing;
use NaturaPass\NotificationBundle\Entity\Lounge\SocketOnly\LoungePublicationNotification;
use NaturaPass\NotificationBundle\Entity\Publication\PublicationLikedNotification;
use NaturaPass\NotificationBundle\Entity\Publication\PublicationProcessedErrorNotification;
use NaturaPass\NotificationBundle\Entity\Publication\PublicationProcessedNotification;
use NaturaPass\NotificationBundle\Entity\Publication\PublicationShareNotification;
use NaturaPass\ObservationBundle\Entity\Attachment;
use NaturaPass\ObservationBundle\Entity\AttachmentReceiver;
use NaturaPass\ObservationBundle\Entity\ObservationReceiver;
use NaturaPass\PublicationBundle\Entity\Publication;
use NaturaPass\PublicationBundle\Entity\PublicationAction;
use NaturaPass\PublicationBundle\Form\Handler\PublicationHandler;
use NaturaPass\PublicationBundle\Form\Type\PublicationFormType;
use NaturaPass\UserBundle\Entity\User;
use NaturaPass\UserBundle\Entity\UserFriend;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\HttpFoundation\Request;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Api\ApiBundle\Controller\v2\Serialization\ObservationSerialization;
use Api\ApiBundle\Controller\v2\Serialization\AnimalSerialization;
use Doctrine\Common\Collections\ArrayCollection;
use NaturaPass\MainBundle\Component\Security\SecurityUtilities;
use NaturaPass\ObservationBundle\Entity\Observation;
use NaturaPass\ObservationBundle\Form\Type\ObservationType;
use Doctrine\ORM\Tools\Pagination\Paginator;
use NaturaPass\GroupBundle\Entity\GroupUser;
use NaturaPass\NotificationBundle\Entity\Group\GroupPublicationNotification;
use NaturaPass\NotificationBundle\Entity\Lounge\LoungeNewPublicationNotification;

class PublicationsController extends ApiRestController
{

    /**
     * Add a points with KML
     *
     * GET /v2/publications/kml
     */
    public function getPublicationKmlAction(Request $request)
    {
//        $this->authorize();
        $manager = $this->getDoctrine()->getManager();
        $yellowCOlor = $manager->getRepository('NaturaPassPublicationBundle:PublicationColor')->find(5);
//        $group = $manager->getRepository('NaturaPassGroupBundle:Group')->find(372);
        $group = $manager->getRepository('NaturaPassGroupBundle:Group')->find(893);
        $file_content = file_get_contents($_SERVER['DOCUMENT_ROOT'] . '/uploads/publication.kml');
        $xml = new \SimpleXMLElement($file_content);
        $xml->registerXPathNamespace('kml', 'http://www.opengis.net/kml/2.2');
        $manager = $this->getDoctrine()->getManager();
        $allElem = array();
        foreach ($xml->xpath("//kml:Folder") as $folder) {
            $folder->registerXPathNamespace('kml', 'http://www.opengis.net/kml/2.2');
            foreach ($folder->xpath("//kml:Placemark") as $placemark) {
                $point = json_decode(json_encode((array)$placemark), TRUE);
                if (!empty($point["name"]) && !empty($point["Point"]) && !empty($point["Point"]["coordinates"])) {
//                if (!empty($point["ExtendedData"]) && !empty($point["ExtendedData"]["SchemaData"]) && !empty($point["ExtendedData"]["SchemaData"]["SimpleData"]) && !empty($point["Point"]) && !empty($point["Point"]["coordinates"])) {
//                    $idMaille = $point["ExtendedData"]["SchemaData"]["SimpleData"][0];
                    $idMaille = $point["name"];
                    $latlng = $point["Point"]["coordinates"];
                    $explode = explode(",", $latlng);
                    $lng = $explode[0];
                    $lat = $explode[1];

                    $publication = new Publication();
                    $publication->setOwner($this->getUser());
                    $publication->setLegend($idMaille);
                    $sharing = new Sharing();
                    $sharing->setShare(Sharing::USER);
                    $publication->setSharing($sharing);
                    $publication->addGroup($group);
                    $publication->setContent($idMaille);
                    $geolocation = new Geolocation();
                    $geolocation->setLatitude($lat);
                    $geolocation->setLongitude($lng);
                    $publication->setGeolocation($geolocation);
                    $publication->setPublicationcolor($yellowCOlor);
                    $manager->persist($publication);
                    $manager->flush();
                }
            }
        }

        return $this->view($this->success(), Codes::HTTP_OK);

    }

    /**
     * Récupère la publication d'identifiant passé en paramètre
     *
     * GET /v2/publications/{publication}
     *
     * @param \NaturaPass\PublicationBundle\Entity\Publication $publication
     * @return \FOS\RestBundle\View\View
     *
     * @ParamConverter("publication", class="NaturaPassPublicationBundle:Publication")
     */
    public function getPublicationAction(Publication $publication)
    {
        $this->authorize();
        $this->authorizePublication($publication);
        return $this->view(
            array('publication' => PublicationSerialization::serializePublication($publication, $this->getUser())), Codes::HTTP_OK
        );
    }

    /**
     * Récupère les publications avec les options passées en paramètre
     *
     * GET /v2/publications?groups[]=8&groups[]=3&limit=20&sharing=4&offset=20&hunts[]=8&hunts[]=3&filter=charnoz
     *
     * Valeurs possibles pour sharing (voir constantes de classes Publication)
     *      0 => ME
     *      1 => FRIENDS
     *      3 => NATURAPASS
     * limit:   Nombre de valeurs retournés
     * offset:  Position à partir de laquelle il faut récupérer
     *
     * @param Request $request
     * @return \FOS\RestBundle\View\View
     */
    public function getPublicationsAction(Request $request)
    {
        $this->authorize();
        $limit = $request->query->get('limit', 10);
        $offset = $request->query->get('offset', 0);
        $search = $request->query->get('filter', false);
        $filter = array(
            'groups' => array(),
            'hunts' => array(),
            'filter' => $search
        );
        $groups = $request->query->get('groups', array());
        $hunts = $request->query->get('hunts', array());
        $persons = $request->query->get('users', array());
        $categories = $request->query->get('categories', array());
        $start = $request->query->get('startTime', 0);
        $end = $request->query->get('endTime', 0);
        if ($request->query->has('sharing') && $request->query->get('sharing') >= 0) {
            $filter["sharing"] = $request->query->get('sharing');
        }

            // do the work

            $filter["groups"] = $groups;
            $filter["hunts"] = $hunts;
            /*$userGroups = $this->getUser()->getAllGroups();
            foreach ($groups as $group) {
                foreach ($userGroups as $userGroup) {
                    if ($group == $userGroup->getId()) {
                        $filter["groups"][] = $group;
                        continue;
                    }
                }
            }
            $userhunts = $this->getUser()->getAllHunts();
            foreach ($hunts as $hunt) {
                foreach ($userhunts as $userhunt) {
                    if ($hunt == $userhunt->getId()) {
                        $filter["hunts"][] = $hunt;
                        continue;
                    }
                }
            }*/

        $filter["persons"] = $persons;

            //
            /*$arrayCategories = array();
            foreach ($categories as $id_category) {
                $em = $this->getDoctrine()->getManager();
                $category = $em->getRepository('AdminSentinelleBundle:Category');
                $arrayCategories = array_merge($arrayCategories, $category->recursiveTree(array($category->find($id_category))));
            }
            $arrayCategories = array_unique($arrayCategories);
            $filter["categories"] = $arrayCategories;*/
            $filter["categories"] = $categories;
            //
            if ($start != 0) {

                $startTime = new \DateTime();
                $startTime->setTimestamp($start);
                $start = $startTime;
                $start = get_object_vars($start);
                $filter["startTime"] = $start["date"];
            }


            if ($end != 0) {

                $endTime = new \DateTime();
                $endTime->setTimestamp($end);
                $end = $endTime;
                $end = get_object_vars($end);
                $filter["endTime"] = $end["date"];
            }

//            if(count($groups) <= 0){
                if (isset($filter["sharing"]) && ($filter["sharing"] == 5 || $filter["sharing"] == 3)) {
                    $PubsBeShared = $this->getUser()->getPublications();
                    foreach ($PubsBeShared as $pub) {
                        $filter["sharesByFriend"][] = $pub->getId();

                    }
                }
//            }


            if (!$this->getUser()->hasRole('ROLE_SUPER_ADMIN') && (empty($filter) || (!isset($filter["sharing"]) && empty($filter["groups"]) && empty($filter["hunts"]) && empty($filter["persons"]) && empty($filter["categories"]) && empty($filter["startTime"]) && empty($filter["filter"])))) {
                throw new HttpException(Codes::HTTP_BAD_REQUEST, $this->message('errors.parameters'));
            }
            $qb = $this->getSharingQueryBuilder(
                'NaturaPassPublicationBundle:Publication', 'p', $filter, 0
            );
            if (isset($filter['startTime'])) {
                $qb->andWhere("p.created >= '" . $filter['startTime'] . "'");
            }
            if (!empty($filter['endTime'])) {
                $qb->andWhere("p.created <= '" . $filter['endTime'] . "'");
            }
            $qb->orderBy('p.created', 'DESC');
            $qb->setFirstResult($offset)
                ->setMaxResults($limit)
                ->getQuery()
                ->useQueryCache(true)    // here
                ->useResultCache(true);  // and here

            $paginator = new Paginator($qb, $fetchJoinCollection = true);
            $results = array();

            foreach ($paginator as $message) {
                $results[] = $message;
            }

        $pubs = PublicationSerialization::serializePublications($results, $this->getUser());

        // vietlh fix show emoji
        $a = array('publications' => $pubs);
        $count = count($a['publications']);
        for($i=0; $i<$count; $i++){
            $a['publications'][$i]["content1"] = preg_replace("/(\\\uD\w{3}\\\uD\w{3})(?!\\s)/", "$1 ", $a['publications'][$i]["content"]);
            // $a['publications'][$i]["content1"] = transliterator_create('Hex-Any')->transliterate($a['publications'][$i]["content1"]);
            $count2 = count($a['publications'][$i]["comments"]["data"]);
            if($count2 > 0){
                for($j=0;$j<$count2;$j++){
                    $a['publications'][$i]["comments"]["data"][$j]["content1"] = preg_replace("/(\\\uD\w{3}\\\uD\w{3})(?!\\s)/", "$1 ", $a['publications'][$i]["comments"]["data"][$j]["content"]);
                    // $a['publications'][$i]["comments"]["data"][$j]["content1"] = transliterator_create('Hex-Any')->transliterate($a['publications'][$i]["comments"]["data"][$j]["content1"]);
                }
            }
        }
        // end
        return $this->view(
            $a, Codes::HTTP_OK
        );

    }

    /**
     * Get all publications visible to user
     *
     * GET /v2/publication/filteroff?limit=20&offset=0&filter=charnoz
     *
     *
     * @param Request $request
     * @return \FOS\RestBundle\View\View
     */
    public function getPublicationFilteroffAction(Request $request)
    {
        $this->authorize();
        $limit = $request->query->get('limit', 10);
        $offset = $request->query->get('offset', 0);
        $search = $request->query->get('filter', false);
        $filter = array(
            'groups' => array(),
            'hunts' => array(),
            'sharing' => 3,
            'filter' => $search
        );

        $categories = $request->query->get('categories', array());
        $start = $request->query->get('startTime', 0);
        $end = $request->query->get('endTime', 0);

        $userGroups = $this->getUser()->getAllGroups();
        foreach ($userGroups as $group) {
            $filter["groups"][] = $group->getId();
        }

        /*$userHunts = $this->getUser()->getAllHunts();
        foreach ($userHunts as $hunt) {
            $filter["hunts"][] = $hunt->getId();
        }*/

                // do the work
                /*$arrayCategories = array();
                foreach ($categories as $id_category) {
                    $em = $this->getDoctrine()->getManager();
                    $category = $em->getRepository('AdminSentinelleBundle:Category');
                    $arrayCategories = array_merge($arrayCategories, $category->recursiveTree(array($category->find($id_category))));
                }
                $arrayCategories = array_unique($arrayCategories);
                $filter["categories"] = $arrayCategories;*/
                $filter["categories"] = $categories;


                if($start != 0){

                    $startTime = new \DateTime();
                    $startTime->setTimestamp($start);
                    $start = $startTime;
                    $start = get_object_vars($start);
                    $filter["startTime"] = $start["date"];
                }

                if($end != 0){

                    $endTime = new \DateTime();
                    $endTime->setTimestamp($end);
                    $end = $endTime;
                    $end = get_object_vars($end);
                    $filter["endTime"] = $end["date"];
                }

                $PubsBeShared = $this->getUser()->getPublications();
                foreach ($PubsBeShared as $pub) {
                    $filter["sharesByFriend"][] = $pub->getId();

                }
                $qb = $this->getSharingQueryBuilder(
                    'NaturaPassPublicationBundle:Publication', 'p', $filter, 0
                );
                if(isset($filter['startTime'])){
                    $qb->andWhere("p.created >= '".$filter['startTime']."'");
                }
                if(!empty($filter['endTime'])){
                    $qb->andWhere("p.created <= '".$filter['endTime']."'");
                }
                $qb->orderBy('p.created', 'DESC')
                    ->setFirstResult($offset)
                    ->setMaxResults($limit)
                    ->getQuery();
                $paginator = new Paginator($qb, $fetchJoinCollection = true);
                $results = array();

                foreach ($paginator as $message) {
                    $results[] = $message;
                }

        $pubFilterOff = PublicationSerialization::serializePublications($results, $this->getUser());

        return $this->view(
            array('publications' => $pubFilterOff), Codes::HTTP_OK
        );
    }

    /**
     * Récupère les publications avec les options passées en paramètre
     *
     * GET /v2/publication/map?swLat=45.87747213066484&swLng=5.1894583414978115&neLat=45.89479835099027&neLng=5.260826558966073&groups[]=8&groups[]=3&sharing=3&hunts[]=8&hunts[]=3&categories[]=1
     *
     * Valeurs possibles pour sharing (voir constantes de classes Publication)
     *      0 => ME
     *      1 => FRIENDS
     *      3 => NATURAPASS
     *
     * @param Request $request
     * @return \FOS\RestBundle\View\View
     *
     * @throws HttpException
     */
    public function getPublicationMapAction(Request $request)
    {
        $this->authorize();
        $swLat = $request->query->get('swLat', false);
        $swLng = $request->query->get('swLng', false);
        $neLat = $request->query->get('neLat', false);
        $neLng = $request->query->get('neLng', false);
        $sharing = $request->query->get('sharing', -1);
        $group = $request->query->get('group', false);
        $hunt = $request->query->get('hunt', false);
        $filter = array(
            'groups' => array(),
            'hunts' => array(),
            'categories' => array()
        );
        if ($request->query->has('sharing') && $request->query->get('sharing') > -1) {
            $filter["sharing"] = $request->query->get('sharing');
        }
        $groups = $request->query->get('groups', array());
        $hunts = $request->query->get('hunts', array());
        $categories = $request->query->get('categories', array());
        if (!$swLat && !$swLng && !$neLat && !$neLng && !$sharing && !$group) {
            throw new HttpException(Codes::HTTP_BAD_REQUEST);
        }
        if ($request->query->has('reset')) {
            $this->get('session')->remove('naturapass_map/positions_loaded');
        }
        $userGroups = $this->getUser()->getAllGroups();
        foreach ($groups as $group) {
            foreach ($userGroups as $userGroup) {
                if ($group == $userGroup->getId()) {
                    $filter["groups"][] = $group;
                    continue;
                }
            }
        }
        $arrayCategories = array();
        foreach ($categories as $id_category) {
            $em = $this->getDoctrine()->getManager();
            $category = $em->getRepository('AdminSentinelleBundle:Category');
            $arrayCategories = array_merge($arrayCategories, $category->recursiveTree(array($category->find($id_category))));
        }
        $arrayCategories = array_unique($arrayCategories);
        $filter["categories"] = $arrayCategories;
        $hunts = $request->query->get('hunts', array());
        $userhunts = $this->getUser()->getAllHunts();
        foreach ($hunts as $hunt) {
            foreach ($userhunts as $userhunt) {
                if ($hunt == $userhunt->getId()) {
                    $filter["hunts"][] = $hunt;
                    continue;
                }
            }
        }
        if (!$this->getUser()->hasRole('ROLE_SUPER_ADMIN') && (empty($filter) || (!isset($filter["sharing"]) && empty($filter["groups"]) && empty($filter["hunts"])))) {
            throw new HttpException(Codes::HTTP_BAD_REQUEST, $this->message('errors.parameters'));
        }
        $qb = $this->getSharingQueryBuilder(
            'NaturaPassPublicationBundle:Publication', 'p', $filter, true
        );
        $qb->join('p.geolocation', 'g')
            ->andWhere(
                $qb->expr()->andx(
                    $qb->expr()->between('g.latitude', $swLat, $neLat), $qb->expr()->between('g.longitude', $swLng, $neLng)
                )
            );
        $alreadyLoaded = $this->get('session')->get('naturapass_map/positions_loaded');
        if (is_array($alreadyLoaded)) {
            foreach ($alreadyLoaded as $rectangle) {
                list($sw, $ne) = $rectangle;
                $qb->andWhere(
                    $qb->expr()->andx(
                        $qb->expr()->not(
                            $qb->expr()->andx(
                                $qb->expr()->between('g.latitude', $sw->getLatitude(), $ne->getLatitude()), $qb->expr()->between('g.longitude', $sw->getLongitude(), $ne->getLongitude())
                            )
                        )
                    )
                );
            }
        }
        $northEast = new Geolocation();
        $northEast->setLatitude($neLat)
            ->setLongitude($neLng);
        $southWest = new Geolocation();
        $southWest->setLatitude($swLat)
            ->setLongitude($swLng);
        $results = $qb->setMaxResults(1000)
            ->getQuery()
            ->getResult();
        $alreadyLoaded[] = array($southWest, $northEast);
        $this->get('session')->set('naturapass_map/positions_loaded', $alreadyLoaded);
        return $this->view(
            array('publications' => PublicationSerialization::serializePublications($results, $this->getUser())), Codes::HTTP_OK
        );
    }

    /**
     * Récupère les publications avec les options passées en paramètre
     *
     * GET /v2/publication/map/mobile?swLat=45.87747213066484&swLng=5.1894583414978115&neLat=45.89479835099027&neLng=5.260826558966073&groups[]=8&groups[]=3&sharing=3&hunts[]=8&hunts[]=3&categories[]=1
     *
     * Valeurs possibles pour sharing (voir constantes de classes Publication)
     *      0 => ME
     *      1 => FRIENDS
     *      3 => NATURAPASS
     *
     * @param Request $request
     * @return \FOS\RestBundle\View\View
     *
     * @throws HttpException
     */
    public function getPublicationMapMobileAction(Request $request)
    {
        $this->authorize();
        $swLat = $request->query->get('swLat', false);
        $swLng = $request->query->get('swLng', false);
        $neLat = $request->query->get('neLat', false);
        $neLng = $request->query->get('neLng', false);
        $sharing = $request->query->get('sharing', -1);
        $group = $request->query->get('group', false);
        $hunt = $request->query->get('hunt', false);
        if (!$swLat && !$swLng && !$neLat && !$neLng) {
            throw new HttpException(Codes::HTTP_BAD_REQUEST);
        }
        $filter = array(
            'groups' => array(),
            'hunts' => array(),
            'categories' => array()
        );
        if ($request->query->has('sharing') && $request->query->get('sharing') > -1) {
            $filter["sharing"] = $request->query->get('sharing');
        }
        $groups = $request->query->get('groups', array());
        $hunts = $request->query->get('hunts', array());
        $categories = $request->query->get('categories', array());
        if ($request->query->has('reset')) {
            $this->get('session')->remove('naturapass_map/positions_loaded');
        }
        $userGroups = $this->getUser()->getAllGroups();
        foreach ($groups as $group) {
            foreach ($userGroups as $userGroup) {
                if ($group == $userGroup->getId()) {
                    $filter["groups"][] = $group;
                    continue;
                }
            }
        }
        $arrayCategories = array();
        foreach ($categories as $id_category) {
            $em = $this->getDoctrine()->getManager();
            $category = $em->getRepository('AdminSentinelleBundle:Category');
            $arrayCategories = array_merge($arrayCategories, $category->recursiveTree(array($category->find($id_category))));
        }
        $arrayCategories = array_unique($arrayCategories);
        $filter["categories"] = $arrayCategories;
        $hunts = $request->query->get('hunts', array());
        $userhunts = $this->getUser()->getAllHunts();
        foreach ($hunts as $hunt) {
            foreach ($userhunts as $userhunt) {
                if ($hunt == $userhunt->getId()) {
                    $filter["hunts"][] = $hunt;
                    continue;
                }
            }
        }
        if (!$this->getUser()->hasRole('ROLE_SUPER_ADMIN') && (empty($filter) || (!isset($filter["sharing"]) && empty($filter["groups"]) && empty($filter["hunts"])))) {
            throw new HttpException(Codes::HTTP_BAD_REQUEST, $this->message('errors.parameters'));
        }
        $qb = $this->getSharingQueryBuilder(
            'NaturaPassPublicationBundle:Publication', 'p', $filter, true
        );
        $qb->join('p.geolocation', 'g')
            ->andWhere(
                $qb->expr()->andx(
                    $qb->expr()->between('g.latitude', $swLat, $neLat), $qb->expr()->between('g.longitude', $swLng, $neLng)
                )
            );
        $manager = $this->getDoctrine()->getManager();
        $qbDeleted = $manager->createQueryBuilder()->select('pd')
            ->from('NaturaPassPublicationBundle:PublicationDeleted', 'pd');
        $qbDeleted->join('pd.geolocation', 'g')
            ->andWhere(
                $qb->expr()->andx(
                    $qb->expr()->between('g.latitude', $swLat, $neLat), $qb->expr()->between('g.longitude', $swLng, $neLng)
                )
            );
        $alreadyLoaded = $this->get('session')->get('naturapass_map/positions_loaded');
        if (is_array($alreadyLoaded)) {
            foreach ($alreadyLoaded as $rectangle) {
                list($sw, $ne) = $rectangle;
                $qb->andWhere(
                    $qb->expr()->andx(
                        $qb->expr()->not(
                            $qb->expr()->andx(
                                $qb->expr()->between('g.latitude', $sw->getLatitude(), $ne->getLatitude()), $qb->expr()->between('g.longitude', $sw->getLongitude(), $ne->getLongitude())
                            )
                        )
                    )
                );
                $qbDeleted->andWhere(
                    $qbDeleted->expr()->andx(
                        $qbDeleted->expr()->not(
                            $qbDeleted->expr()->andx(
                                $qbDeleted->expr()->between('g.latitude', $sw->getLatitude(), $ne->getLatitude()), $qbDeleted->expr()->between('g.longitude', $sw->getLongitude(), $ne->getLongitude())
                            )
                        )
                    )
                );
            }
        }
        $northEast = new Geolocation();
        $northEast->setLatitude($neLat)
            ->setLongitude($neLng);
        $southWest = new Geolocation();
        $southWest->setLatitude($swLat)
            ->setLongitude($swLng);
        $results = $qb->setMaxResults(1000)
            ->getQuery()
            ->getResult();
        $resultsDelete = $qbDeleted
            ->setMaxResults(1000)
            ->getQuery()
            ->getResult();
        $arrayDelete = array();
        foreach ($resultsDelete as $deleted) {
            $arrayDelete[] = $deleted->getId();
        }
        $alreadyLoaded[] = array($southWest, $northEast);
        $this->get('session')->set('naturapass_map/positions_loaded', $alreadyLoaded);
        return $this->view(
            array('publications' => PublicationSerialization::serializePublications($results, $this->getUser()), 'deleted' => $arrayDelete), Codes::HTTP_OK
        );
    }

    /**
     * Get all map publications
     *
     * GET /v2/publication/map/mobile/filteroff?swLat=45.87747213066484&swLng=5.1894583414978115&neLat=45.89479835099027&neLng=5.260826558966073&categories[]=1
     *
     *
     * @param Request $request
     * @return \FOS\RestBundle\View\View
     *
     * @throws HttpException
     */
    public function getPublicationMapMobileFilteroffAction(Request $request)
    {
        $this->authorize();
        $swLat = $request->query->get('swLat', false);
        $swLng = $request->query->get('swLng', false);
        $neLat = $request->query->get('neLat', false);
        $neLng = $request->query->get('neLng', false);
        if (!$swLat && !$swLng && !$neLat && !$neLng) {
            throw new HttpException(Codes::HTTP_BAD_REQUEST);
        }
        $filter = array(
            'groups' => array(),
            'hunts' => array(),
            'sharing' => 3,
            'categories' => array()
        );
        if ($request->query->has('reset')) {
            $this->get('session')->remove('naturapass_map/positions_loaded');
        }
        $userGroups = $this->getUser()->getAllGroups();
        foreach ($userGroups as $group) {
            $filter["groups"][] = $group->getId();
        }
        $userhunts = $this->getUser()->getAllHunts();
        foreach ($userhunts as $hunt) {
            $filter["hunts"][] = $hunt->getId();
        }
        $categories = $request->query->get('categories', array());
        $arrayCategories = array();
        foreach ($categories as $id_category) {
            $em = $this->getDoctrine()->getManager();
            $category = $em->getRepository('AdminSentinelleBundle:Category');
            $arrayCategories = array_merge($arrayCategories, $category->recursiveTree(array($category->find($id_category))));
        }
        $arrayCategories = array_unique($arrayCategories);
        $filter["categories"] = $arrayCategories;
        $qb = $this->getSharingQueryBuilder(
            'NaturaPassPublicationBundle:Publication', 'p', $filter, true
        );
        $qb->join('p.geolocation', 'g')
            ->andWhere(
                $qb->expr()->andx(
                    $qb->expr()->between('g.latitude', $swLat, $neLat), $qb->expr()->between('g.longitude', $swLng, $neLng)
                )
            );
        $manager = $this->getDoctrine()->getManager();
        $qbDeleted = $manager->createQueryBuilder()->select('pd')
            ->from('NaturaPassPublicationBundle:PublicationDeleted', 'pd');
        $qbDeleted->join('pd.geolocation', 'g')
            ->andWhere(
                $qb->expr()->andx(
                    $qb->expr()->between('g.latitude', $swLat, $neLat), $qb->expr()->between('g.longitude', $swLng, $neLng)
                )
            );
        $alreadyLoaded = $this->get('session')->get('naturapass_map/positions_loaded');
        if (is_array($alreadyLoaded)) {
            foreach ($alreadyLoaded as $rectangle) {
                list($sw, $ne) = $rectangle;
                $qb->andWhere(
                    $qb->expr()->andx(
                        $qb->expr()->not(
                            $qb->expr()->andx(
                                $qb->expr()->between('g.latitude', $sw->getLatitude(), $ne->getLatitude()), $qb->expr()->between('g.longitude', $sw->getLongitude(), $ne->getLongitude())
                            )
                        )
                    )
                );
                $qbDeleted->andWhere(
                    $qbDeleted->expr()->andx(
                        $qbDeleted->expr()->not(
                            $qbDeleted->expr()->andx(
                                $qbDeleted->expr()->between('g.latitude', $sw->getLatitude(), $ne->getLatitude()), $qbDeleted->expr()->between('g.longitude', $sw->getLongitude(), $ne->getLongitude())
                            )
                        )
                    )
                );
            }
        }
        $northEast = new Geolocation();
        $northEast->setLatitude($neLat)
            ->setLongitude($neLng);
        $southWest = new Geolocation();
        $southWest->setLatitude($swLat)
            ->setLongitude($swLng);
        $results = $qb->setMaxResults(1000)
            ->getQuery()
            ->getResult();
        $resultsDelete = $qbDeleted
            ->setMaxResults(1000)
            ->getQuery()
            ->getResult();
        $arrayDelete = array();
        foreach ($resultsDelete as $deleted) {
            $arrayDelete[] = $deleted->getId();
        }
        $alreadyLoaded[] = array($southWest, $northEast);
        $this->get('session')->set('naturapass_map/positions_loaded', $alreadyLoaded);
        return $this->view(
            array('publications' => PublicationSerialization::serializePublications($results, $this->getUser()), 'deleted' => $arrayDelete), Codes::HTTP_OK
        );
    }

    /**
     * Get the user publications
     *
     * GET /publications/{user_id}/user?limit=20&offset=0
     *
     * @param Request $request
     * @param User $user
     *
     * @return \FOS\RestBundle\View\View
     *
     * @ParamConverter("user", class="NaturaPassUserBundle:User")
     */
    public function getPublicationsUserAction(Request $request, User $user)
    {
        $this->authorize();
        $limit = $request->query->get('limit', 10);
        $offset = $request->query->get('offset', 0);
        $sharing = Sharing::NATURAPASS;
        if ($this->getUser() == $user) {
            $sharing = Sharing::USER;
        } else {
            if ($this->getUser()->hasFriendshipWith($user, array(UserFriend:: CONFIRMED))) {
                $sharing = Sharing::FRIENDS;
            }
        }
        $manager = $this->getDoctrine()->getManager();
        /**
         * @var QueryBuilder $qb
         */
        $qb = $manager->createQueryBuilder()->select('p')
            ->from('NaturaPassPublicationBundle:Publication', 'p')
            ->innerJoin('p.sharing', 's')
            ->where('p.owner = :owner')
            ->andWhere('p.landmark = :landmark')
            ->andWhere('p.created <= :datetoday')
            ->orderBy('p.created', 'DESC')
            ->setParameter('landmark', 0)
            ->setParameter('owner', $user)
            ->setParameter('datetoday', date("Y-m-d H:i:s"))
            ->setMaxResults($limit)
            ->setFirstResult($offset);
        if (!$this->isConnected('ROLE_SUPER_ADMIN')) {
            $qb->andWhere($qb->expr()->between('s.share', $sharing, Sharing::NATURAPASS))
                ->andWhere(
                    $qb->expr()->notIn(
                        ':connected', $manager->createQueryBuilder()->select('w.id')
                        ->from('NaturaPassMainBundle:Sharing', 's2')
                        ->innerJoin('s2.withouts', 'w')
                        ->where('s.id = s2.id')
                        ->getDql()
                    )
                )
                ->setParameter('connected', $this->getUser()->getId());
        }
        $results = $qb->getQuery()->getResult();
        return $this->view(
            array('publications' => PublicationSerialization::serializePublications($results, $this->getUser())), Codes::HTTP_OK
        );
    }

    /**
     * Ajoute un like utilisateur sur une publication
     *
     * POST /v2/publications/{publication}/likes
     *
     * @param \NaturaPass\PublicationBundle\Entity\Publication
     * @return \FOS\RestBundle\View\View
     *
     * @ParamConverter("publication", class="NaturaPassPublicationBundle:Publication")
     */
    public function postPublicationLikeAction(Publication $publication)
    {
        $this->authorize();
        $this->authorizePublication($publication);
        $manager = $this->getDoctrine()->getManager();
        $like = $manager->getRepository('NaturaPassPublicationBundle:PublicationAction')->findOneBy(
            array(
                'user' => $this->getUser(),
                'publication' => $publication
            )
        );
        if (!$like) {
            $like = new PublicationAction();
            $like->setPublication($publication)
                ->setUser($this->getUser());
        }
        $like->setState(PublicationAction::STATE_LIKE);
        $manager->persist($like);
        $manager->flush();
        if ($this->getUser() != $publication->getOwner()) {
            $this->getNotificationService()->queue(
                new PublicationLikedNotification($publication), $publication->getOwner()
            );
//            $this->delay(function () use ($publication) {
//                $this->getGraphService()->generateEdge(
//                    $this->getUser(), $publication->getOwner(), Edge::PUBLICATION_LIKED, $publication->getId()
//                );
//            });
        }
        return $this->view(
            array(
                'likes' => $publication->getActions(PublicationAction ::STATE_LIKE)->count(),
            ), Codes::HTTP_OK
        );
    }

    /**
     * return active colors for publication
     *
     * GET /publicationcolors
     *
     * @return \FOS\RestBundle\View\View
     *
     */
    public function getPublicationcolorAction()
    {
        $colors = $this->getDoctrine()->getManager()->getRepository("NaturaPassPublicationBundle:PublicationColor")->findBy(array("active" => 1));
        $aColor = array();
        foreach ($colors as $color) {
            $aColor[] = PublicationSerialization::serializePublicationColor($color);
        }
        return $this->view(
            array(
                'colors' => $aColor
            ), Codes::HTTP_OK
        );
    }

    /**
     * Récupère les likes utilisateurs
     *
     * GET /publication/{publication_id}/likes
     *
     * @param \NaturaPass\PublicationBundle\Entity\Publication $publication
     *
     * @return \FOS\RestBundle\View\View
     *
     * @ParamConverter("publication", class="NaturaPassPublicationBundle:Publication")
     */
    public function getPublicationLikesAction(Publication $publication)
    {
        return $this->view(
            array(
                'likes' => PublicationSerialization::serializePublicationLikes(
                    $publication->getActions(), $this->getUser()
                )
            ), Codes::HTTP_OK
        );
    }

    /**
     * Add a publication
     *
     * POST /v2/publications
     *
     * @param Request $request
     * @return \FOS\RestBundle\View\View
     */
    public function postPublicationAction(Request $request)
    {
        $this->authorize();
        $params = $request->request->get('publication');
        $form = $this->createForm(new PublicationFormType($this->getSecurityTokenStorage(), $this->container), new Publication());
        $handler = new PublicationHandler(
            $form, $request, $this->getDoctrine()->getManager(), $this->getSecurityTokenStorage(), $this->getGeolocationService()
        );
        if (!$request->files->get('publication[media][file]', false, true) && $this->get('session')->has('upload_handler/publication.upload')) {
            $file = new File(
                $this->get('session')->get(
                    'upload_handler/publication.upload'
                )
            );
            $params = array('publication' => array('media' => array('file' => $file)));
            $request->files->replace($params);
            if (strpos($file->getMimeType(), 'video') != -1) {
                $this->get('session')->remove('upload_handler/publication.upload');
                $this->delay(
                    function () use ($handler) {
                        try {
                            if ($publication = $handler->process()) {
                                $this->getNotificationService()->queue(
                                    new PublicationProcessedNotification($publication), $this->getUser()
                                );
                            } else {
                                $this->getNotificationService()->queue(
                                    new PublicationProcessedErrorNotification(), $this->getUser()
                                );
                            }
                        } catch (\Exception $exception) {
                            $this->getNotificationService()->queue(
                                new PublicationProcessedErrorNotification(), $this->getUser()
                            );
                        }
                    }, 10
                );
                return $this->view($this->success(), Codes::HTTP_ACCEPTED);
            }
        }
        if ($publication = $handler->process()) {
            $checkDuplicatePublication = $this->checkDuplicatedPublication($publication);
            if ($checkDuplicatePublication) {
                $manager = $this->getDoctrine()->getManager();
                $manager->remove($publication);
                $manager->flush();
                $publication = $checkDuplicatePublication;
            }
            $this->get('session')->remove('upload_handler/publication.upload');
            $this->delay(
                function () use ($publication) {
                    foreach ($publication->getHunts() as $hunt) {
                        $this->getNotificationService()->queue(
                            new LoungePublicationNotification($hunt, $publication), $hunt->getSubscribers(array(LoungeUser::ACCESS_DEFAULT, LoungeUser::ACCESS_ADMIN), true)->toArray()
                        );
                    }
                }, 10
            );
            return $this->view(
                array(
                    'publication' => PublicationSerialization:: serializePublication($publication, $this->getUser(), true)
                ), Codes::HTTP_CREATED
            );
        }
        return $this->view($form->getErrors(true, false), Codes::HTTP_BAD_REQUEST);
    }

    /**
     * edit a publication
     *
     * POST /v2/publications/{publication}/media
     *
     * @param Request $request
     * @return \FOS\RestBundle\View\View
     */
    public function postPublicationMediaAction(Publication $publication, Request $request)
    {
        $this->authorize($publication->getOwner());
        $params = $request->request->get('publication');
        if (empty($params["created"])) {
            $params["created"] = $publication->getCreated()->format(\DateTime::ATOM);
        }
        $arrayPublication = array(
            'publication' => $params);
        $requestPublication = new Request($_GET, $arrayPublication, array(), $_COOKIE, $_FILES, $_SERVER);
        if (empty($pramas["geolocation"])) {
            $this->getDoctrine()->getManager()->persist($publication->setGeolocation(null));
        }
        $form = $this->createForm(new PublicationFormType($this->getSecurityTokenStorage(), $this->container), $publication);
        $handler = new PublicationHandler(
            $form, $requestPublication, $this->getDoctrine()->getManager(), $this->getSecurityTokenStorage(), $this->getGeolocationService()
        );
        if (!$request->files->get('publication[media][file]', false, true) && $this->get('session')->has(
                'upload_handler/publication.upload')
        ) {
            $file = new File(
                $this->get('session')->get(
                    'upload_handler/publication.upload'
                )
            );
            $params = array('publication' => array('media' => array('file' => $file)));
            $requestPublication->files->replace($params);
            if (strpos($file->getMimeType(), 'video') != -1) {
                $this->get('session')->remove('upload_handler/publication.upload');
                $this->delay(
                    function () use ($handler) {
                        try {
                            if ($publication = $handler->process()) {
                                $this->getNotificationService()->queue(
                                    new PublicationProcessedNotification($publication), $this->getUser()
                                );
                            } else {
                                $this->getNotificationService()->queue(
                                    new PublicationProcessedErrorNotification(), $this->getUser()
                                );
                            }
                        } catch (\Exception $exception) {
                            $this->getNotificationService()->queue(
                                new PublicationProcessedErrorNotification(), $this->getUser()
                            );
                        }
                    }, 10
                );
                return $this->view($this->success(), Codes::HTTP_ACCEPTED);
            }
        }
        if ($publication = $handler->process()) {
            $checkDuplicatePublication = $this->checkDuplicatedPublication($publication);
            if ($checkDuplicatePublication) {
                $manager = $this->getDoctrine()->getManager();
                $manager->remove($publication);
                $manager->flush();
                $publication = $checkDuplicatePublication;
            }
            $this->get('session')->remove('upload_handler/publication.upload');
            $this->delay(
                function () use ($publication) {
                    foreach ($publication->getHunts() as $hunt) {
                        $this->getNotificationService()->queue(
                            new LoungePublicationNotification($hunt, $publication), $hunt->getSubscribers(array(LoungeUser::ACCESS_DEFAULT, LoungeUser::ACCESS_ADMIN), true)->toArray()
                        );
                    }
                }, 10
            );
            return $this->view(
                array(
                    'publication' => PublicationSerialization:: serializePublication($publication, $this->getUser(), true)
                ), Codes::HTTP_CREATED
            );
        }
        return $this->view($form->getErrors(true, false), Codes::HTTP_BAD_REQUEST);
    }

    /**
     * Add a publication
     *
     * POST /v2/publications/observations/sharings
     *
     * {
     *  "publication":{
     *      "geolocation":{
     *          "address":"Charnoz-sur-Ain, France",
     *          "latitude":"45.886135916336",
     *          "longitude":"5.2251424502319"
     *      },
     *      "sharing":{
     *          "share":1,
     *          "withouts":[
     *          ]
     *      },
     *      "created":"2015-10-21T13:55:27+02:00",
     *      "date":"",
     *      "content":"gggg",
     *      "legend":"legend",
     *      "groups":[
     *      ],
     *      "users":[
     *      ],
     *      "landmark":false,
     *      "special":true
     *  },
     *  "observation":{
     *      "specific":0,
     *      "animal":10,
     *      "attachments":[
     *          {
     *              "label":1,
     *              "value":1
     *          },
     *          {
     *              "label":2,
     *              "value":1
     *          },
     *          {
     *              "label":3,
     *              "value":1
     *          },
     *          {
     *              "label":4,
     *              "value":1
     *          },
     *          {
     *              "label":5,
     *              "value":1
     *          }
     *      ],
     *      "receivers":[
     *      ],
     *      "category":124
     *  }
     * }
     *
     * @param Request $request
     * @return \FOS\RestBundle\View\View
     */
    public function postPublicationObservationSharingAction(Request $request)
    {
        $this->authorize();
        $params = $request->request->get('publication');
        
        $arrayPublication = array(
            'publication' => $params);
        $requestPublication = new Request($_GET, $arrayPublication, array(), $_COOKIE, $_FILES, $_SERVER);

        $form = $this->createForm(new PublicationFormType($this->getSecurityTokenStorage(), $this->container), new Publication());
        $handler = new PublicationHandler(
            $form, $requestPublication, $this->getDoctrine()->getManager(), $this->getSecurityTokenStorage(), $this->getGeolocationService()
        );
        if (!$request->files->get('publication[media][file]', false, true) && $this->get('session')->has('upload_handler/publication.upload') && isset($params['media'] ['legend'], $params['media']['tags'])) {

            $file = new File(
                $this->get('session')->get(
                    'upload_handler/publication.upload'
                )
            );
            $params = array('publication' => array('media' => array('file' => $file)));
            $requestPublication->files->replace($params);
            if (strpos($file->getMimeType(), 'video') != -1) {
                $this->get('session')->remove('upload_handler/publication.upload');
                $this->delay(
                    function () use ($handler) {
                        try {
                            if ($publication = $handler->process()) {
                                $this->getNotificationService()->queue(
                                    new PublicationProcessedNotification($publication), $this->getUser()
                                );
                            } else {
                                $this->getNotificationService()->queue(
                                    new PublicationProcessedErrorNotification(), $this->getUser()
                                );
                            }
                        } catch (\Exception $exception) {
                            $this->getNotificationService()->queue(
                                new PublicationProcessedErrorNotification(), $this->getUser()
                            );
                        }
                    }, 10
                );
                return $this->view($this->success(), Codes::HTTP_ACCEPTED);
            }
        }
        if ($publication = $handler->process()) {
          
            $checkDuplicatePublication = $this->checkDuplicatedPublication($publication);
            if ($checkDuplicatePublication) {
                foreach ($checkDuplicatePublication as $p) {
                    $manager = $this->getDoctrine()->getManager();
                    $manager->remove($p);
                    $manager->flush();
                }
                $aReturn = array(
                    'publication' => PublicationSerialization::serializePublication($publication, $this->getUser()),
                );
                if (!is_null($publication->getGeolocation())) {
                    $aReturn["sqlite"] = SqliteSerialization::serializeSqliteInserOrReplace("tb_carte", array(PublicationSerialization::serializePublicationSqliteRefresh2(array(), null, $publication, $this->getUser(), true)));
                }
                return $this->view(
                    $aReturn, Codes::HTTP_CREATED
                );
            } else {
                // send notificaiton
                    $special = false;
                    $group111 = $publication->getGroups();
                    $hunt111 = $publication->getHunts();
                    if(count($group111)>0){
                        $userInGroups = $group111[0]->getSubscribers(array(GroupUser::ACCESS_ADMIN, GroupUser::ACCESS_DEFAULT), true);
                        foreach ($userInGroups as $userReciver) {
                            if($userReciver->getId() != $this->getUser()->getId()){
                                $this->getNotificationService()->queue(
                                    new GroupPublicationNotification($group111[0],$publication), $userReciver
                                );
                            }
                        }
                        $senders[] = $this->getUser()->getFullName();

                        $this->getEmailService()->generate(
                            'group.publication_added',
                            array('%group%' => $group111[0]->getName()),
                            $group111[0]->getEmailableSubscribers()->toArray(),
                            'NaturaPassEmailBundle:Group:publication-added.html.twig',
                            array('group' => $group111[0], 'senders' => $this->getNotificationService()->getLinkedValues(new ArrayCollection($senders)))
                        );
                    }

                    if(count($hunt111)>0){
                        $userInHunts = $hunt111[0]->getSubscribers(array(GroupUser::ACCESS_ADMIN, GroupUser::ACCESS_DEFAULT), true);
                        foreach ($userInHunts as $userReciver) {
                            if($userReciver->getId() != $this->getUser()->getId()){
                                $this->getNotificationService()->queue(
                                    new LoungeNewPublicationNotification($hunt111[0],$publication), $userReciver
                                );
                            }
                        }
                        $senders[] = $this->getUser()->getFullName();

                        $this->getEmailService()->generate(
                            'group.publication_added',
                            array('%group%' => $hunt111[0]->getName()),
                            $hunt111[0]->getEmailableSubscribers()->toArray(),
                            'NaturaPassEmailBundle:Group:publication-added.html.twig',
                            array('group' => $hunt111[0], 'senders' => $this->getNotificationService()->getLinkedValues(new ArrayCollection($senders)))
                        );
                    }
                    /*$senders[] = $this->getUser()->getFullName();

                    $this->getEmailService()->generate(
                        'group.publication_added',
                        array('%group%' => $hunt111[0]->getName()),
                        $hunt111[0]->getEmailableSubscribers()->toArray(),
                        'NaturaPassEmailBundle:Group:publication-added.html.twig',
                        array('group' => $hunt111[0], 'senders' => $this->getNotificationService()->getLinkedValues(new ArrayCollection($senders)))
                    );*/

                    $receiversNotification = $publication->getShareusers();
                    foreach ($receiversNotification as $userReciver) {
                        $this->getNotificationService()->queue(
                            new PublicationShareNotification($publication), $userReciver
                        );
                    }


            // end notification
                try {
                    $this->get('session')->remove('upload_handler/publication.upload');
                    $this->delay(
                        function () use ($publication) {
                            foreach ($publication->getHunts() as $hunt) {
                                $this->getNotificationService()->queue(
                                    new LoungePublicationNotification($hunt, $publication), $hunt->getSubscribers(array(LoungeUser::ACCESS_DEFAULT, LoungeUser::ACCESS_ADMIN), true)->toArray()
                                );
                            }
                        }, 10
                    );
                    if ($request->request->has('observation')) {
                        $params = $request->request->get('observation');
                        if (isset($params["receivers"])) {
                            $receivers = $params["receivers"];
                            unset($params["receivers"]);
                        }else{
                            $receivers = null;
                        }
                        $arrayObservation = array('observation' => $params);
                        $requestObservation = new Request($_GET, $arrayObservation, array(), $_COOKIE, array(), $_SERVER);
                        $observation = new Observation();
                        $formObservation = $this->createForm(new ObservationType($publication), $observation, array('csrf_protection' => false));
                        $formObservation->handleRequest($requestObservation);
                        // if ($formObservation->isValid()) {
                        if (true) {
                            $manager = $this->getDoctrine()->getManager();
                            $attachments = $observation->getAttachments();
                            $observation->setAttachments(array());
                            $manager->persist($observation);
                            $manager->refresh($publication);
                            $manager->flush();
                            foreach ($attachments as $attachment) {
                                if (is_null($attachment->getValue())) {
                                    $attachment->setValue("");
                                }
                                $manager->persist($attachment);
                                $observation->addAttachment($attachment);
                            }
                            $em = $this->getDoctrine()->getManager();
                            if (!is_null($receivers)){

                            foreach ($receivers as $array) {
                                $receiver = $em->getRepository('AdminSentinelleBundle:Receiver')->find($array["receiver"]);
                                if (!is_null($receiver)) {
                                    $observation->addReceiver($receiver);
                                    $manager->persist($observation);
                                    $manager->flush();
                                    $observationReceiver = new ObservationReceiver();
                                    $observationReceiver->duplicateObservation($observation);
                                    $observationReceiver->setReceiver($receiver);
                                    $manager->persist($observationReceiver);
                                    $manager->flush();
                                    foreach ($observation->getAttachments() as $attachment) {
                                        $attachmentreceiver = new AttachmentReceiver();
                                        $attachmentreceiver->duplicateAttachment($attachment);
                                        $attachmentreceiver->setObservationreceiver($observationReceiver);
                                        $manager->persist($attachmentreceiver);
                                        $manager->flush();
                                    }
                                }
                            }
                            }
                            
                            $manager->flush();
                        } else {
                            return $this->view($form->getErrors(true, false), Codes::HTTP_BAD_REQUEST);
                        }
                    }
                    $aReturn = array(
                        'publication' => PublicationSerialization::serializePublication($publication, $this->getUser(), true),
                    );
                    if (!is_null($publication->getGeolocation())) {
                        $aReturn["sqlite"] = SqliteSerialization::serializeSqliteInserOrReplace("tb_carte", array(PublicationSerialization::serializePublicationSqliteRefresh2(array(), null, $publication, $this->getUser(), true)));
                    }
                    return $this->view(
                        $aReturn, Codes::HTTP_CREATED
                    );
                } catch (\Exception $exception) {
                    $aReturn = array(
                        'publication' => PublicationSerialization::serializePublication($publication, $this->getUser(), true),
                        'message' => $this->message('errors.partial_content')
                    );
                    if (!is_null($publication->getGeolocation())) {
                        $aReturn["sqlite"] = SqliteSerialization::serializeSqliteInserOrReplace("tb_carte", array(PublicationSerialization::serializePublicationSqliteRefresh2(array(), null, $publication, $this->getUser(), true)));
                    }
                    return $this->view(
                        $aReturn, Codes::HTTP_PARTIAL_CONTENT
                    );
                }
            }
            // send notification
        }

        return $this->view($form->getErrors(true, false), Codes::HTTP_BAD_REQUEST);
    }



    /**
     * Edit Publication Content
     *
     * PUT /v2/publications/{publication}/content
     *
     * Content-Type: Form-Data
     *      content = "Aujourd'hui j'ai tué 5 lapins"
     *
     * @param \NaturaPass\PublicationBundle\Entity\Publication $publication
     * @param Request $request
     *
     * @ParamConverter("publication", class="NaturaPassPublicationBundle:Publication")
     *
     * @return \FOS\RestBundle\View\View
     */
    public function putPublicationContentAction(Publication $publication, Request $request)
    {
        $manager = $this->getDoctrine()->getManager();
        $this->authorize($publication->getOwner());
        $content = $request->request->get('content');
        $publication->setContent($content);
        $manager->merge($publication);
        $manager->flush();
        $aReturn = array(
            'success' => true,
            'publication' => PublicationSerialization::serializePublication($publication, $this->getUser(), true)
        );
        if (!is_null($publication->getGeolocation())) {
            $aReturn["sqlite"] = SqliteSerialization::serializeSqliteInserOrReplace("tb_carte", array(PublicationSerialization::serializePublicationSqliteRefresh2(array(), null, $publication, $this->getUser(), true)));
        }
        return $this->view($aReturn, Codes::HTTP_CREATED);
    }

    /**
     * Edit Publication Legend
     *
     * PUT /v2/publications/{publication}/legend
     *
     * Content-Type: Form-Data
     *      legend = "legend of publication"
     *
     * @param \NaturaPass\PublicationBundle\Entity\Publication $publication
     * @param Request $request
     *
     * @ParamConverter("publication", class="NaturaPassPublicationBundle:Publication")
     *
     * @return \FOS\RestBundle\View\View
     */
    public function putPublicationLegendAction(Publication $publication, Request $request)
    {
        $manager = $this->getDoctrine()->getManager();
        $this->authorize($publication->getOwner());
        $legend = $request->request->get('legend');
        $publication->setLegend($legend);
        $manager->merge($publication);
        $manager->flush();
        $aReturn = array(
            'success' => true,
            'publication' => PublicationSerialization::serializePublication($publication, $this->getUser(), true)
        );
        if (!is_null($publication->getGeolocation())) {
            $aReturn["sqlite"] = SqliteSerialization::serializeSqliteInserOrReplace("tb_carte", array(PublicationSerialization::serializePublicationSqliteRefresh2(array(), null, $publication, $this->getUser(), true)));
        }
        return $this->view($aReturn, Codes::HTTP_CREATED);
    }

    /**
     * Edit Publication Geolocation
     *
     * PUT /v2/publications/{publication}/geolocation
     *
     * Content-Type: Form-Data
     *      latitude = "105"
     *      longitude = "50"
     *      altitude = "10"
     *      address = "Address of publication"
     *
     * @param \NaturaPass\PublicationBundle\Entity\Publication $publication
     * @param Request $request
     *
     * @ParamConverter("publication", class="NaturaPassPublicationBundle:Publication")
     *
     * @return \FOS\RestBundle\View\View
     */
    public function putPublicationGeolocationAction(Publication $publication, Request $request)
    {
        $this->authorize($publication->getOwner());
        $latitude = $request->request->get('latitude', false);
        $longitude = $request->request->get('longitude', false);
        $altitude = $request->request->get('latitude', false);
        $address = $request->request->get('address', false);
        if ($latitude && $longitude && $address) {
            $geolocation = $publication->getGeolocation();
            if (is_null($geolocation)) {
                $geolocation = new Geolocation();
                $geolocation->setCreated(new \DateTime(date('Y-m-d H:i:s', time())));
            }
            $geolocation->setLatitude($latitude)
                ->setLongitude($longitude)
                ->setAddress($address)
                ->setAltitude($altitude);

            $manager = $this->getDoctrine()->getManager();

            $locality = $this->getGeolocationService()->findACity($geolocation);
            if ($locality instanceof Locality) {
                $publication->setLocality($locality);
                $manager->merge($publication);
            }
            $manager->merge($geolocation);
            $manager->flush();
            $publication->setGeolocation($geolocation);
            $manager->merge($publication);
            $manager->flush();
            $aReturn = array(
                'success' => true,
                'publication' => PublicationSerialization::serializePublication($publication, $this->getUser(), true)
            );
            if (!is_null($publication->getGeolocation())) {
                $aReturn["sqlite"] = SqliteSerialization::serializeSqliteInserOrReplace("tb_carte", array(PublicationSerialization::serializePublicationSqliteRefresh2(array(), null, $publication, $this->getUser(), true)));
            }
            return $this->view($aReturn, Codes::HTTP_CREATED);
        }
    }

    /**
     * Edit Publication Observation
     *
     * PUT /v2/publications/{publication}/observation
     *
     * {
     * "observation":{
     *      "specific":0,
     *      "animal":10,
     *      "attachments":[
     *          {
     *              "label":1,
     *              "value":1
     *          },
     *          {
     *              "label":2,
     *              "value":1
     *          },
     *          {
     *              "label":3,
     *              "value":1
     *          },
     *          {
     *              "label":4,
     *              "value":1
     *          },
     *          {
     *              "label":5,
     *              "value":1
     *          }
     *      ],
     *      "receivers":[
     *      ],
     *      "category":124
     *  }
     * }
     * @param \NaturaPass\PublicationBundle\Entity\Publication $publication
     * @param Request $request
     *
     * @ParamConverter("publication", class="NaturaPassPublicationBundle:Publication")
     *
     * @return \FOS\RestBundle\View\View
     */
    public function putPublicationObservationsAction(Publication $publication, Request $request)
    {
        $this->authorize($publication->getOwner());
        $params = $request->request->get('observation');
        $observations = $publication->getObservations();
        $em = $this->getDoctrine()->getManager();

        if (isset($observations[0])) {
            $observation = $observations[0];
//            foreach ($observation->getReceivers() as $observationreceiver) {
//                foreach ($observationreceiver->getAttachmentreceivers() as $attachmentreceiver) {
//                    $em->remove($attachmentreceiver);
//                }
//            }
//            $em->flush();
        } else {
            $observation = new Observation();
            $observation->setPublication($publication);
        }

        if (isset($params['animal']) && ($params['animal'] > 0)) {
            $observation->setAnimal($em->getRepository("\Admin\AnimalBundle\Entity\Animal")->find($params['animal']));
        } else {
            $observation->setAnimal(NULL);
        }
        $observation->setSpecific($params['specific']);
        $observation->setCategory($em->getRepository("Admin\SentinelleBundle\Entity\Category")->find($params['category']));
        foreach ($observation->getAttachments() as $a) {
            $em->remove($a);
            $em->flush();
        }
        $em->persist($observation);
        $em->flush();
        if (isset($params['attachments'])){
            foreach ($params['attachments'] as $attachment) {
            $oAttachment = new Attachment();
            $oAttachment->setLabel($em->getRepository("Admin\SentinelleBundle\Entity\CardLabel")->find($attachment['label']));
            $oAttachment->setValue($attachment['value']);
            $oAttachment->setObservation($observation);
            $em->persist($oAttachment);
            $em->flush();
            }
        }
        
        $em->refresh($observation);
        $em->flush();
        $em->refresh($publication);
        $em->flush();
        $aReturn = array(
            'success' => true,
            'publication' => PublicationSerialization::serializePublication($publication, $this->getUser(), true)
        );
        if (!is_null($publication->getGeolocation())) {
            $aReturn["sqlite"] = SqliteSerialization::serializeSqliteInserOrReplace("tb_carte", array(PublicationSerialization::serializePublicationSqliteRefresh2(array(), null, $publication, $this->getUser(), true)));
        }
        return $this->view($aReturn, Codes::HTTP_CREATED);

    }

    /**
     * Edit Publication Sharing
     *
     * PUT /v2/publications/{publication}/sharing
     *
     * {"sharing":{
     *          "share":1,
     *          "withouts":[ 1, 2 ],
     *          "groups": [ 1, 2 ],
     *          "hunts": [ 3, 4 ]
     *      },
     * }
     * @param \NaturaPass\PublicationBundle\Entity\Publication $publication
     * @param Request $request
     *
     * @ParamConverter("publication", class="NaturaPassPublicationBundle:Publication")
     *
     * @return \FOS\RestBundle\View\View
     */
    public function putPublicationSharingAction(Publication $publication, Request $request)
    {
        $this->authorize($publication->getOwner());
        $em = $this->getDoctrine()->getManager();
        $params = $request->request->get('sharing');
        $share = $publication->getSharing();
        $sharing = new Sharing();
        if ($share) {
            $sharing = $share;
        }
        $without = array();
        foreach ($params['withouts'] as $id) {
            $user = $em->getRepository("NaturaPass\UserBundle\Entity\User")->find($id);
            if ($user) {
                $without[] = $user;
            }
        }
        $share->setShare($params['share']);
        $share->setWithouts($without);
        $em->persist($share);
        $em->flush();


        foreach ($publication->getGroups() as $group) {
            $publication->removeGroup($group);
            $em->persist($publication);
            $em->flush();
        }
        

        foreach ($publication->getHunts() as $hunt) {
            $publication->removeHunt($hunt);
            $em->persist($publication);
            $em->flush();
        }
        foreach ($params['groups'] as $id) {
            $group = $em->getRepository("NaturaPass\GroupBundle\Entity\Group")->find($id);
            if (!is_null($group) && $group && $group->checkAllowAdd($this->getUser()) && $group->isSubscriber($this->getUser())) {
                $publication->addGroup($group);
            }
        }

        /*foreach ($publication->getShareusers() as $user) {
            $publication->removeShareuser($user);
            $em->persist($publication);
            $em->flush();
        }*/

        $getSharedUser = $publication->getShareusers();// get all user were sharedm in this publication
        $arrSharedUser = array();
        foreach ($getSharedUser as $shared) {
            if(!in_array($shared->getId(), $params['users'])){ // if user was shared not in new array user => remove it
                $publication->removeShareuser($shared);
                $em->persist($publication);
                $em->flush();
            }else{
                $arrSharedUser[] = $shared->getId(); // if not => put it to array were shared
            }
        }

        $arrPushNew = array();
        foreach ($params['users'] as $id) {
            if(!in_array($id, $arrSharedUser)){ // if new user not in array were shared => share to him
                $user = $em->getRepository("NaturaPass\UserBundle\Entity\User")->find($id);
                if (!is_null($user)) {
                    $publication->addShareuser($user);
                    $arrPushNew[] = $user;                    
                }
            }            
        }

        foreach ($params['hunts'] as $id) {
            $hunt = $em->getRepository("NaturaPass\LoungeBundle\Entity\Lounge")->find($id);
            if (!is_null($hunt) && $hunt && $hunt->checkAllowAdd($this->getUser()) && $hunt->isSubscriber($this->getUser())) {
                $publication->addHunt($hunt);
            }
        }

        $em->persist($publication);
        $em->flush();
        $em->refresh($publication);
        $em->flush();

        foreach ($arrPushNew as $userReciver) {
            $this->getNotificationService()->queue(
                new PublicationShareNotification($publication), $userReciver
            );
        }

        $aReturn = array(
            'success' => true,
            'publication' => PublicationSerialization::serializePublication($publication, $this->getUser(), true)
        );
        if (!is_null($publication->getGeolocation())) {
            $aReturn["sqlite"] = SqliteSerialization::serializeSqliteInserOrReplace("tb_carte", array(PublicationSerialization::serializePublicationSqliteRefresh2(array(), null, $publication, $this->getUser(), true)));
        }
        return $this->view($aReturn, Codes::HTTP_CREATED);
    }


    /**
     * Edit Publication color label
     *
     * PUT /v2/publications/{publication}/color
     *
     * Content-Type: Form-Data
     *      publicationcolor = "3"
     *
     * @param \NaturaPass\PublicationBundle\Entity\Publication $publication
     * @param Request $request
     *
     * @ParamConverter("publication", class="NaturaPassPublicationBundle:Publication")
     *
     * @return \FOS\RestBundle\View\View
     */
    public function putPublicationColorAction(Publication $publication, Request $request)
    {
        $manager = $this->getDoctrine()->getManager();
        $this->authorize($publication->getOwner());
        $publication->setPublicationcolor(null);
        $publicationcolor = $request->request->get('publicationcolor', "");
        $colorlabel = $manager->getRepository("NaturaPass\PublicationBundle\Entity\PublicationColor")->find($publicationcolor);
        if ($colorlabel) {
            $publication->setPublicationcolor($colorlabel);
        };
        $manager->merge($publication);
        $manager->flush();
        $aReturn = array(
            'success' => true,
            'publication' => PublicationSerialization::serializePublication($publication, $this->getUser(), true)
        );
        if (!is_null($publication->getGeolocation())) {
            $aReturn["sqlite"] = SqliteSerialization::serializeSqliteInserOrReplace("tb_carte", array(PublicationSerialization::serializePublicationSqliteRefresh2(array(), null, $publication, $this->getUser(), true)));
        }
        return $this->view($aReturn, Codes::HTTP_CREATED);
    }

    /**
     * get Sqlite of all points in map of the current user
     *
     * GET /v2/publication/sqlite
     *
     * @param Request $request
     * @return \FOS\RestBundle\View\View
     */
    public function getPublicationSqliteAction(Request $request)
    {
        $this->authorize();
        $lastId = $request->query->get('id', 0);
        $updated = $request->query->get('updated', false);
        $limit = $request->query->get('limit', 10);
        $offset = $request->query->get('offset', 0);
        $filter = array(
            'groups' => array(),
            'hunts' => array(),
            'sharing' => 3
        );
        $userGroups = $this->getUser()->getAllGroups();
        foreach ($userGroups as $group) {
            $filter["groups"][] = $group->getId();
        }
        $userHunts = $this->getUser()->getAllHunts();
        foreach ($userHunts as $hunt) {
            $filter["hunts"][] = $hunt->getId();
        }
        $qb = $this->getSharingQueryBuilder(
            'NaturaPassPublicationBundle:Publication', 'p', $filter
        );
        $qb->orderBy('p.created', 'DESC')
//            ->setFirstResult($offset)
//            ->setMaxResults($limit)
            ->getQuery();
        $paginators = new Paginator($qb, $fetchJoinCollection = true);
        if ($lastId != 0) {
            $return = array("sqlite" => array(), "user" => array());
        } else {
            $return = array("sqlite" => array());
        }
        foreach ($paginators as $publication) {
            if ($lastId == 0) {
                $get = PublicationSerialization::serializePublicationSqlite($publication, $this->getUser());
                if (!is_null($get)) {
                    $return["sqlite"][] = $get;
                }
            } else {
                if ($publication->getOwner()->getId() == $this->getUser()->getId()) {
                    $get = PublicationSerialization::serializePublicationSqliteNewPoint($lastId, $updated, $publication, $this->getUser(), false);
                    if (!is_null($get)) {
                        $return["user"][] = $get;
                    }
                } else {
                    $get = PublicationSerialization::serializePublicationSqliteNewPoint($lastId, $updated, $publication, $this->getUser());
                    if (!is_null($get)) {
                        $return["sqlite"][] = $get;
                    }
                }
            }
        }
        if ($lastId != 0) {
            $arrayDeleteId = array();
            $manager = $this->getDoctrine()->getManager();
            $qbDeleted = $manager->createQueryBuilder()->select('pd')
                ->from('NaturaPassPublicationBundle:PublicationDeleted', 'pd')
                ->getQuery()
                ->getResult();
            foreach ($qbDeleted as $publicationId) {
                $arrayDeleteId[] = $publicationId->getId();
            }
            if (count($arrayDeleteId)) {
                $return["sqlite"][] = "DELETE FROM `tb_carte` WHERE `c_id` IN (" . join(',', $arrayDeleteId) . ") AND `c_user_id` = '" . $this->getUser()->getId() . "';";
            }
        }
        return $this->view($return, Codes::HTTP_OK);
    }

    /**
     * get Sqlite of all points in map of the current user
     *
     * PUT /v2/publication/sqlite/refresh
     *
     * {
     *  "publications":"1,2,3,4,5",
     *  "updated":"1450860029"
     * }
     *
     * @param Request $request
     * @return \FOS\RestBundle\View\View
     */
    public function putPublicationSqliteRefreshAction(Request $request)
    {
        $this->authorize();
        $updated = $request->request->get('updated', false);
        $publicationIds = $request->request->get('publications', array());
        if (!is_array($publicationIds)) {
            $publicationIds = explode(",", $publicationIds);
        }
        $limit = $request->request->get('limit', 10);
        $offset = $request->request->get('offset', 0);
        $filter = array(
            'groups' => array(),
            'hunts' => array(),
            'sharing' => 3
        );
        $userGroups = $this->getUser()->getAllGroups();
        foreach ($userGroups as $group) {
            $filter["groups"][] = $group->getId();
        }
        $userHunts = $this->getUser()->getAllHunts();
        foreach ($userHunts as $hunt) {
            $filter["hunts"][] = $hunt->getId();
        }
        $qb = $this->getSharingQueryBuilder(
            'NaturaPassPublicationBundle:Publication', 'p', $filter
        );
        $qb->orderBy('p.created', 'DESC')
            ->getQuery();
        $paginators = new Paginator($qb, $fetchJoinCollection = true);
        $return = array("sqlite" => array());
        $arrayGoodIds = array();
        foreach ($paginators as $publication) {
            $arrayGoodIds[] = $publication->getId();
            $get = PublicationSerialization::serializePublicationSqliteRefresh($publicationIds, $updated, $publication, $this->getUser());
            if (!is_null($get)) {
                $return["sqlite"][] = $get;
            }
        }
        $arrayDeleteId = array();
        foreach ($publicationIds as $publicationId) {
            if (!in_array($publicationId, $arrayGoodIds)) {
                $arrayDeleteId[] = $publicationId;
            }
        }
//        $arrayDeleteId = array();
//        $manager = $this->getDoctrine()->getManager();
//        $qbDeleted = $manager->createQueryBuilder()->select('pd')
//            ->from('NaturaPassPublicationBundle:PublicationDeleted', 'pd')
//            ->getQuery()
//            ->getResult();
//        foreach ($qbDeleted as $publicationId) {
//            if (in_array($publicationId->getId(), $publicationIds)) {
//                $arrayDeleteId[] = $publicationId->getId();
//            }
//        }
        if (count($arrayDeleteId)) {
            $return["sqlite"][] = "DELETE FROM `tb_carte` WHERE `c_id` IN (" . join(',', $arrayDeleteId) . ") AND `c_user_id` = '" . $this->getUser()->getId() . "';";
        }

        return $this->view($return, Codes::HTTP_OK);
    }

    /**
     * Add a publication
     *
     * POST /v2/publications/push
     *
     * @param Request $request
     * @return \FOS\RestBundle\View\View
     */
    public function postPublicationPushAction(Request $request)
    {
        $this->authorize();
        $manager = $this->getDoctrine()->getManager();
        $publicationTest = $manager->getRepository('NaturaPassPublicationBundle:Publication')->findOneBy(
            array(
                'id' => '56600'
            )
        );
        $limit = $request->request->get('limit');
        $offset = $request->request->get('offset');
//        $usersTest = array('1','4723','6440','359','2336','3608');
        $qb = $manager->createQueryBuilder()->select('p')
            ->from('NaturaPassUserBundle:User', 'p')
            ->setFirstResult($offset)
            ->setMaxResults($limit)
            ->orderBy('p.id', 'ASC');

        $results = $qb->getQuery()->getResult();
        foreach ($results as $userGetTest){
            /*$userGetTest = $manager->getRepository('NaturaPassUserBundle:User')->findOneBy(
                array(
                    'id' => $test
                )
            );*/
            $this->getNotificationService()->queue(
                new PublicationProcessedNotification($publicationTest), $userGetTest
            );
        }
        // send notification

        return $this->view(Codes::HTTP_OK);
    }
}
