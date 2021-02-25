<?php

namespace NaturaPass\GroupBundle\Controller;

use FOS\RestBundle\Util\Codes;
use NaturaPass\NotificationBundle\Entity\Group\SocketOnly\GroupChangeAllowNotification;
use NaturaPass\PublicationBundle\Form\Type\PublicationFormType;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use NaturaPass\GroupBundle\Form\Type\GroupType;
use NaturaPass\GroupBundle\Form\Handler\GroupHandler;
use NaturaPass\GroupBundle\Entity\Group;
use NaturaPass\GroupBundle\Entity\GroupUser;
use Symfony\Component\HttpKernel\Exception\HttpException;

class GroupsController extends Controller
{

    public function indexAction()
    {
        return $this->render('NaturaPassGroupBundle:Default:angular.index.html.twig');
    }

    public function addAction(Request $request)
    {
        $form = $this->createForm(new GroupType($this->getUser(), $this->container), new Group());
        $handler = new GroupHandler($form, $request, $this->getDoctrine()->getManager());

        if ($group = $handler->process()) {
            return new RedirectResponse($this->get('router')->generate('naturapass_group_invite', array(
                    'grouptag' => $group->getGrouptag())
            ));
        }

        $manager = $this->container->get('doctrine')->getManager();

        $paramEmails = $manager->getRepository("NaturaPassEmailBundle:EmailModel")->findAll();
        $emails = array();
        foreach ($paramEmails as $email) {
            if (strpos($email->getType(), "group.") !== false) {
                $emails[] = $email;
            }
        }
        $metadata = $manager->getMetadataFactory()->getMetadataFor('NaturaPass\NotificationBundle\Entity\AbstractNotification');
        $arrayNotifications = array();
        foreach ($metadata->discriminatorMap as $notificationType => $notificationClass) {
            $arrayNotifications[$notificationType] = $notificationClass::PERIOD;;
        }
        $notification_parameter = array();
        foreach ($arrayNotifications as $notification => $period) {
            if (strpos($notification, "group.") !== false) {
                $notification_parameter[$notification] = $period;
            }
        }


        return $this->render('NaturaPassGroupBundle:Default:angular.add.html.twig', array(
            'form' => $form->createView(),
            'group' => new Group(),
            'ajout' => 1,
            'emails' => $emails,
            'notifications' => $notification_parameter
        ));
    }

    /**
     * @param \NaturaPass\GroupBundle\Entity\Group $group
     * @return \Symfony\Component\HttpFoundation\Response
     *
     * @ParamConverter("group", class="NaturaPassGroupBundle:Group")
     */
    public function invitedAction($group)
    {
        if (!$group->isSubscriber($this->getUser(), array(GroupUser::ACCESS_INVITED, GroupUser::ACCESS_RESTRICTED))) {
            if ($group->isSubscriber($this->getUser(), array(GroupUser::ACCESS_DEFAULT, GroupUser::ACCESS_ADMIN))) {
                $parameters = array(
                    'group' => $group,
                    'mediaForm' => $this->createForm(new PublicationFormType($this->get('security.token_storage'), $this->container))->createView(),
                    'publicationForm' => $this->createForm(new PublicationFormType($this->get('security.token_storage'), $this->container))->createView(),
                    'editPublicationForm' => $this->createForm(new PublicationFormType($this->get('security.token_storage'), $this->container))->createView(),
                    'editMediaPublicationForm' => $this->createForm(new PublicationFormType($this->get('security.token_storage'), $this->container, '_edit'))->createView(),
                    'menuInit' => false
                );

                $this->get('session')->remove('upload_handler/publication.upload');

                return $this->render('NaturaPassGroupBundle:Default:angular.show.html.twig', $parameters);
            }

            return $this->render('NaturaPassGroupBundle:Default:angular.index.html.twig');
        }

//        return $this->render('NaturaPassGroupBundle:Default:angular.index.html.twig');
        return $this->render('NaturaPassGroupBundle:Default:angular.index.html.twig', array('group' => $group));
    }

    /**
     * @param \NaturaPass\GroupBundle\Entity\Group $group
     * @throws AccessDeniedException
     * @return \Symfony\Component\HttpFoundation\Response
     *
     * @ParamConverter("group", class="NaturaPassGroupBundle:Group")
     */
    public function editAction($group, Request $request)
    {
        if (in_array($this->getUser(), $group->getAdmins()->toArray())) {

            $form = $this->createForm(new GroupType($this->getUser(), $this->container), $group);

            $handler = new GroupHandler($form, $request, $this->getDoctrine()->getManager());

            if ($success = $handler->process()) {
                $subscribers = $group->getSubscribers(array(GroupUser::ACCESS_DEFAULT, GroupUser::ACCESS_ADMIN), true);
                $subscribers->removeElement($this->getUser());
                $this->get('naturapass.notification')->queue(
                    new GroupChangeAllowNotification($group), $subscribers->toArray()
                );
                return new RedirectResponse($this->get('router')->generate('naturapass_group_invite', array(
                        'grouptag' => $success->getGrouptag())
                ));
            }

            $manager = $this->container->get('doctrine')->getManager();

            $paramEmails = $manager->getRepository("NaturaPassEmailBundle:EmailModel")->findAll();
            $emails = array();
            foreach ($paramEmails as $email) {
                if (strpos($email->getType(), "group.") !== false) {
                    $emails[] = $email;
                }
            }

            $metadata = $manager->getMetadataFactory()->getMetadataFor('NaturaPass\NotificationBundle\Entity\AbstractNotification');
            $arrayNotifications = array();
            foreach ($metadata->discriminatorMap as $notificationType => $notificationClass) {
                $arrayNotifications[$notificationType] = $notificationClass::PERIOD;;
            }
            $notification_parameter = array();
            foreach ($arrayNotifications as $notification => $period) {
                if (strpos($notification, "group.") !== false) {
                    $notification_parameter[$notification] = $period;
                }
            }

            return $this->render('NaturaPassGroupBundle:Default:angular.add.html.twig', array(
                'form' => $form->createView(),
                'group' => $group,
                'ajout' => 0,
                'emails' => $emails,
                'notifications' => $notification_parameter
            ));
        } else {
            throw new AccessDeniedException();
        }
    }

    /**
     * @param \NaturaPass\GroupBundle\Entity\Group $group
     * @throws  AccessDeniedException
     * @return \Symfony\Component\HttpFoundation\Response
     *
     * @ParamConverter("group", class="NaturaPassGroupBundle:Group")
     */
    public function inviteAction($group)
    {
        if (($group->getAccess() == Group::ACCESS_PROTECTED || $group->getAccess() == Group::ACCESS_SEMIPROTECTED) && !$group->isSubscriber($this->getUser(), array(GroupUser::ACCESS_ADMIN))) {
            throw new AccessDeniedException();
        }

        return $this->render('NaturaPassGroupBundle:Default:angular.invite.html.twig', array(
            'group' => $group
        ));
    }

    /**
     * @param \NaturaPass\GroupBundle\Entity\Group $group
     * @throws  AccessDeniedException
     * @return \Symfony\Component\HttpFoundation\Response
     *
     * @ParamConverter("group", class="NaturaPassGroupBundle:Group")
     */
    public function showAction($group)
    {
        if (($group->getAccess() == Group::ACCESS_PROTECTED || $group->getAccess() == Group::ACCESS_SEMIPROTECTED) && (!$group->isSubscriber($this->getUser()))) {
            throw new AccessDeniedException(Codes::HTTP_FORBIDDEN);
        }

        $parameters = array(
            'mediaForm' => $this->createForm(new PublicationFormType($this->get('security.token_storage'), $this->container))->createView(),
            'publicationForm' => $this->createForm(new PublicationFormType($this->get('security.token_storage'), $this->container))->createView(),
            'editPublicationForm' => $this->createForm(new PublicationFormType($this->get('security.token_storage'), $this->container))->createView(),
            'editMediaPublicationForm' => $this->createForm(new PublicationFormType($this->get('security.token_storage'), $this->container, '_edit'))->createView(),
            'group' => $group,
            'all_add' => $group->checkAllowAdd($this->getUser()),
            'menuInit' => false
        );

        return $this->render('NaturaPassGroupBundle:Default:angular.show.html.twig', $parameters);
    }

    /**
     * @param \NaturaPass\GroupBundle\Entity\Group $group
     * @throws  HttpException
     * @return \Symfony\Component\HttpFoundation\Response
     *
     * @ParamConverter("group", class="NaturaPassGroupBundle:Group")
     */
    public function exitAction($group)
    {
        $manager = $this->getDoctrine()->getManager();
        $repository = $manager->getRepository('NaturaPassGroupBundle:GroupUser');

        $groupUser = $repository->findOneBy(array(
            'user' => $this->getUser(),
            'group' => $group
        ));

        if ($groupUser) {
            $manager->remove($groupUser);
            $manager->flush();
        }

        return new RedirectResponse($this->get('router')->generate('naturapass_group_homepage'));
    }

}
