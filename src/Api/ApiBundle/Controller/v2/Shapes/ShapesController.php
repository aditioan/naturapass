<?php
namespace Api\ApiBundle\Controller\v2\Shapes;

use Api\ApiBundle\Controller\v2\Serialization\ShapeSerialization;
use Doctrine\ORM\Tools\Pagination\Paginator;
use NaturaPass\MainBundle\Entity\Shape;
use NaturaPass\MainBundle\Entity\Point;
use NaturaPass\MainBundle\Entity\Sharing;
use NaturaPass\UserBundle\Entity\User;
use NaturaPass\MainBundle\Entity\Geolocation;
use Doctrine\ORM\Query\AST\Join;
use Symfony\Component\CssSelector\CssSelector;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\HttpFoundation\Request;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use FOS\RestBundle\Controller\Annotations\View;
use FOS\RestBundle\Util\Codes;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Doctrine\ORM\Query\Expr;
use NaturaPass\MainBundle\Component\Security\SecurityUtilities;
use Symfony\Component\HttpFoundation\File\File;
use Api\ApiBundle\Controller\v2\ApiRestController;
use NaturaPass\GroupBundle\Entity\Group;

/**
 * Description of ShapesController
 *
 */
class ShapesController extends ApiRestController
{
    public $shapeTypes = array("polygon", "polyline", "circle", "rectangle");

    /**
     * Get shapes
     *
     * GET /v2/shapes?swLat=45.87938425471556&swLng=5.189093561071786&neLat=45.892886757450654&neLng=5.261191339392099&sharing=3&reset=1
     */
    public function getShapesAction(Request $request)
    {
        $session = $this->get('session');
        $session->start();
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
        if ($request->query->has('reset')) {
            $session->remove('naturapass_map/shape_loaded');
        }
        $filter = array(
            'groups' => array(),
            'hunts' => array(),
        );
        if ($request->query->has('sharing') && $request->query->get('sharing') > -1) {
            $filter["sharing"] = $request->query->get('sharing');
        }
        $groups = $request->query->get('groups', array());
        $hunts = $request->query->get('hunts', array());
        if (!$swLat && !$swLng && !$neLat && !$neLng && !$sharing && !$group && !$hunt) {
            throw new HttpException(Codes::HTTP_BAD_REQUEST);
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
            'NaturaPassMainBundle:Shape',
            'p',
            $filter,
            true
        );
        $limit = $request->query->get('limit', 500);
        $offset = $request->query->get('offset', 0);
        $shapes = $qb->join('NaturaPassMainBundle:Point', 'sp', Expr\Join::WITH, 'p = sp.shape')
            ->andWhere(
                $qb->expr()->andx(
                    $qb->expr()->between('sp.latitude', $swLat, $neLat),
                    $qb->expr()->between('sp.longitude', $swLng, $neLng)
                )
            )
            ->distinct()
            ->orderBy('p.updated', 'DESC')
            ->setFirstResult($offset)
            ->setMaxResults($limit);
        $alreadyLoaded = $session->get('naturapass_map/shape_loaded');
        if (is_array($alreadyLoaded)) {
            $qb->andWhere('p.id NOT IN (:loadedIds)')
                ->setParameter('loadedIds', $alreadyLoaded);
        } else {
            $alreadyLoaded = array();
        }
        $shapes = $qb->getQuery()
            ->getResult();
        $shapeArray = array();

        foreach ($shapes as $shape) {
            $alreadyLoaded[] = $shape->getId();
            $shapeArray[] = ShapeSerialization::serializeShape($shape, $this->getUser());
        }

        $session->set('naturapass_map/shape_loaded', $alreadyLoaded);
        return $this->view(array('shapes' => $shapeArray), Codes::HTTP_OK);
    }

    /**
     * Get shapes
     *
     * GET /v2/shapes/mobile?swLat=45.87938425471556&swLng=5.189093561071786&neLat=45.892886757450654&neLng=5.261191339392099&sharing=3&reset=1
     */
    public function getShapesMobileAction(Request $request)
    {
        $session = $this->get('session');
        $session->start();
        $this->authorize();
        $swLat = $request->query->get('swLat', false);
        $swLng = $request->query->get('swLng', false);
        $neLat = $request->query->get('neLat', false);
        $neLng = $request->query->get('neLng', false);
        if (!$swLat && !$swLng && !$neLat && !$neLng) {
            throw new HttpException(Codes::HTTP_BAD_REQUEST);
        }
        if ($request->query->has('reset')) {
            $session->remove('naturapass_map/shape_loaded');
        }
        $qb = $this->getSharingQueryBuilder(
            'NaturaPassMainBundle:Shape',
            'p',
            $request->query->get('sharing', Sharing::NATURAPASS),
            true
        );
        $limit = $request->query->get('limit', 500);
        $offset = $request->query->get('offset', 0);
        $shapes = $qb->join('NaturaPassMainBundle:Point', 'sp', Expr\Join::WITH, 'p = sp.shape')
            ->andWhere(
                $qb->expr()->andx(
                    $qb->expr()->between('sp.latitude', $swLat, $neLat),
                    $qb->expr()->between('sp.longitude', $swLng, $neLng)
                )
            )
            ->distinct()
            ->orderBy('p.updated', 'DESC')
            ->setFirstResult($offset)
            ->setMaxResults($limit);
        $alreadyLoaded = $session->get('naturapass_map/shape_loaded');
        if (is_array($alreadyLoaded)) {
            $qb->andWhere('p.id NOT IN (:loadedIds)')
                ->setParameter('loadedIds', $alreadyLoaded);
        } else {
            $alreadyLoaded = array();
        }
        $shapes = $qb->getQuery()
            ->getResult();
        $shapeArray = array();

        foreach ($shapes as $shape) {
            $alreadyLoaded[] = $shape->getId();
            $shapeArray[] = ShapeSerialization::serializeShapeMobile($shape, $this->getUser());
        }

        $session->set('naturapass_map/shape_loaded', $alreadyLoaded);
        return $this->view(array('shapes' => $shapeArray), Codes::HTTP_OK);
    }

    /**
     * Add a shape
     *
     * JSON li�:
     * {
     *      "shape": {
     *          "data": [],
     *          "type": "polygon" //circle | polyline | rectangle
     *      }
     *
     * }
     *
     * POST /v2/shapes
     */
    public function postShapeAction(Request $request)
    {
        $this->authorize();
        $shapeParams = $request->request->get('shape', false);

        if (!$shapeParams || !isset($shapeParams["data"]) || !isset($shapeParams["type"]) || !in_array($shapeParams["type"], $this->shapeTypes)) {
            throw new HttpException(Codes::HTTP_BAD_REQUEST, $this->message("codes.400"));
        }

        $manager = $this->getDoctrine()->getManager();
        // suspend auto-commit
        $manager->getConnection()->beginTransaction();

        // Try and make the transaction
        try {
            $sharing = new Sharing;
            $sharing->setShare((int)$shapeParams["sharing"]);
            $manager->persist($sharing);
            $manager->flush();
            $shape = new Shape();
            $shape->setData($shapeParams["data"]);
            $shape->setType(strtolower($shapeParams["type"]));
            $shape->setTitle($shapeParams["title"]);
            $shape->setDescription($shapeParams["description"]);
            $shape->setSharing($sharing);
            $shape->setOwner($this->getUser());
            if (isset($shapeParams["groups"])) {

                $groups = $shapeParams["groups"];
                $shape->removeAllGroups();
                foreach ($groups as $group_id) {
                    $group = $manager->getRepository('NaturaPassGroupBundle:Group')->findOneBy(array("id" => $group_id));
                    if (is_object($group)) {
                        $shape->addGroup($group);
                    }
                }
            }

            if (isset($shapeParams["hunts"])) {
                $hunts = $shapeParams["hunts"];
                $shape->removeAllHunts();
                foreach ($hunts as $hunt_id) {
                    $hunt = $manager->getRepository('NaturaPassLoungeBundle:Lounge')->find(array("id" => $hunt_id));
                    if (is_object($hunt)) {
                        $shape->addHunt($hunt);
                    }
                }
            }
            $manager->persist($shape);
            $manager->flush();
            $points = array();
            if (isset($shapeParams["data"]["paths"])) {
                $points = $shapeParams["data"]["paths"];
            } elseif (isset($shapeParams["data"]["bounds"])) {
                $points = $shapeParams["data"]["bounds"];
            }
            foreach ($points as $point) {
                $shapePoint = new Point();

                $shapePoint->setShape($shape);
                $shapePoint->setLatitude($point[0]);
                $shapePoint->setLongitude($point[1]);
                $manager->persist($shapePoint);
                $manager->flush();
            }
            $shape->calculPoints();
            $shape->getCentre();

            // Try and commit the transaction
            $manager->getConnection()->commit();
        } catch (Exception $e) {
            // Rollback the failed transaction attempt
            $manager->getConnection()->rollback();
            throw $e;
        }
        $session = $this->get('session');
        $session->start();
        $alreadyLoaded = $session->get('naturapass_map/shape_loaded');
        if (!is_array($alreadyLoaded)) {
            $alreadyLoaded = array();
        }
        $alreadyLoaded[] = $shape->getId();
        $session->set('naturapass_map/shape_loaded', $alreadyLoaded);
        return $this->view(array("shapeId" => $shape->getId()), Codes::HTTP_OK);
    }

    /**
     * Update shape data
     *
     * PUT /v2/{shape}/shape
     *
     * @param \NaturaPass\MainBundle\Entity\Shape $shape
     *
     * @ParamConverter("shape", class="NaturaPassMainBundle:Shape")
     *
     * @return View
     * @throws HttpException
     */
    public function putShapeAction(Shape $shape, Request $request)
    {
        $this->authorize();
        $shapeParams = $request->request->get('shape', false);

        if (!$shapeParams || !isset($shapeParams["data"]))
            throw new HttpException(Codes::HTTP_BAD_REQUEST, $this->message("codes.400"));

        $manager = $this->getDoctrine()->getManager();
        $manager->getConnection()->beginTransaction();
        try {
            $shape->getSharing()->setShare((int)$shapeParams["sharing"]);
            $shape->setData($shapeParams["data"]);
            $shape->setTitle($shapeParams["title"]);
            $shape->setDescription($shapeParams["description"]);

            if (isset($shapeParams["groups"])) {
                $groups = $shapeParams["groups"];
                $shape->removeAllGroups();
                foreach ($groups as $group_id) {
                    $group = $manager->getRepository('NaturaPassGroupBundle:Group')->find(array("id" => $group_id));
                    if (is_object($group)) {
                        $shape->addGroup($group);
                    }
                }
            }

            if (isset($shapeParams["hunts"])) {
                $hunts = $shapeParams["hunts"];
                $shape->removeAllHunts();
                foreach ($hunts as $hunt_id) {
                    $hunt = $manager->getRepository('NaturaPassLoungeBundle:Lounge')->find(array("id" => $hunt_id));
                    if (is_object($hunt)) {
                        $shape->addHunt($hunt);
                    }
                }
            }
            $manager->persist($shape);
            $manager->flush();
            $points = array();
            if (isset($shapeParams["data"]["paths"])) {
                $points = $shapeParams["data"]["paths"];
            } elseif (isset($shapeParams["data"]["bounds"])) {
                $points = $shapeParams["data"]["bounds"];
            }
            $shapePoints = $shape->getPoints();
            $i = 0;
            foreach ($points as $point) {
                if (isset($shapePoints[$i])) {
                    $shapePoint = $shapePoints[$i];
                } else {
                    $shapePoint = new Point();
                }

                $shapePoint->setShape($shape);
                $shapePoint->setLatitude($point[0]);
                $shapePoint->setLongitude($point[1]);
                $manager->persist($shapePoint);
                $manager->flush();
                $i++;
            }
            for ($j = $i; $j < count($shapePoints); $j++) {
                $manager->remove($shapePoints[$j]);
            }
            $shape->calculPoints();
            $shape->getCentre();

            // Try and commit the transaction
            $manager->getConnection()->commit();
        } catch (Exception $e) {
            // Rollback the failed transaction attempt
            $manager->getConnection()->rollback();
            throw $e;
        }

        return $this->view($this->success(), Codes::HTTP_OK);
    }

    /**
     * Delete a shape
     *
     * DELETE /v2/{shape}/shape
     *
     * @param \NaturaPass\MainBundle\Entity\Shape $shape
     *
     * @ParamConverter("shape", class="NaturaPassMainBundle:Shape")
     *
     * @return View
     * @throws HttpException
     */
    public function deleteShapeAction(Shape $shape)
    {
        $this->authorize();
        $manager = $this->getDoctrine()->getManager();
        $manager->remove($shape);
        $manager->flush();
        return $this->view($this->success(), Codes::HTTP_OK);
    }

    private static function checkChildrenExist($array, $tagNameElement)
    {
        $toReturn = "";
        foreach ($array as $key => $element) {
            if ($key == $tagNameElement) {
                return (string)$element;
            } else if (is_array($element) || is_object($element) && $toReturn == "") {
                $toReturn = ShapesController::checkChildrenExist($element, $tagNameElement);
            }
        }
        return $toReturn;
    }

    private static function purgeAttributes($object)
    {
        foreach ($object as $key => $value) {
            if (gettype($value) == 'object') {
                $object->$key = purgeAttributes($value);
            }

            if ($key == '@attributes') {
                unset($object->$key);
            }
        }

        return $object;
    }


    /**
     * Add a shape with KML
     *
     * GET /v2/shape/kml
     */
    public function getShapeKmlAction(Request $request)
    {
//        $this->authorize();
        $file_content = file_get_contents($_SERVER['DOCUMENT_ROOT'] . '/uploads/doc.kml');
        $xml = new \SimpleXMLElement($file_content);
        $xml->registerXPathNamespace('kml', 'http://www.opengis.net/kml/2.2');
        $manager = $this->getDoctrine()->getManager();
        $group = $manager->getRepository('NaturaPassGroupBundle:Group')->find(893);

        foreach ($xml->xpath("//kml:Folder") as $folder) {
            $folder->registerXPathNamespace('kml', 'http://www.opengis.net/kml/2.2');
//            if (count($folder->xpath("//kml:Folder"))) {
//            echo "<pre>";
//            print_r([$folder->name, $folder->xpath("//kml:Folder")]);
//            echo "</pre>";
//            }
            $color = "";
            $style = ShapesController::checkChildrenExist($folder, "styleUrl");
            if ($style != "" && is_string($style)) {
                foreach ($xml->xpath('//kml:Style[@id="' . str_replace("#", '', $style) . '"]//kml:PolyStyle') as $PolyStyle) {
                    $color = "#" . $PolyStyle->color;
                }
            }
            if ($color == "") {
                $color = "#000000";
            }
            foreach ($folder->xpath("//kml:Polygon") as $polygon) {
//                echo "<pre>";
//                print_r($polygon);
//                echo "</pre>";
                $polygon->registerXPathNamespace('kml', 'http://www.opengis.net/kml/2.2');
                $coordinates = array();
                $datas = array("paths" => array(), "options" => array("color" => "#DCDCDC"));
                foreach ($polygon->xpath("kml:outerBoundaryIs//kml:coordinates") as $coordinate) {
                    $explode = explode(" ", (string)$coordinate);
                    foreach ($explode as $geolocation) {
                        if ($geolocation != "") {
                            $latLng = explode(",", $geolocation);
                            if (count($latLng) >= 2) {
                                $coordinates[] = array('lng' => trim($latLng[0]), 'lat' => trim($latLng[1]));
                                $datas["paths"][] = array(trim($latLng[1]) . "," . trim($latLng[0]));
                            }
                        }
                    }
                }
                if (count($coordinates)) {
                    $shape = new Shape();
                    $shape->setOwner($this->getUser());
                    $shape->addGroup($group);
                    $sharing = new Sharing();
                    $sharing->setShare(Sharing::USER);
                    $shape->setSharing($sharing);
                    $shape->setType("polygon");
                    $shape->setTitle($folder->name);
                    $shape->setDescription($folder->description);
                    $shape->setData(str_replace(array('"],["', '"]],', ':[["'), array('],[', ']],', ':[['), json_encode($datas)));
                    $shape->calculPoints();
                    $shape->getCentre();
                    $manager->persist($shape);
                    foreach ($coordinates as $coordinate) {
                        $point = new Point();
                        $point->setLatitude($coordinate["lat"]);
                        $point->setLongitude($coordinate["lng"]);
                        $point->setShape($shape);
                        $manager->persist($point);
                    }
                }
            }
        }

        return $this->view($this->success(), Codes::HTTP_OK);

    }

    /**
     * get Sqlite of all shapes in map of the current user
     *
     * PUT /v2/shape/sqlite/refresh
     *
     * {
     *  "shapes":"1,2,3,4,5",
     *  "updated":"1450860029"
     * }
     *
     * @param Request $request
     * @return \FOS\RestBundle\View\View
     */
    public function putShapeSqliteRefreshAction(Request $request)
    {
        $this->authorize();
        $updated = $request->request->get('updated', false);
        $sahpeIds = $request->request->get('shapes', array());
        if (!is_array($sahpeIds)) {
            $sahpeIds = explode(",", $sahpeIds);
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
            'NaturaPassMainBundle:Shape',
            'p',
            $filter,
            true
        );
        $qb->orderBy('p.created', 'DESC')
//            ->setFirstResult($offset)
//            ->setMaxResults($limit)
            ->getQuery();
        $paginators = new Paginator($qb, $fetchJoinCollection = true);
        $return = array("sqlite" => array());
        $allIds = array();
        $arrayDeleteId = array();
        foreach ($paginators as $shape) {
            $allIds[] = $shape->getId();
            $get = ShapeSerialization::serializeShapeSqliteRefresh($sahpeIds, $updated, $shape, $this->getUser());
            if (!is_null($get)) {
                $return["sqlite"][] = $get;
            }
        }
        foreach ($sahpeIds as $shapeId) {
            if (!in_array($shapeId, $allIds)) {
                $arrayDeleteId[] = $shapeId;
            }
        }
        if (count($arrayDeleteId)) {
            $return["sqlite"][] = "DELETE FROM `tb_shape` WHERE `c_id` IN (" . join(',', $arrayDeleteId) . ") AND `c_user_id` = '" . $this->getUser()->getId() . "';";
        }
        return $this->view($return, Codes::HTTP_OK);
    }
}