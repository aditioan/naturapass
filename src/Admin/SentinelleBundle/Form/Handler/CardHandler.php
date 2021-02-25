<?php

namespace Admin\SentinelleBundle\Form\Handler;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Form\Form;
use Admin\SentinelleBundle\Entity\Card;

/**
 * Description of CardHandler
 *
 */
class CardHandler {

    protected $request;
    protected $form;
    protected $manager;

    public function __construct(Form $form, Request $request, EntityManagerInterface $manager) {
        $this->request = $request;
        $this->form = $form;
        $this->manager = $manager;
    }

    /**
     * @return \Admin\SentinelleBundle\Entity\Card
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
     * @param \Admin\SentinelleBundle\Entity\Card $card
     * @return \Admin\SentinelleBundle\Entity\Card $card
     */
    public function onSuccess(Card $card) {
        $this->manager->persist($card);
        $this->manager->flush();

        return $card;
    }

}
