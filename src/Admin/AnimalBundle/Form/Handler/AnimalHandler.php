<?php

namespace Admin\AnimalBundle\Form\Handler;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Form\Form;
use Admin\AnimalBundle\Entity\Animal;

/**
 * Description of AnimalHandler
 *
 */
class AnimalHandler {

    protected $request;
    protected $form;
    protected $manager;

    public function __construct(Form $form, Request $request, EntityManagerInterface $manager) {
        $this->request = $request;
        $this->form = $form;
        $this->manager = $manager;
    }

    /**
     * @return \Admin\AnimalBundle\Entity\Animal
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
     * @param \Admin\AnimalBundle\Entity\Animal $animal
     * @return \Admin\AnimalBundle\Entity\Animal $animal
     */
    public function onSuccess(Animal $animal) {

        $this->manager->persist($animal);
        $this->manager->flush();

        return $animal;
    }

}
