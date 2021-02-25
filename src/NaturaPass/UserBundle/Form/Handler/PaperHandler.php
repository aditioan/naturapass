<?php

namespace NaturaPass\UserBundle\Form\Handler;

use Doctrine\ORM\EntityManagerInterface;
use NaturaPass\UserBundle\Entity\PaperParameter;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Form\Form;

/**
 * Description of PaperHandler
 *
 */
class PaperHandler
{

    protected $request;
    protected $form;
    protected $manager;

    public function __construct(Form $form, Request $request, EntityManagerInterface $manager)
    {
        $this->request = $request;
        $this->form = $form;
        $this->manager = $manager;
    }

    /**
     * @return \NaturaPass\UserBundle\Entity\DogParameter
     */
    public function process()
    {
        if ($this->request->getMethod() === 'POST' || $this->request->getMethod() === 'PUT') {
            $this->form->handleRequest($this->request);

            if ($this->form->isValid()) {
                return $this->onSuccess($this->form->getData());
            }
        }

        return false;
    }

    /**
     * @param \NaturaPass\UserBundle\Entity\PaperParameter $paper
     * @return \NaturaPass\UserBundle\Entity\PaperParameter $paper
     */
    public function onSuccess(PaperParameter $paper)
    {
        $this->manager->persist($paper);
        $this->manager->flush();
        return $paper;
    }

}
