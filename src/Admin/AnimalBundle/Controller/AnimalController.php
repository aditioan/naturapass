<?php

namespace Admin\AnimalBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Admin\AnimalBundle\Form\Handler\AnimalHandler;
use Admin\AnimalBundle\Form\Type\AnimalType;
use Admin\AnimalBundle\Entity\Animal;

class AnimalController extends Controller {

    public function addAction(Request $request) {
        $form = $this->createForm(new AnimalType($this->container), new Animal());
        $handler = new AnimalHandler($form, $request, $this->getDoctrine()->getManager());

        if ($cardValid = $handler->process()) {
            $url = $this->container->get('router')->generate('admin_animal_list');
            $redirectResponse = new RedirectResponse($url);
            return $redirectResponse;
        } else {
            return $this->render('AdminAnimalBundle:Animal:angular.add.html.twig', array(
                        'form' => $form->createView(),
                        'add' => 1,
            ));
        }
    }

    /**
     *
     * @param \Admin\AnimalBundle\Entity\Animal $animal
     * @return type
     *
     * @ParamConverter("animal", class="AdminAnimalBundle:Animal")
     */
    public function editAction($animal, Request $request) {
        $form = $this->createForm(new AnimalType($this->container), $animal);
        $handler = new AnimalHandler($form, $request, $this->getDoctrine()->getManager());

        if ($cardValid = $handler->process()) {
            $url = $this->container->get('router')->generate('admin_animal_list');
            $redirectResponse = new RedirectResponse($url);
            return $redirectResponse;
        } else {
            return $this->render('AdminAnimalBundle:Animal:angular.add.html.twig', array(
                        'id' => $animal->getId(),
                        'form' => $form->createView(),
                        'add' => 0,
            ));
        }
    }

    public function listAction() {
        return $this->render('AdminAnimalBundle:Animal:angular.index.html.twig');
    }

    public function treeAction(Request $request) {
        $arrayTree = json_decode($request->get('tree'), true);
        if (count($arrayTree)) {
            $em = $this->getDoctrine()->getManager();
            $repoAnimal = $em->getRepository('AdminAnimalBundle:Animal');
            $repoAnimal->deleteTreeModel();
            $repoAnimal->setTreeModel($arrayTree);
        }
        return $this->render('AdminAnimalBundle:Animal:angular.tree.html.twig');
    }

}
