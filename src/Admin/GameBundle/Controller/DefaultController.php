<?php

namespace Admin\GameBundle\Controller;

use Admin\GameBundle\Entity\Game;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Admin\GameBundle\Form\Type\GameType;
use Admin\GameBundle\Form\Handler\GameHandler;
use Symfony\Component\HttpFoundation\RedirectResponse;

class DefaultController extends Controller
{

    public function indexAction()
    {
        return $this->render('AdminGameBundle:Default:angular.index.html.twig');
    }

    public function addAction(Request $request)
    {
//        return $this->render('AdminGameBundle:Default:add.html.twig');
        $form = $this->createForm(new GameType($this->container), new Game());
        $handler = new GameHandler($form, $request, $this->getDoctrine()->getManager());

        if ($game = $handler->process()) {
            return new RedirectResponse($this->get('router')->generate('admin_game_homepage'));
        }
        return $this->render('AdminGameBundle:Default:angular.add.html.twig', array(
            'form' => $form->createView(),
            'ajout' => 1
        ));
    }

    /**
     * @param \Admin\GameBundle\Entity\Game $game
     * @return \Symfony\Component\HttpFoundation\Response
     *
     * @ParamConverter("game", class="AdminGameBundle:Game")
     */
    public function editAction($game, Request $request)
    {
//        return $this->render('AdminGameBundle:Default:edit.html.twig', array('game' => $game));
        $form = $this->createForm(new GameType($this->container), $game);
        $handler = new GameHandler($form, $request, $this->getDoctrine()->getManager());

        if ($handler->process()) {
            return new RedirectResponse($this->get('router')->generate('admin_game_homepage'));
        }
        return $this->render('AdminGameBundle:Default:angular.add.html.twig', array(
            'game' => $game,
            'form' => $form->createView(),
            'ajout' => 0
        ));
    }

}
