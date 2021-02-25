<?php

namespace Api\ApiBundle\Controller\Admin;

use Doctrine\Common\Collections\ArrayCollection;
use Admin\DistributorBundle\Entity\Brand;
use Api\ApiBundle\Controller\v1\ApiRestController;
use FOS\RestBundle\Util\Codes;
use Symfony\Component\HttpFoundation\Request;
use FOS\RestBundle\Controller\Annotations\View;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;

class BrandController extends ApiRestController {

    /**
     * FR : Retourne les données d'une marque
     * EN : Returns datas of a brand
     *
     * GET /admin/brands/{brand_id}
     *
     * @param Brand $brand
     * @return \FOS\RestBundle\View\View
     *
     * @ParamConverter("brand", class="AdminDistributorBundle:Brand")
     * @View(serializerGroups={"BrandDetail", "BrandLess"})
     */
    public function getBrandAction(Brand $brand) {
        return $this->view(array('brand' => $brand), Codes::HTTP_OK);
    }

    /**
     * FR : Retourne les marques
     * EN : Returns brands
     *
     * GET /admin/brands?limit=10&offset=0&filter=test
     *
     * @param Request $request
     * @return \FOS\RestBundle\View\View
     */
    public function getBrandsAction(Request $request) {
        $limit = $request->query->get('limit', 10);
        $offset = $request->query->get('offset', 0);
        $filter = urldecode($request->query->get('filter', ''));

        $manager = $this->getDoctrine()->getManager();
        $qb = $manager->createQueryBuilder()->select('b')
                ->from('AdminDistributorBundle:Brand', 'b')
                ->where('b.name LIKE :name')
                ->orderBy('b.name', 'ASC')
                ->setParameter('name', '%' . $filter . '%')
                ->setFirstResult($offset)
                ->setMaxResults($limit);

        $results = $qb->getQuery()->getResult();
        $brands = array();

        foreach ($results as $result) {
                $brands[] = $this->getFormatBrandDetail($result);
        }

        return $this->view(array('brands' => $brands), Codes::HTTP_OK);
    }

    /**
     * FR : Supprime une marque de la BDD
     * EN : Remove a brand of database
     *
     * GET /admin/brands/{brand_id}
     *
     * @param Brand $brand
     * @return \FOS\RestBundle\View\View
     *
     * @ParamConverter("brand", class="AdminDistributorBundle:Brand")
     */
    public function deleteBrandAction(Brand $brand) {
        $this->authorize(null, 'ROLE_ADMIN');

        $manager = $this->getDoctrine()->getManager();

        $manager->remove($brand);
        $manager->flush();

        return $this->view($this->success(), Codes::HTTP_NO_CONTENT);
    }

}
