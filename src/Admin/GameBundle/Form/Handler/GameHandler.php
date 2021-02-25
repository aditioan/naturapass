<?php

namespace Admin\GameBundle\Form\Handler;

use Doctrine\ORM\EntityManagerInterface;
use Admin\GameBundle\Entity\Game;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Form\Form;

/**
 * Description of GameHandler
 *
 */
class GameHandler {

    protected $request;
    protected $form;
    protected $manager;

    public function __construct(Form $form, Request $request, EntityManagerInterface $manager) {
        $this->request = $request;
        $this->form = $form;
        $this->manager = $manager;
    }

    /**
     * @return \Admin\GameBundle\Entity\Game
     */
    public function process() {
        if ($this->request->getMethod() === 'POST' || $this->request->getMethod() === 'PUT') {
            $this->form->handleRequest($this->request);

            if ($this->form->isValid()) {
                return $this->onSuccess($this->form->getData());
            }
        }

        return false;
    }

    /**
     * @param \Admin\GameBundle\Entity\Game $game
     * @return \Admin\GameBundle\Entity\Game $game
     */
    public function onSuccess(Game $game) {

        $this->manager->persist($game);
        $this->manager->flush();

        return $game;
    }

}
