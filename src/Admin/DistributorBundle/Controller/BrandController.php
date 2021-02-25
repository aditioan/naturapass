<?php

namespace Admin\DistributorBundle\Controller;

use Admin\DistributorBundle\Entity\Brand;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Admin\DistributorBundle\Form\Type\BrandType;
use Admin\DistributorBundle\Form\Handler\BrandHandler;
use Symfony\Component\HttpFoundation\RedirectResponse;

class BrandController extends Controller
{

    public function indexAction()
    {
        return $this->render('AdminDistributorBundle:Default:angular.index-brand.html.twig');
    }

    public function addAction(Request $request)
    {
        $form = $this->createForm(new BrandType($this->container), new Brand());
        $handler = new BrandHandler($form, $request, $this->getDoctrine()->getManager());

        if ($brand = $handler->process()) {
            return new RedirectResponse($this->get('router')->generate('admin_distributor_brand_homepage'));
        }
        return $this->render('AdminDistributorBundle:Default:angular.add-brand.html.twig', array(
            'form' => $form->createView(),
            'ajout' => 1
        ));
    }

    /**
     * @param \Admin\DistributorBundle\Entity\Brand $brand
     * @return \Symfony\Component\HttpFoundation\Response
     *
     * @ParamConverter("brand", class="AdminDistributorBundle:Brand")
     */
    public function editAction($brand, Request $request)
    {
        $form = $this->createForm(new BrandType($this->container), $brand);
        $handler = new BrandHandler($form, $request, $this->getDoctrine()->getManager());

        if ($handler->process()) {
            return new RedirectResponse($this->get('router')->generate('admin_distributor_brand_homepage'));
        }
        return $this->render('AdminDistributorBundle:Default:angular.add-brand.html.twig', array(
            'brand' => $brand,
            'form' => $form->createView(),
            'ajout' => 0
        ));
    }

}
