<?php

namespace Api\ApiBundle\Controller\Admin;

use Symfony\Component\HttpFoundation\Request;
use FOS\RestBundle\Controller\Annotations\View;
use FOS\RestBundle\Util\Codes;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Admin\SentinelleBundle\Entity\Receiver;
use Api\ApiBundle\Controller\v1\ApiRestController;

/**
 * Description of GroupsController
 *
 */
class GroupController extends ApiRestController {

    /**
     * FR : Retourne les donnÃ©es des groupes
     * EN : Returns data of a groups
     *
     * GET /admin/groups?limit=10&offset=0&filter=test
     *
     * @param Request $request
     * @return \FOS\RestBundle\View\View
     *
     */
    public function getGroupsAction(Request $request) {
        $this->authorize(null, 'ROLE_ADMIN');

//        $limit = $request->query->get('limit', 10);
//        $offset = $request->query->get('offset', 0);
        $filter = urldecode($request->query->get('filter', ''));

        $manager = $this->getDoctrine()->getManager();
        $qb = $manager->createQueryBuilder()->select('g')
                ->from('NaturaPassGroupBundle:Group', 'g')
                ->where('g.name LIKE :name')
                ->orderBy('g.name', 'ASC')
                ->setParameter('name', '%' . $filter . '%');
//                ->setFirstResult($offset)
//                ->setMaxResults($limit);

        $results = $qb->getQuery()->getResult();

        return $this->view(array('groups' => \Api\ApiBundle\Controller\v2\Serialization\GroupSerialization::serializeGroupSearchs($results)), Codes::HTTP_OK);
    }

}
