<?php

namespace NaturaPass\UserBundle\Controller;

use NaturaPass\UserBundle\Entity\UserFriend;
use NaturaPass\UserBundle\Form\Type\RegistrationFormType;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use NaturaPass\NotificationBundle\Entity\User\UserFriendshipAskedNotification;
use Symfony\Component\HttpFoundation\Request;
use NaturaPass\UserBundle\Entity\Invitation;
use NaturaPass\UserBundle\Entity\InvitationTask;
use NaturaPass\UserBundle\Form\Type\InvitationTaskType;
use NaturaPass\UserBundle\Form\Handler\InvitationFormHandler;

class DefaultController extends Controller
{

    /**
     * This method is only here to check the permissions for the firewall
     * Don't delete - it's supposed to be empty
     */
    public function loginCheckAction()
    {

    }

    public function clearAction()
    {
        if ($this->getUser()->hasRole('ROLE_ADMIN')) {
            $manager = $this->getDoctrine()->getManager();
            $userRepo = $manager->getRepository('NaturaPassUserBundle:User');

            for ($i = 1; $i <= 3; $i++) {
                $user = $userRepo->findOneByEmail('test' . $i . '@naturapass.com');

                if (is_object($user)) {
                    $entities = array();

                    $entities = array_merge($entities, $manager->getRepository('NaturaPassMessageBundle:Message')->findByOwner($user));

                    $entities = array_merge($entities, $manager->getRepository('NaturaPassUserBundle:UserFriend')->findByUser($user));

                    $entities = array_merge($entities, $manager->getRepository('NaturaPassUserBundle:Invitation')->findByUser($user));

                    $entities = array_merge($entities, $manager->getRepository('NaturaPassUserBundle:UserMedia')->findByOwner($user));

                    $entities = array_merge($entities, $manager->getRepository('NaturaPassUserBundle:UserFriend')->findByFriend($user));

                    $entities = array_merge($entities, $manager->getRepository('NaturaPassPublicationBundle:Publication')->findByOwner($user));

                    $entities = array_merge($entities, $manager->getRepository('NaturaPassPublicationBundle:PublicationComment')->findByOwner($user));

                    $entities = array_merge($entities, $manager->getRepository('NaturaPassPublicationBundle:PublicationCommentAction')->findByUser($user));

                    $entities = array_merge($entities, $manager->getRepository('NaturaPassPublicationBundle:PublicationAction')->findByUser($user));

                    $entities = array_merge($entities, $manager->getRepository('NaturaPassNotificationBundle:Notification')->findBySender($user));

                    $entities = array_merge($entities, $manager->getRepository('NaturaPassNotificationBundle:NotificationReceiver')->findByReceiver($user));

                    $entities = array_merge($entities, $manager->getRepository('NaturaPassLoungeBundle:Lounge')->findByOwner($user));

                    $entities = array_merge($entities, $manager->getRepository('NaturaPassLoungeBundle:LoungeUser')->findByUser($user));

                    $entities = array_merge($entities, $manager->getRepository('NaturaPassGroupBundle:Group')->findByOwner($user));

                    $entities = array_merge($entities, $manager->getRepository('NaturaPassGroupBundle:GroupUser')->findByUser($user));

                    $entities = array_merge($entities, $manager->getRepository('NaturaPassGraphBundle:Edge')->findByFrom($user));

                    $entities = array_merge($entities, $manager->getRepository('NaturaPassGraphBundle:Edge')->findByTo($user));

                    foreach ($entities as $entity) {
                        $manager->remove($entity);
                    }

                    $manager->remove($user);

                    $manager->flush();
                }
            }
        }
    }

    public function invitationAction(Request $request)
    {
        $form = $this->createForm(new InvitationTaskType(), new InvitationTask());
        $formHandler = new InvitationFormHandler($form, $request, $this->get('mailer'));
        // $form = $this->container->get('naturapass_user.invitation.form');
        // $formHandler = $this->container->get('naturapass_user.form.handler.invitation');
        $thisUser = $this->getUser();
        $userEmail = $thisUser->getEmail();
        $manager = $this->getDoctrine()->getManager();
        if ($this->get('session')->has('user.register/notConfirmation')) {
            $this->container->get('session')->set('user.register/notConfirmation', false);
            $message = \Swift_Message::newInstance()
                ->setContentType("text/html")
                ->setSubject($this->container->get('translator')->trans('user.register_without_confirmation.subject', array(), $this->container->getParameter("translation_name").'email'))
                ->setFrom($this->container->get('translator')->trans('user.register_without_confirmation.from', array(), $this->container->getParameter("translation_name").'email'))
                ->addBcc('suivi@naturapass.com')
                ->setTo($this->getUser()->getEmail())
                ->setBody($this->container->get('templating')->render('NaturaPassEmailBundle:User:register_api.html.twig', array(
                    'fullname' => $this->getUser()->getFullName()
                )));

            $this->container->get('mailer')->send($message);
            $this->getUser()->setEnabled(true);
            $manager->persist($this->getUser());
            $manager->flush();
        }

        $registration = true;

        if (!$this->get('session')->has('user.register/just_registered')) {
            $this->get('session')->set('user.invitation/invitations', true);
            $registration = false;
        }

        $process = $formHandler->process($userEmail);
        if ($process) {
            $repo = $manager->getRepository('NaturaPassUserBundle:User');

            $hostUser = $repo->findOneBy(array(
                'email' => $userEmail
            ));
            $manager->persist($hostUser);
            $manager->flush();
            // traitement contact
            $data = $form->getData();

            foreach ($data->getInvitations()->getValues() as $v) {
                $email = $v->getEmail();

                $user = $repo->findOneBy(array(
                    'email' => $email
                ));
                // l'utilisateur ne peut pas s'auto-envoyer une notification ou un mail, non il ne peut pas le renard
                if ($email != $userEmail) {
                    if ($user) {
                        // si le contact est deja un utitlisateur
                        // envoi d'une notifiactin a l'utilisateur
                        $repoFriend = $manager->getRepository('NaturaPassUserBundle:UserFriend');

                        $userFriend = $repoFriend->findOneBy(array(
                            'user' => $thisUser,
                            'friend' => $user
                        ));

                        if (is_null($userFriend) || !$userFriend) {

                            $userFriend = new UserFriend();
                            $userFriend->setUser($thisUser)
                                ->setFriend($user)
                                ->setState(UserFriend::ASKED)
                                ->setType(UserFriend::TYPE_FRIEND);

                            $manager->persist($userFriend);
                            $manager->flush();

                            $this->get('naturapass.notification')->queue(
                                new UserFriendshipAskedNotification($user), $user
                            );

                            $message = \Swift_Message::newInstance()
                                ->setContentType("text/html")
                                ->setSubject($this->get('translator')->trans('invitation.friend.subject', array('%fullname%' => $hostUser->getFullName()), 'email'))
                                ->setFrom($this->get('translator')->trans('invitation.friend.from', array(), 'email'))
                                // ->setFrom($hostUser->getEmail())
                                ->setTo($email)
                                ->addBcc('suivi@naturapass.com')
                                ->setBody($this->renderView('NaturaPassEmailBundle:User:friend-email.html.twig', array(
                                    'user_fullname' => $user->getFullName(),
                                    'fullname' => $hostUser->getFullName(),
                                    'user_tag' => $thisUser->getUserTag()
                                )));
                            $this->get('mailer')->send($message);
                        }
                    } else {
                        // creation et sauvegarde entite Invitation
                        $repository = $manager->getRepository('NaturaPassUserBundle:Invitation');


                        $invitation = $repository->findOneBy(array(
                            'email' => $email,
                            'user' => $thisUser
                        ));
                        if (!$invitation) {
                            $invitation = new Invitation();
                        }
                        $invitation->setEmail($email);
                        $invitation->setUser($thisUser);
                        $manager->persist($invitation);
                        $manager->flush();
                        //envoi du mail
                        $message = \Swift_Message::newInstance()
                            ->setContentType("text/html")
                            ->setSubject($this->get('translator')->trans('invitation.new.subject', array('%fullname%' => $hostUser->getFullName()), 'email'))
                            ->setFrom($this->get('translator')->trans('invitation.new.from', array(), 'email'))
                            ->setTo($email)
                            ->addBcc('suivi@naturapass.com')
                            ->setBody($this->renderView('NaturaPassEmailBundle:User:invitation-email.html.twig', array(
                                'user_fullname' => $hostUser->getFullName(),
                                'email' => $email
                            )));
                        $this->get('mailer')->send($message);
                    }
                }
            }

            // FIN traitement contacts
            // redirection pas complete
            if ($this->get('session')->has('user.register/just_registered')) {
                $this->get('session')->remove('user.register/just_registered');
                $url = $this->container->get('router')->generate('naturapass_user_complement');
            } else {
                $url = $this->container->get('router')->generate('naturapass_main_homepage');
            }

            return new RedirectResponse($url);
        }


        return $this->container->get('templating')->renderResponse('NaturaPassUserBundle:Default:invitation.html.twig', array(
            'form' => $form->createView(),
            'registration' => $registration
        ));
    }

    public function completeAction(Request $request)
    {
        $form = $this->createForm(new RegistrationFormType($this->container->get('security.token_storage')), $this->getUser());
        $handler = new \Api\ApiBundle\Form\Handler\RegistrationFormHandler($form, $request, $this->getDoctrine()->getManager());

        if ($user = $handler->process(false)) {
            return new RedirectResponse($this->container->get('router')->generate('naturapass_main_homepage'));
        }

        return $this->render('NaturaPassUserBundle:Default:complete.html.twig', array(
            'form' => $form->createView()
        ));
    }

    public function searchAction()
    {
        return $this->render('NaturaPassUserBundle:Default:search.html.twig');
    }

    public function friendsAction()
    {
        return $this->render('NaturaPassUserBundle:Default:friends.html.twig');
    }

}
