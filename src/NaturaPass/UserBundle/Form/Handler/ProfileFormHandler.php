<?php

namespace NaturaPass\UserBundle\Form\Handler;

use Doctrine\ORM\EntityManagerInterface;
use FOS\UserBundle\Model\UserInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use NaturaPass\UserBundle\Entity\UserMedia;

class ProfileFormHandler {

    protected $request;
    protected $manager;
    protected $form;

    public function __construct(FormInterface $form, Request $request, EntityManagerInterface $manager) {
        $this->form = $form;
        $this->request = $request;
        $this->manager = $manager;
    }

    /**
     * @param bool $create
     * @param bool $confirmation
     * @return bool|UserInterface
     */
    public function process($create = true, $confirmation = false) {
        if ('POST' === $this->request->getMethod()) {
            $this->form->bind($this->request);

            if ($this->form->isValid()) {
                return $this->onSuccess($this->form->getData(), $confirmation);
            }
        }

        return false;
    }

    protected function onSuccess(UserInterface $user) {
        if ($this->form->has('photo')) {
            if ($media = $this->form->get('photo')->getData()) {
                while ($other = $user->getProfilePicture()) {
                    $other->setState(UserMedia::STATE_NOTHING);
                    $this->manager->persist($other);
                }

                $media->setState(UserMedia::STATE_PROFILE_PICTURE);
                $media->setOwner($user);

                $this->manager->persist($media);
            }
        }

        $user->setUsername($user->getEmail());
        $user->setEnabled(true);

        $this->manager->persist($user);
        $this->manager->flush();

        return $user;
    }

}
