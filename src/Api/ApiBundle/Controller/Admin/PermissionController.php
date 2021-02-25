<?php

namespace Api\ApiBundle\Controller\Admin;

use Symfony\Component\HttpFoundation\Request;
use FOS\RestBundle\Controller\Annotations\View;
use FOS\RestBundle\Util\Codes;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Admin\SentinelleBundle\Entity\Zone;
use Api\ApiBundle\Controller\v1\ApiRestController;

/**
 * Description of PermissionController
 *
 */
class PermissionController extends ApiRestController {

    /**
     * FR : Retourne les données d'une zone
     * EN : Returns datas of a zone
     *
     * GET /admins/{ID_ZONE}/zone
     *
     * @param Zone $zone
     *
     * @return \FOS\RestBundle\View\View
     *
     * @ParamConverter("zone", class="AdminSentinelleBundle:Zone")
     * @View(serializerGroups={"ZoneDetail", "ZoneLess"})
     *
     */
    public function getZoneAction(Zone $zone) {
        $this->authorize(null, 'ROLE_ADMIN');

        return $this->view(array('zone' => $zone), Codes::HTTP_OK);
    }

    /**
     * FR : Retourne les zones
     * EN : Returns zones
     *
     * GET /admin/zones?limit=10&offset=0&filter=test
     *
     * @param Request $request
     *
     * @return \FOS\RestBundle\View\View
     *
     * @View(serializerGroups={"ZoneLess"})
     *
     */
    public function getPermissionsAction(Request $request) {
        $this->authorize(null, 'ROLE_ADMIN');

        $limit = $request->query->get('limit', 10);
        $offset = $request->query->get('offset', 0);
        $filter = urldecode($request->query->get('filter', ''));

        $manager = $this->getDoctrine()->getManager();

        $qb = $manager->createQueryBuilder()->select('z')
                ->from('AdminSentinelleBundle:Zone', 'z')
                ->where('z.name LIKE :name')
                ->orderBy('z.name', 'ASC')
                ->setParameter('name', '%' . strtolower($filter) . '%')
                ->setFirstResult($offset)
                ->setMaxResults($limit);

        $results = $qb->getQuery()->getResult();

        return $this->view(array('zones' => $results), Codes::HTTP_OK);
    }

}
