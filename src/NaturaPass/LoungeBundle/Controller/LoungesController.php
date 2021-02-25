<?php

namespace NaturaPass\LoungeBundle\Controller;

use FOS\RestBundle\Util\Codes;
use NaturaPass\LoungeBundle\Entity\LoungeUser;
use NaturaPass\NotificationBundle\Entity\Lounge\SocketOnly\LoungeChangeAllowNotification;
use NaturaPass\PublicationBundle\Form\Type\PublicationFormType;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use NaturaPass\LoungeBundle\Form\Type\LoungeType;
use NaturaPass\LoungeBundle\Form\Handler\LoungeHandler;
use NaturaPass\LoungeBundle\Entity\Lounge;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

class LoungesController extends Controller
{

    public function indexAction()
    {
        return $this->render('NaturaPassLoungeBundle:Default:angular.index.html.twig');
    }

    /**
     * @param \NaturaPass\LoungeBundle\Entity\Lounge $lounge
     * @return \Symfony\Component\HttpFoundation\Response
     *
     * @ParamConverter("lounge", class="NaturaPassLoungeBundle:Lounge")
     */
    public function invitedAction($lounge)
    {
        return $this->render('NaturaPassLoungeBundle:Default:angular.index.html.twig');
    }

    public function addAction(Request $request)
    {
        $form = $this->createForm(new LoungeType($this->getUser(), $this->container), new Lounge());
        $handler = new LoungeHandler($form, $request, $this->getDoctrine()->getManager());

        if ($lounge = $handler->process()) {
            return new RedirectResponse($this->get('router')->generate('naturapass_lounge_invite', array(
                    'loungetag' => $lounge->getLoungetag())
            ));
        }

        return $this->render('NaturaPassLoungeBundle:Default:angular.add.html.twig', array(
            'form' => $form->createView(),
            'ajout' => 1
        ));
    }

    /**
     * @param \NaturaPass\LoungeBundle\Entity\Lounge $lounge
     * @return \Symfony\Component\HttpFoundation\Response
     *
     * @ParamConverter("lounge", class="NaturaPassLoungeBundle:Lounge")
     *
     * @throws AccessDeniedException
     */
    public function editAction(Lounge $lounge, Request $request)
    {
        if (in_array($this->getUser(), $lounge->getAdmins()->toArray())) {
            $form = $this->createForm(new LoungeType($this->getUser(), $this->container), $lounge);
            $handler = new LoungeHandler($form, $request, $this->getDoctrine()->getManager());

            if ($handler->process()) {
                $subscribers = $lounge->getSubscribers(array(LoungeUser::ACCESS_DEFAULT, LoungeUser::ACCESS_ADMIN), true);
                $subscribers->removeElement($this->getUser());

                $this->get('naturapass.notification')->queue(
                    new LoungeChangeAllowNotification($lounge), $subscribers->toArray()
                );
                return new RedirectResponse($this->get('router')->generate('naturapass_lounge_invite', array(
                        'loungetag' => $lounge->getLoungetag())
                ));
            }

            return $this->render('NaturaPassLoungeBundle:Default:angular.add.html.twig', array(
                'lounge' => $lounge,
                'form' => $form->createView(),
                'ajout' => 0
            ));
        } else {
            throw new AccessDeniedException();
        }
    }

    /**
     * @param \NaturaPass\LoungeBundle\Entity\Lounge $lounge
     * @throws  HttpException
     * @return \Symfony\Component\HttpFoundation\Response
     *
     * @ParamConverter("lounge", class="NaturaPassLoungeBundle:Lounge")
     */
    public function inviteAction($lounge)
    {
        if ($lounge->getAccess() == Lounge::ACCESS_PROTECTED && (!$lounge->getSubscribers()->contains($this->getUser()) && $this->getUser() != $lounge->getOwner())) {
            throw new HttpException(Codes::HTTP_FORBIDDEN);
        }

        return $this->render('NaturaPassLoungeBundle:Default:angular.invite.html.twig', array(
            'lounge' => $lounge
        ));
    }

    /**
     * @param \NaturaPass\LoungeBundle\Entity\Lounge $lounge
     * @throws  HttpException
     * @return \Symfony\Component\HttpFoundation\Response
     *
     * @ParamConverter("lounge", class="NaturaPassLoungeBundle:Lounge")
     */
    public function exitAction($lounge)
    {
        $manager = $this->getDoctrine()->getManager();
        $repository = $manager->getRepository('NaturaPassLoungeBundle:LoungeUser');

        $loungeUser = $repository->findOneBy(array(
            'user' => $this->getUser(),
            'lounge' => $lounge
        ));

        if ($loungeUser) {
            $manager->remove($loungeUser);
            $manager->flush();
        }

        return new RedirectResponse($this->get('router')->generate('naturapass_lounge_homepage'));
    }

    /**
     * @param \NaturaPass\LoungeBundle\Entity\Lounge $lounge
     * @throws  HttpException
     * @return \Symfony\Component\HttpFoundation\Response
     *
     * @ParamConverter("lounge", class="NaturaPassLoungeBundle:Lounge")
     */
    public function showAction($lounge)
    {
        if (($lounge->getAccess() == Lounge::ACCESS_PROTECTED || $lounge->getAccess() == Lounge::ACCESS_SEMIPROTECTED) && (!$lounge->isSubscriber($this->getUser()))) {
            throw new HttpException(Codes::HTTP_FORBIDDEN);
        }


        $this->get('session')->remove('naturapass_map/positions_loaded');

        return $this->render('NaturaPassLoungeBundle:Default:angular.show.html.twig', array(
            'mediaForm' => $this->createForm(new PublicationFormType($this->get('security.token_storage'), $this->container))->createView(),
            'publicationForm' => $this->createForm(new PublicationFormType($this->get('security.token_storage'), $this->container))->createView(),
            'editPublicationForm' => $this->createForm(new PublicationFormType($this->get('security.token_storage'), $this->container))->createView(),
            'editMediaPublicationForm' => $this->createForm(new PublicationFormType($this->get('security.token_storage'), $this->container, '_edit'))->createView(),
            'lounge' => $lounge
        ));
    }

}
