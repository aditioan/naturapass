<?php

namespace Admin\DistributorBundle\Form\Handler;

use Doctrine\ORM\EntityManagerInterface;
use Admin\DistributorBundle\Entity\BrandMedia;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Form\Form;
use Admin\DistributorBundle\Entity\Brand;

/**
 * Description of BrandHandler
 *
 */
class BrandHandler {

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
     * @param \Admin\DistributorBundle\Entity\Brand $brand
     * @return \Admin\DistributorBundle\Entity\Brand $brand
     */
    public function onSuccess(Brand $brand) {

        if ($photo = $this->request->files->get('brand[logo][file]', false, true)) {
            $media = new BrandMedia();
            $media->setFile($photo);

            $this->manager->remove($brand->getLogo());

            $brand->setLogo($media);
        }

        if ($brand->getPartner() === "on" || $brand->getPartner() == 1) {
            $brand->setPartner(1);
        } else {
            $brand->setPartner(0);
        }

        $this->manager->persist($brand);
        $this->manager->flush();

        return $brand;
    }

}
