<?php

namespace Admin\DistributorBundle\Form\Handler;

use Doctrine\ORM\EntityManagerInterface;
use Admin\DistributorBundle\Entity\DistributorMedia;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Form\Form;
use Admin\DistributorBundle\Entity\Distributor;

/**
 * Description of DistributorHandler
 *
 */
class DistributorHandler {

    protected $request;
    protected $form;
    protected $manager;

    public function __construct(Form $form, Request $request, EntityManagerInterface $manager) {
        $this->request = $request;
        $this->form = $form;
        $this->manager = $manager;
    }

    /**
     * @return \Admin\DistributorBundle\Entity\Distributor
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
     * @param \Admin\DistributorBundle\Entity\Distributor $distributor
     * @return \Admin\DistributorBundle\Entity\Distributor $group
     */
    public function onSuccess(Distributor $distributor) {

        if ($photo = $this->request->files->get('distributor[logo][file]', false, true)) {
            $media = new DistributorMedia();
            $media->setFile($photo);

            $this->manager->remove($distributor->getLogo());

            $distributor->setLogo($media);
        }

        $this->manager->persist($distributor);
        $this->manager->flush();

        return $distributor;
    }

}
