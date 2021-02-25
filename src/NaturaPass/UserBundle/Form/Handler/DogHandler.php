<?php

namespace NaturaPass\UserBundle\Form\Handler;

use Doctrine\ORM\EntityManagerInterface;
use NaturaPass\UserBundle\Entity\DogParameter;
use NaturaPass\UserBundle\Entity\DogPhoto;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Form\Form;

/**
 * Description of DogHandler
 *
 */
class DogHandler
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
     * @param \NaturaPass\UserBundle\Entity\DogParameter $dog
     * @return \NaturaPass\UserBundle\Entity\DogParameter $dog
     */
    public function onSuccess(DogParameter $dog)
    {
        $edit = $dog->getId();

        if ($photo = $this->request->files->get('dog[photo][file]', false, true)) {
            $media = new DogPhoto();
            $media->setFile($photo);

            $this->manager->remove($dog->getPhoto());

            $dog->setPhoto($media);
        }

        $this->manager->persist($dog);
        $this->manager->flush();
        return $dog;
    }

}
