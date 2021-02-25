<?php

namespace Admin\DistributorBundle\Controller;

use Admin\DistributorBundle\Entity\Distributor;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Admin\DistributorBundle\Form\Type\DistributorType;
use Admin\DistributorBundle\Form\Handler\DistributorHandler;
use Symfony\Component\HttpFoundation\RedirectResponse;

class DefaultController extends Controller {

    public function indexAction() {
        return $this->render('AdminDistributorBundle:Default:angular.index.html.twig');
    }

    public function addAction(Request $request) {
        $form = $this->createForm(new DistributorType($this->container), new Distributor());
        $handler = new DistributorHandler($form, $request, $this->getDoctrine()->getManager());

        if ($distributor = $handler->process()) {
            return new RedirectResponse($this->get('router')->generate('admin_distributor_homepage'));
        }
        return $this->render('AdminDistributorBundle:Default:angular.add.html.twig', array(
                    'form' => $form->createView(),
                    'ajout' => 1
        ));
    }

    /**
     * @param \Admin\DistributorBundle\Entity\Distributor $distributor
     * @return \Symfony\Component\HttpFoundation\Response
     *
     * @ParamConverter("distributor", class="AdminDistributorBundle:Distributor")
     */
    public function editAction($distributor, Request $request) {
        $form = $this->createForm(new DistributorType($this->container), $distributor);
        $handler = new DistributorHandler($form, $request, $this->getDoctrine()->getManager());

        if ($handler->process()) {
            return new RedirectResponse($this->get('router')->generate('admin_distributor_homepage'));
        }
        return $this->render('AdminDistributorBundle:Default:angular.add.html.twig', array(
                    'distributor' => $distributor,
                    'form' => $form->createView(),
                    'ajout' => 0
        ));
    }

}
