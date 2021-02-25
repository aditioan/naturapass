<?php

namespace NaturaPass\UserBundle\Listener;

use Doctrine\Bundle\DoctrineBundle\Registry as Doctrine;
use Symfony\Component\HttpKernel\Event\FilterControllerEvent;
use DateTime;
use NaturaPass\UserBundle\Entity\User;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

class Activity
{

    protected $context;
    protected $em;

    public function __construct(TokenStorageInterface $context, Doctrine $doctrine)
    {
        $this->context = $context;
        $this->em = $doctrine->getManager();
    }

    /**
     * On each request we want to update the user's last activity datetime
     *
     * @return void
     */
    public function onKernelTerminate()
    {
        if (is_object($this->context->getToken()) && $this->em->isOpen()) {
            $user = $this->context->getToken()->getUser();
            if ($user instanceof User) {
                //here we can update the user as necessary
                $user->setLastActivity(new DateTime());
                $this->em->persist($user);
                $this->em->flush();
            }
        }
    }

}
