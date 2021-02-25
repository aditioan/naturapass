<?php

namespace Api\ApiBundle\Controller\v2\Hunts;

use Api\ApiBundle\Controller\v2\ApiRestController;
use Api\ApiBundle\Controller\v2\Serialization\HuntSerialization;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Tools\Pagination\Paginator;
use FOS\RestBundle\Util\Codes;
use NaturaPass\LoungeBundle\Entity\Lounge;
use NaturaPass\LoungeBundle\Entity\LoungeUser;
use NaturaPass\GroupBundle\Entity\GroupUser;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Api\ApiBundle\Controller\v2\Serialization\PublicationSerialization;
use NaturaPass\MainBundle\Entity\Geolocation;
use Api\ApiBundle\Controller\v2\Serialization\SqliteSerialization;


class HuntPublicationsController extends ApiRestController
{

    /**
     * FR : Récupère les publications (et la hunt) avec les options passées en paramètre
     * EN : Get publications (and the hunt) with options passed as parameter
     *
     * GET /v2/hunts/{hunt_id}/publications?limit=30&offset=0
     *
     * limit:   Nombre de valeurs retournés
     * offset:  Position à partir de laquelle il faut récupérer
     *
     *
     * @param Request $request
     * @param Lounge $hunt
     * @return \FOS\RestBundle\View\View
     *
     * @ParamConverter("hunt", class="NaturaPassLoungeBundle:Lounge")
     */
    public function getPublicationsAction(Request $request, Lounge $hunt)
    {
        $this->authorize();
        $this->authorizeHunt($hunt);

        $filter = array(
            'groups' => array(),
            'hunts' => array($hunt->getId()),
            'sharing' => -1
        );
        $qb = $this->getSharingQueryBuilder(
            'NaturaPassPublicationBundle:Publication', 'p', $filter, 0
        );
        $qb->orderBy('p.created', 'DESC')
            ->setFirstResult($request->query->get('offset', 0))
            ->setMaxResults($request->query->get('limit', 5))
            ->getQuery();
        $results = new Paginator($qb, $fetchJoinCollection = true);
        $arrayPub = array();
        foreach ($results as $result) {
            if ($hunt->getAllowShow() == Lounge::ALLOW_ALL_MEMBERS || $hunt->isAdmin($this->getUser())) {
                $arrayPub[] = PublicationSerialization::serializePublication($result, $this->getUser());
            }
        }
        $publications = array('publications' => $arrayPub);


        return $this->view($publications, Codes::HTTP_OK);
    }

    /**
     * Récupère les publications du groupe avec les options passées en paramètre
     *
     * GET /v2/hunts/{hunt_id}/publications/map?swLat=45.87747213066484&swLng=5.1894583414978115&neLat=45.89479835099027&neLng=5.260826558966073&categories[]=1
     *
     * @param Request $request
     * @param Lounge $hunt
     * @return \FOS\RestBundle\View\View
     *
     * @throws HttpException
     * @ParamConverter("hunt", class="NaturaPassLoungeBundle:Lounge")
     */
    public function getPublicationsMapAction(Request $request, Lounge $hunt)
    {
        $this->authorize();
        $this->authorizeHunt($hunt);

        $swLat = $request->query->get('swLat', false);
        $swLng = $request->query->get('swLng', false);
        $neLat = $request->query->get('neLat', false);
        $neLng = $request->query->get('neLng', false);
        $categories = $request->query->get('categories', array());
        $arrayCategories = array();
        foreach ($categories as $id_category) {
            $em = $this->getDoctrine()->getManager();
            $category = $em->getRepository('AdminSentinelleBundle:Category');
            $arrayCategories = array_merge($arrayCategories, $category->recursiveTree(array($category->find($id_category))));
        }
        $arrayCategories = array_unique($arrayCategories);

        if (!$swLat && !$swLng && !$neLat && !$neLng) {
            throw new HttpException(Codes::HTTP_BAD_REQUEST);
        }

        $this->get('session')->remove('naturapass_hunt_map/positions_loaded');

        $manager = $this->getDoctrine()->getManager();

        /**
         * @var QueryBuilder $qb
         */
        $qb = $manager->createQueryBuilder()->select('p')
            ->from('NaturaPassPublicationBundle:Publication', 'p');

        $wheres = $qb->expr()->orX();

        $wheresLounge = $qb->expr()->orX();
        $qb->innerJoin('p.hunts', 'hu');
        $wheresLounge->add($qb->expr()->eq('hu.id', ':hunt' . $hunt->getId()));
        $qb->setParameter('hunt' . $hunt->getId(), $hunt->getId());
        $wheres->add($wheresLounge);

        $qb->where($wheres);


        $qb->join('p.geolocation', 'g')
            ->andWhere(
                $qb->expr()->andx(
                    $qb->expr()->between('g.latitude', $swLat, $neLat), $qb->expr()->between('g.longitude', $swLng, $neLng)
                )
            );
        if (!empty($arrayCategories)) {
            $qb->innerJoin('p.observations', 'ob');
            $wheresCategory = $qb->expr()->orX();
            foreach ($arrayCategories as $id_category) {
                $wheresCategory->add($qb->expr()->eq('ob.category', ':category' . $id_category));
                $qb->setParameter('category' . $id_category, $id_category);
            }
            $qb->andWhere($wheresCategory);
        }

        $alreadyLoaded = $this->get('session')->get('naturapass_hunt_map/positions_loaded');
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
        $this->get('session')->set('naturapass_hunt_map/positions_loaded', $alreadyLoaded);

        return $this->view(
            array('publications' => PublicationSerialization::serializePublications($results, $this->getUser())), Codes::HTTP_OK
        );
    }

    /**
     * Ajoute l'ensemble des publications des groupes à la chasse
     *
     * POST /v2/hunts/{hunt_id}/publications/bygroups
     *
     * Content-Type:
     *      groups[] = 1, 2, 3
     *      categories[] = 1, 2, 3
     *
     * @param Request $request
     * @param Lounge $hunt
     * @return \FOS\RestBundle\View\View
     *
     * @throws HttpException
     * @ParamConverter("hunt", class="NaturaPassLoungeBundle:Lounge")
     */
    public function postPublicationsBygroupAction(Request $request, Lounge $hunt)
    {
        if (!$hunt->isAdmin($this->getUser())) {
            throw new HttpException(Codes::HTTP_BAD_REQUEST, $this->message('codes.403'));
        }
        $groups = $request->request->get('groups', array());
        $categories = $request->request->get('categories', array());
        if (is_array($categories) && count($categories) && strpos($categories[0], ",")) {
            $categories = explode(',', $categories[0]);
        }

        $categories = array_unique($categories);
        $arrayCategories = array();
        foreach ($categories as $id_category) {
            $em = $this->getDoctrine()->getManager();
            $category = $em->getRepository('AdminSentinelleBundle:Category');
            $arrayCategories = array_merge($arrayCategories, $category->recursiveTree(array($category->find($id_category))));
        }
        $arrayCategories = array_unique($arrayCategories);
        if (is_array($groups) && strpos($groups[0], ",")) {
            $groups = explode(',', $groups[0]);
        }

        $groups = array_unique($groups);
        $allgroup = array();
        $userGroups = $this->getUser()->getAllGroups();

        foreach ($groups as $group) {
            foreach ($userGroups as $userGroup) {
                if ($group == $userGroup->getId() && $userGroup->isAdmin($this->getUser())) {
                    $allgroup[] = $group;
                    continue;
                }
            }
        }

        if (!$request->request->has('groups')) {
            throw new HttpException(Codes::HTTP_BAD_REQUEST, $this->message('errors.parameters'));
        } else if (empty($allgroup)) {
            throw new HttpException(Codes::HTTP_BAD_REQUEST, $this->message('errors.group_admin'));
        }
        $manager = $this->getDoctrine()->getManager();
        foreach ($allgroup as $id_group) {
            $groupObject = $manager->getRepository('NaturaPassGroupBundle:Group')->find($id_group);
            if (is_object($groupObject)) {
                foreach ($groupObject->getPublications() as $publication) {
                    if (!$publication->hasHunt($hunt) && (count($arrayCategories) == 0 || $publication->hasCategories($arrayCategories))) {
                        $publication->addHunt($hunt);
                        $manager->persist($publication);

                        $val = PublicationSerialization::serializePublicationSqliteInsertOrReplace($publicationIds, $updated, $publication, $this->getUser());
                        if (!is_null($val)) {
                            $arrayValues[] = $val;
                        }
                    }
                }
            }
        }
        $manager->flush();
        if (!empty($arrayValues)) {
            $return["sqlite"] = array_merge($return["sqlite"], SqliteSerialization::serializeSqliteInserOrReplace("tb_carte", $arrayValues));
        }

        return $this->view(
            array(
                'success' => true,
                "sqlite" => $return["sqlite"]
            ), Codes::HTTP_OK
        );
    }

}