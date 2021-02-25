<?php

namespace Api\ApiBundle\Form\Handler;

use FOS\UserBundle\Model\UserInterface;
use NaturaPass\UserBundle\Entity\User;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use NaturaPass\UserBundle\Entity\UserMedia;

class RegistrationFormHandler
{

    protected $request;
    protected $manager;
    protected $form;
    protected $mailer;
    protected $tokenGenerator;

    public function __construct(FormInterface $form, Request $request, \Doctrine\ORM\EntityManagerInterface $manager)
    {
        $this->form = $form;
        $this->request = $request;
        $this->manager = $manager;
    }

    /**
     * @param bool $create
     * @param bool $confirmation
     * @return bool|UserInterface
     */
    public function process($create = true, $confirmation = false)
    {
        if ('POST' === $this->request->getMethod()) {
            $this->form->bind($this->request);

            if ($this->form->isValid()) {
                return $this->onSuccess($this->form->getData(), $confirmation);
            }
        } else if ('PUT' === $this->request->getMethod()) {
            $this->form->bind($this->request);

            if ($this->form->isValid()) {
                return $this->onSuccessPUT($this->form->getData());
            }
        }

        return false;
    }

    protected function onSuccessPUT(UserInterface $user)
    {
        if ($media = $this->form->get('photo')->getData()) {
            while ($other = $user->getProfilePicture()) {
                $other->setState(UserMedia::STATE_NOTHING);
                $this->manager->persist($other);
            }

            $media->setState(UserMedia::STATE_PROFILE_PICTURE);
            $media->setOwner($user);

            $this->manager->persist($media);
        }

        $user->setUsername($user->getEmail());

        $this->manager->persist($user);
        $this->manager->flush();

        return $user;
    }

    protected function onSuccess(UserInterface $user)
    {
        if ($media = $this->form->get('photo')->getData()) {
            while ($other = $user->getProfilePicture()) {
                $other->setState(UserMedia::STATE_NOTHING);
                $this->manager->persist($other);
            }

            $media->setState(UserMedia::STATE_PROFILE_PICTURE);
            $media->setOwner($user);

            $this->manager->persist($media);
        }

        $user->setUsername($user->getEmail());
        $user->setEnabled(true);

        if (!$user->getPassword()) {
            $user->setPassword('');
        } else {
            $user->setPassword(rtrim($user->getPassword()));
        }

        if (!$user->getCourtesy()) {
            $user->setCourtesy(User::COURTESY_UNDEFINED);
        }

        $user->setFirstname(ucfirst($user->getFirstname()));
        $user->setLastname(ucfirst($user->getLastname()));

        $this->manager->persist($user);
        $this->manager->flush();

        return $user;
    }

}
