<?php

namespace Api\ApiBundle\Controller\Backoffice;

use Api\ApiBundle\Controller\v2\Serialization\CategorySerialization;
use FOS\RestBundle\Util\Codes;
use Api\ApiBundle\Controller\v1\ApiRestController;

/**
 * Description of CategoriesController
 *
 */
class CategoriesController extends ApiRestController
{

    /**
     * Retourne l'arborescence
     *
     * GET /backoffice/categories
     *
     * @return \FOS\RestBundle\View\View
     *
     */
    public function getCategoriesAction()
    {
        $this->authorize(null, 'ROLE_BACKOFFICE');
        $receiver = $this->getUser()->getFirstReceiver();
        $em = $this->getDoctrine()->getManager();
        $trees = $em->getRepository('AdminSentinelleBundle:Category')->getRootNodes('lft');

        $arrayTree = array();

        foreach ($trees as $tree) {
            $array = $this->getFormatCategoryByReceiverBackoffice($tree, $receiver);
            (!is_null($array)) ? $arrayTree[] = $array : '';
        }

        return $this->view(array('tree' => $arrayTree), Codes::HTTP_OK);

    }
}
