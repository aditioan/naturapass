<?php

namespace Api\ApiBundle\Controller\Backoffice;

use Api\ApiBundle\Controller\v2\Serialization\UserSerialization;
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
class UsersController extends ApiRestController
{

    /**
     * FR : Retourne les communes
     * EN : Returns localities
     *
     * GET /backoffice/user/search?limit=10&offset=0&q=test
     *
     * @param Request $request
     * @return \FOS\RestBundle\View\View
     *
     * @View(serializerGroups={"LocalityLess"})
     */
    public function getUserSearchAction(Request $request)
    {
//        $this->authorize(null, 'ROLE_FDC');

        $search = urldecode($request->query->get('q', ''));
        $searchWithoutSpace = str_replace(' ', '', urldecode(trim($request->query->get('q', ''))));
        $select2 = $request->query->get('select2', false);

        $users = $this->getDoctrine()->getManager()
            ->createQueryBuilder()
            ->select('u')
            ->from('NaturaPassUserBundle:User', 'u')
            ->where(
                "CONCAT(u.firstname, u.lastname) LIKE :name OR CONCAT(u.lastname, u.firstname) LIKE :name OR u.firstname LIKE :firstname OR u.lastname LIKE :lastname"
            )
            ->setParameter('lastname', '%' . $search . '%')
            ->setParameter('firstname', '%' . $search . '%')
            ->setParameter('name', '%' . $searchWithoutSpace . '%')
            ->setMaxResults($request->query->get('page_limit', 100000))
            ->setFirstResult($request->query->get('page_offset', 0))
            ->getQuery()
            ->getResult();

        return $this->view(array('users' => UserSerialization::serializeUserLesss($users)), Codes::HTTP_OK);
    }
}
