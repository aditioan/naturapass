<?php

namespace Api\ApiBundle\Controller\Backoffice;

use Doctrine\Common\Collections\ArrayCollection;
use Symfony\Component\HttpFoundation\Request;
use FOS\RestBundle\Controller\Annotations\View;
use FOS\RestBundle\Util\Codes;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Api\ApiBundle\Controller\v1\ApiRestController;
use Admin\SentinelleBundle\Entity\Locality;
use Api\ApiBundle\Controller\v2\Serialization\LocalitySerialization;

/**
 * Description of LocalityController
 *
 */
class LocalityController extends ApiRestController
{

    /**
     * FR : Retourne les communes
     * EN : Returns localities
     *
     * GET /backoffice/localitiy/search?limit=10&offset=0&filter=test
     *
     * @param Request $request
     * @return \FOS\RestBundle\View\View
     *
     * @View(serializerGroups={"LocalityLess"})
     */
    public function getLocalitySearchAction(Request $request)
    {
//        $this->authorize(null, 'ROLE_FDC');

        $limit = $request->query->get('limit', 10);
        $offset = $request->query->get('offset', 0);
        $filter = urldecode($request->query->get('filter', ''));

        $manager = $this->getDoctrine()->getManager();
        $qb = $manager->createQueryBuilder()->select('l')
            ->from('AdminSentinelleBundle:Locality', 'l')
            ->where('l.name LIKE :name')
            ->orWhere('l.postal_code LIKE :name')
            ->orWhere('l.administrative_area_level_2 LIKE :name')
            ->orderBy('l.name', 'ASC')
            ->setParameter('name', '%' . $filter . '%')
            ->setFirstResult($offset)
            ->setMaxResults($limit);
        $results = $qb->getQuery()->getResult();

        return $this->view(array('localities' => LocalitySerialization::serializeLocalitySearchs($results)), Codes::HTTP_OK);
    }

    /**
     * FR : Retourne les communes par INSEE
     * EN : Returns localities by INSEE
     *
     * GET /backoffice/localitiy/insee/search?limit=10&offset=0&filter=test
     *
     * @param Request $request
     * @return \FOS\RestBundle\View\View
     *
     * @View(serializerGroups={"LocalityLess"})
     */
    public function getLocalityInseeSearchAction(Request $request)
    {
//        $this->authorize(null, 'ROLE_FDC');

        $limit = $request->query->get('limit', 10);
        $offset = $request->query->get('offset', 0);
        $filter = urldecode($request->query->get('filter', ''));

        $manager = $this->getDoctrine()->getManager();
        $qb = $manager->createQueryBuilder()->select('l')
            ->from('AdminSentinelleBundle:Locality', 'l')
            ->where('l.insee LIKE :name')
            ->orderBy('l.name', 'ASC')
            ->setParameter('name', '%' . $filter . '%')
            ->setFirstResult($offset)
            ->setMaxResults($limit);
        $results = $qb->getQuery()->getResult();

        return $this->view(array('insees' => LocalitySerialization::serializeLocalitySearchs($results)), Codes::HTTP_OK);
    }
}
