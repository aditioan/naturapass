<?php

namespace NaturaPass\UserBundle\Form\Handler;

use Doctrine\ORM\EntityManagerInterface;
use NaturaPass\UserBundle\Entity\WeaponParameter;
use NaturaPass\UserBundle\Entity\WeaponPhoto;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Form\Form;

/**
 * Description of WeaponHandler
 *
 */
class WeaponHandler
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
     * @return \NaturaPass\UserBundle\Entity\WeaponParameter
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
     * @param \NaturaPass\UserBundle\Entity\WeaponParameter $weapon
     * @return \NaturaPass\UserBundle\Entity\WeaponParameter $weapon
     */
    public function onSuccess(WeaponParameter $weapon)
    {
        $edit = $weapon->getId();

        if ($photo = $this->request->files->get('weapon[photo][file]', false, true)) {
            $media = new WeaponPhoto();
            $media->setFile($photo);

            $this->manager->remove($weapon->getPhoto());

            $weapon->setPhoto($media);
        }

        $this->manager->persist($weapon);
        $this->manager->flush();
        return $weapon;
    }

}
