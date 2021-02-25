<?php

/**
 * Created by PhpStorm.
 * User: vincentvalot
 * Date: 07/08/15
 * Time: 10:48
 */

namespace Api\ApiBundle\Controller\v2\Groups;

use Api\ApiBundle\Controller\v2\ApiRestController;
use Api\ApiBundle\Controller\v2\Serialization\GroupSerialization;
use Doctrine\ORM\Tools\Pagination\Paginator;
use FOS\RestBundle\Util\Codes;
use NaturaPass\GroupBundle\Entity\Group;
use NaturaPass\GroupBundle\Entity\GroupUser;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Api\ApiBundle\Controller\v2\Serialization\PublicationSerialization;
use NaturaPass\MainBundle\Entity\Geolocation;

class GroupPublicationsController extends ApiRestController
{

    /**
     * FR : Récupère les publications (et le groupe) avec les options passées en paramètre
     * EN : Get publications (and the group) with options passed as parameter
     *
     * GET /v2/groups/{group_id}/publications?limit=30&offset=0
     *
     * limit:   Nombre de valeurs retournés
     * offset:  Position à partir de laquelle il faut récupérer
     *
     *
     * @param Request $request
     * @param Group $group
     * @return \FOS\RestBundle\View\View
     *
     * @ParamConverter("group", class="NaturaPassGroupBundle:Group")
     */
    public function getPublicationsAction(Request $request, Group $group)
    {
        $this->authorize();
        $this->authorizeGroup($group);

//        $results = $group->getPublications()->slice(
//            $request->query->get('offset', 0), $request->query->get('limit', 5)
//        );

        $filter = array(
            'groups' => array($group->getId()),
            'hunts' => array(),
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
            if ($group->getAllowShow() == Group::ALLOW_ALL_MEMBERS || $group->isAdmin($this->getUser())) {
                $arrayPub[] = PublicationSerialization::serializePublication($result, $this->getUser());
            }
        }
        $publications = array('publications' => $arrayPub);

        return $this->view($publications, Codes::HTTP_OK);
    }

    /**
     * Récupère les publications du groupe avec les options passées en paramètre
     *
     * GET /v2/groups/{group_id}/publications/map?swLat=45.87747213066484&swLng=5.1894583414978115&neLat=45.89479835099027&neLng=5.260826558966073&categories[]=1
     *
     * @param Request $request
     * @param Group $group
     * @return \FOS\RestBundle\View\View
     *
     * @throws HttpException
     * @ParamConverter("group", class="NaturaPassGroupBundle:Group")
     */
    public function getPublicationsMapAction(Request $request, Group $group)
    {
        $this->authorize();
        $this->authorizeGroup($group);

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

//        if ($request->query->has('reset')) {
        $this->get('session')->remove('naturapass_group_map/positions_loaded');
//        }

        $manager = $this->getDoctrine()->getManager();

        /**
         * @var QueryBuilder $qb
         */
        $qb = $manager->createQueryBuilder()->select('p')
            ->from('NaturaPassPublicationBundle:Publication', 'p');

        $wheres = $qb->expr()->orX();

        $wheresGroup = $qb->expr()->orX();
        $qb->innerJoin('p.groups', 'gr');
        $wheresGroup->add($qb->expr()->eq('gr.id', ':group' . $group->getId()));
        $qb->setParameter('group' . $group->getId(), $group->getId());
        $wheres->add($wheresGroup);

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

        $alreadyLoaded = $this->get('session')->get('naturapass_group_map/positions_loaded');
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
        $this->get('session')->set('naturapass_group_map/positions_loaded', $alreadyLoaded);

        return $this->view(
            array('publications' => PublicationSerialization::serializePublications($results, $this->getUser())), Codes::HTTP_OK
        );
    }

}
