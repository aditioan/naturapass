<?php

namespace NaturaPass\UserBundle\Controller;

use Api\ApiBundle\Controller\v2\Serialization\NewsSerialization;
use Doctrine\Common\Collections\ArrayCollection;
use NaturaPass\LoungeBundle\Controller\LoungesController;
use NaturaPass\MainBundle\Entity\PostRequest;
use NaturaPass\PublicationBundle\Form\Type\PublicationFormType;
use NaturaPass\UserBundle\Entity\Invitation;
use NaturaPass\LoungeBundle\Entity\Lounge;
use NaturaPass\LoungeBundle\Entity\LoungeInvitation;
use NaturaPass\LoungeBundle\Entity\LoungeUser;
use NaturaPass\GroupBundle\Entity\Group;
use NaturaPass\GroupBundle\Entity\GroupInvitation;
use NaturaPass\UserBundle\Entity\User;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use NaturaPass\GroupBundle\Entity\GroupUser;
use NaturaPass\UserBundle\Entity\UserFriend;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use FOS\UserBundle\Controller\RegistrationController as BaseController;

class RegistrationController extends BaseController
{

    const ID_LOUNGE_GAME_FAIR = 516;
    const ID_LOUNGE_RAMBOUILLET = 571;
    const ID_LOUNGE_LILLE = 653;
    const ID_LOUNGE_FONTAINEBLEAU = 677;
    const ID_VIDEOS_USER = 7929;

    /**
     * Tell the user to check his email provider
     */
    public function checkEmailAction($email = false)
    {
        //$email = $this->container->get('session')->get('fos_user_send_confirmation_email/email');
        //$this->container->get('session')->remove('fos_user_send_confirmation_email/email');

        $user = $this->container->get('fos_user.user_manager')->findUserByEmail($email);

        if (null === $user) {
            throw new NotFoundHttpException(sprintf('The user with email "%s" does not exist', $email));
        }

        return $this->container->get('templating')->renderResponse('FOSUserBundle:Registration:checkEmail.html.' . $this->getEngine(), array(
                'user' => $user,
        ));
    }

    /**
     * Receive the confirmation token from user email provider, login the user
     */
    public function confirmAction($token)
    {
        $user = $this->container->get('fos_user.user_manager')->findUserByConfirmationToken($token);

        if (null === $user) {
            throw new NotFoundHttpException(sprintf('The user with confirmation token "%s" does not exist', $token));
        }

        $user->setConfirmationToken(null);
        $user->setEnabled(true);
        $user->setLastLogin(new \DateTime());

        $this->container->get('fos_user.user_manager')->updateUser($user);
        $response = new RedirectResponse($this->container->get('router')->generate('naturapass_user_invitation', array('create' => 1)));
        $this->authenticateUser($user, $response);

        $this->container->get('session')->set('user.register/just_registered', true);

        return $response;
    }

    public function registerAction()
    {
        // vietlh: check if user has already login => redirect to homepage
        $securityContext = $this->container->get('security.authorization_checker');
        if ($securityContext->isGranted('IS_AUTHENTICATED_FULLY')) {
            $redirectResponse = new RedirectResponse('/');
            return $redirectResponse;
        }
        //end
        $form = $this->container->get('fos_user.registration.form');
        $formHandler = $this->container->get('naturapass_user.form.handler.registration');
        $confirmationEnabled = $this->container->getParameter('fos_user.registration.confirmation.enabled');
        $process = $formHandler->process($confirmationEnabled);
        $user = null;

        if ($process) {
            $user = $form->getData();

            /**             * *************************************************
             * Add new functionality (e.g. log the registration) *
             * *************************************************** */
            $this->container->get('logger')->info(
                sprintf('New user registration: %s', $user)
            );

            if ($this->container->getParameter("application") == "Naturapass") {
                $request = new PostRequest('http://webmarketing.e-conception.fr/inscr/naturapass/register.php');
                $request->setData('email', $user->getEmail())
                    ->setData('nom', $user->getLastname())
                    ->setData('prenom', $user->getFirstname())
                    ->setData('id_groupe', '4011');
                $request->send();
            }
            if (!$confirmationEnabled) {
                $route = 'naturapass_user_invitation';

                $manager = $this->container->get('doctrine')->getManager();
                $invitations = $manager->getRepository('NaturaPassUserBundle:Invitation')->findBy(array(
                    'email' => $user->getEmail(),
                    'state' => Invitation::INVITATION_SENT,
                ));

                foreach ($invitations as $invite) {
                    $userFriend = new UserFriend();
                    $userFriend->setUser($invite->getUser())
                        ->setFriend($user)
                        ->setState(UserFriend::ASKED)
                        ->setType(UserFriend::TYPE_FRIEND);

                    $invite->setState(Invitation::INVITATION_INSCRIPTION_SUCCESS);

                    $manager->persist($userFriend);
                    $manager->persist($invite);

                    $this->container->get('naturapass.notification')->generate(
                        'user.friendship.asked', $invite->getUser(), array($user), array(), array('usertag' => $user->getUsertag()));
                }

                $loungeInvitations = $manager->getRepository('NaturaPassLoungeBundle:LoungeInvitation')->findBy(array(
                    'email' => $user->getEmail(),
                    'state' => Invitation::INVITATION_SENT,
                ));
                foreach ($loungeInvitations as $loungeInvitation) {
                    $loungeUser = new LoungeUser();
                    $loungeUser->setAccess(LoungeUser::ACCESS_INVITED)
                        ->setUser($user)
                        ->setLounge($loungeInvitation->getLounge());

                    $loungeInvitation->setState(LoungeInvitation::INVITATION_INSCRIPTION_SUCCESS);

                    $manager->persist($loungeUser);
                    $manager->persist($loungeInvitation);

                    $this->container->get('naturapass.notification')->generate(
                        'lounge.join.invited', $loungeInvitation->getUser(), array($user), array('lounge' => $loungeInvitation->getLounge()->getName()), array('loungetag' => $loungeInvitation->getLounge()->getLoungetag(), $loungeInvitation->getLounge()->getId())
                    );
                }

                $groupInvitations = $manager->getRepository('NaturaPassGroupBundle:GroupInvitation')->findBy(array(
                    'email' => $user->getEmail(),
                    'state' => Invitation::INVITATION_SENT,
                ));
                foreach ($groupInvitations as $groupInvitation) {
                    $groupUser = new GroupUser();
                    $groupUser->setAccess(GroupUser::ACCESS_INVITED)
                        ->setUser($user)
                        ->setGroup($groupInvitation->getGroup());

                    $groupInvitation->setState(GroupInvitation::INVITATION_INSCRIPTION_SUCCESS);

                    $manager->persist($groupUser);
                    $manager->persist($groupInvitation);

                    $this->container->get('naturapass.notification')->generate(
                        'group.join.invited', $groupInvitation->getUser(), array($user), array('group' => $groupInvitation->getGroup()->getName()), array('grouptag' => $groupInvitation->getGroup()->getGrouptag(), $groupInvitation->getGroup()->getId())
                    );
                }
                $manager->flush();
                $this->container->get('session')->set('user.register/notConfirmation', true);

                if ($this->container->get('session')->get('user.register/RegisterGameFair')) {
                    $route = 'naturapass_lounge_show';

                    $doctrine = $this->container->get('doctrine');
                    $manager = $doctrine->getManager();
                    $lounge = $manager->getRepository("NaturaPassLoungeBundle:Lounge")->findOneBy(array("id" => RegistrationController::ID_LOUNGE_GAME_FAIR));
                    if (!empty($lounge)) {
                        $loungeUser = $manager->getRepository("NaturaPassLoungeBundle:LoungeUser")->findOneBy(array("user" => $user, "lounge" => $lounge));
                        if (empty($loungeUser)) {
                            $loungeUser = new LoungeUser();
                            $loungeUser->setAccess(LoungeUser::ACCESS_DEFAULT);
                            $loungeUser->setLounge($lounge);
                            $loungeUser->setUser($user);
                        }
                        $loungeUser->setParticipation(LoungeUser::PARTICIPATION_YES);
                        $manager->persist($loungeUser);
                        $url = $this->container->get('router')->generate($route, array('loungetag' => $lounge->getLoungetag()), true);
                        $redirectResponse = new RedirectResponse($url);
                    } else {
                        $url = $this->container->get('router')->generate($route, array('create' => 1));
                        $redirectResponse = new RedirectResponse($url);
                    }
                } else {
                    $url = $this->container->get('router')->generate($route, array('create' => 1));
                    $redirectResponse = new RedirectResponse($url);
                }

                $this->authenticateUser($user, $redirectResponse);
            } else if ($confirmationEnabled) {
                $message = \Swift_Message::newInstance()
                    ->setContentType("text/html")
                    ->setSubject($this->container->get('translator')->trans('user.register_confirmation.subject', array(), $this->container->getParameter("translation_name") . 'email'))
                    ->setFrom($this->container->get('translator')->trans('user.register_confirmation.from', array(), $this->container->getParameter("translation_name") . 'email'))
                    ->addBcc('suivi@naturapass.com')
                    ->setTo($user->getEmail())
                    ->setBody($this->container->get('templating')->render('NaturaPassEmailBundle:User:registration.html.twig', array(
                        'fullname' => $user->getFullName(),
                        'link' => $user->getConfirmationToken()
                    )));

                $this->container->get('mailer')->send($message);
                $route = 'fos_user_registration_check_email';

                $this->container->get('session')->set('fos_user_send_confirmation_email/email', $user->getEmail());

                return $this->checkEmailAction($user->getEmail());

                //$url = $this->container->get('router')->generate($route);
                //$redirectResponse = new RedirectResponse($url);
            } else {

            }

            $this->container->get('session')->set('user.register/just_registered', true);
            $this->setFlash('fos_user_success', 'registration.flash.user_created');
            return $redirectResponse;
        }

        $slides = $this->container->get('doctrine')->getRepository('AdminNewsBundle:Slide')->createQueryBuilder('s')
            ->where('s.active = 1')
            ->orderBy('s.sort', 'ASC')
            ->getQuery()
            ->getResult();


        $news = $this->container->get('doctrine')->getRepository('AdminNewsBundle:News')->createQueryBuilder('n')
            ->setFirstResult(0)
            ->setMaxResults(10)
            ->where('n.active = 1')
            ->orderBy('n.date', 'DESC')
            ->getQuery()
            ->getResult();

        $games = $this->container->get('doctrine')->getRepository('AdminGameBundle:Game')->createQueryBuilder('g')
            ->setFirstResult(0)
            ->setMaxResults(10)
            // ->where('g.debut > :endDate')
            ->where('g.fin >= :endDate')
            ->setParameter('endDate', new \DateTime())
            ->orderBy('g.created', 'DESC')
            ->getQuery()
            ->getResult();

        $allGamesOpen = new ArrayCollection();

        foreach ($games as $game) {
            $mGame = array(
                'id' => $game->getId(),
                'title' => $game->getTitle(),
                'titleFormat' => $game->getTitleFormat(),
                'color' => $game->getColor(),
                'debut' => $game->getDebut()->format("d/m/Y"),
                'fin' => $game->getFin()->format("d/m/Y"),
                'debutFormat' => $this->getTranslator()->trans(
                    'concours.date.start', array('%date%' => $game->getDebut()->format("d/m/Y")), 'main'
                ),
                'finFormat' => $this->getTranslator()->trans(
                    'concours.date.start', array('%date%' => $game->getFin()->format("d/m/Y")), 'main'
                ),
                'type' => $game->getType(),
                'created' => $game->getCreated()->format(\DateTime::ATOM),
                'updated' => $game->getUpdated()->format(\DateTime::ATOM),
                'visuel' => $game->getVisuel(),
                'resultat' => $game->getResultat(),
            );
            $allGamesOpen->add($mGame);
        }

        $channel_id = "UC0o6HzkTPsTfxk8aSBOW1RA";
        $playlist_id = "PLGPkJV7kgiYfBWhKJuZ28cn9fl81RHJ6L";
        $maxResults = 25;
        $key = "AIzaSyAEP9rIn9ruUx-FyuGzUeA3Pcqx9330wKU";
        $url_channel = "https://www.googleapis.com/youtube/v3/search?type=video&order=date&part=snippet&channelId=" . $channel_id . "&maxResults=" . $maxResults . "&key=" . $key;
        $url_playlist = "https://www.googleapis.com/youtube/v3/playlistItems?order=date&part=snippet&playlistId=" . $playlist_id . "&maxResults=" . $maxResults . "&key=" . $key;
        $data_channel = json_decode(file_get_contents($url_channel), true);
        $data_playlist = json_decode(file_get_contents($url_playlist), true);

        return $this->container->get('templating')->renderResponse('@NaturaPassUser/Default/home.html.twig', array(
            'channel'  => $data_channel,
            'playlist' => $data_playlist,
            'games' => $allGamesOpen,
            'news' => NewsSerialization::serializeNews($news),
            'form' => $form->createView(),
            'email' => ($this->container->get('request')->query->has('email')) ? $this->container->get('request')->query->get('email') : false
        ));
    }

    public function registerGameFairAction()
    {
        $this->container->get('session')->set('user.register/RegisterGameFair', true);
        $form = $this->container->get('fos_user.registration.form');

        return $this->container->get('templating')->renderResponse('NaturaPassUserBundle:Default:inscription-game-fair.html.twig', array(
                'form'  => $form->createView(),
                'email' => ($this->container->get('request')->query->has('email')) ? $this->container->get('request')->query->get('email') : false
        ));
    }

    public function registerConcoursAction($game, $connect)
    {
        if ($connect == 0) {
            $form = $this->container->get('fos_user.registration.form');
            $formHandler = $this->container->get('naturapass_user.form.handler.registration');
            $confirmationEnabled = $this->container->getParameter('fos_user.registration.confirmation.enabled');
            $process = $formHandler->process($confirmationEnabled);

            if ($process) {
                $user = $form->getData();

                /**                 * *************************************************
                 * Add new functionality (e.g. log the registration) *
                 * *************************************************** */
                $this->container->get('logger')->info(
                        sprintf('New user registration: %s', $user)
                );

                if ($this->container->getParameter("application") == "Naturapass") {
                    $request = new PostRequest('http://webmarketing.e-conception.fr/inscr/naturapass/register.php');
                    $request->setData('email', $user->getEmail())
                            ->setData('nom', $user->getLastname())
                            ->setData('prenom', $user->getFirstname())
                            ->setData('id_groupe', '4011');
                    $request->send();
                }
                if (!$confirmationEnabled) {
                    $route = 'naturapass_user_invitation';

                    $manager = $this->container->get('doctrine')->getManager();
                    $invitations = $manager->getRepository('NaturaPassUserBundle:Invitation')->findBy(array(
                            'email' => $user->getEmail(),
                            'state' => Invitation::INVITATION_SENT,
                    ));

                    foreach ($invitations as $invite) {
                        $userFriend = new UserFriend();
                        $userFriend->setUser($invite->getUser())
                                ->setFriend($user)
                                ->setState(UserFriend::ASKED)
                                ->setType(UserFriend::TYPE_FRIEND);

                        $invite->setState(Invitation::INVITATION_INSCRIPTION_SUCCESS);

                        $manager->persist($userFriend);
                        $manager->persist($invite);

                        $this->container->get('naturapass.notification')->generate(
                                'user.friendship.asked', $invite->getUser(), array($user), array(), array('usertag' => $user->getUsertag()));
                    }

                    $loungeInvitations = $manager->getRepository('NaturaPassLoungeBundle:LoungeInvitation')->findBy(array(
                            'email' => $user->getEmail(),
                            'state' => Invitation::INVITATION_SENT,
                    ));
                    foreach ($loungeInvitations as $loungeInvitation) {
                        $loungeUser = new LoungeUser();
                        $loungeUser->setAccess(LoungeUser::ACCESS_INVITED)
                                ->setUser($user)
                                ->setLounge($loungeInvitation->getLounge());

                        $loungeInvitation->setState(LoungeInvitation::INVITATION_INSCRIPTION_SUCCESS);

                        $manager->persist($loungeUser);
                        $manager->persist($loungeInvitation);

                        $this->container->get('naturapass.notification')->generate(
                                'lounge.join.invited', $loungeInvitation->getUser(), array($user), array('lounge' => $loungeInvitation->getLounge()->getName()), array('loungetag' => $loungeInvitation->getLounge()->getLoungetag(), $loungeInvitation->getLounge()->getId())
                        );
                    }

                    $groupInvitations = $manager->getRepository('NaturaPassGroupBundle:GroupInvitation')->findBy(array(
                            'email' => $user->getEmail(),
                            'state' => Invitation::INVITATION_SENT,
                    ));
                    foreach ($groupInvitations as $groupInvitation) {
                        $groupUser = new GroupUser();
                        $groupUser->setAccess(GroupUser::ACCESS_INVITED)
                                ->setUser($user)
                                ->setGroup($groupInvitation->getGroup());

                        $groupInvitation->setState(GroupInvitation::INVITATION_INSCRIPTION_SUCCESS);

                        $manager->persist($groupUser);
                        $manager->persist($groupInvitation);

                        $this->container->get('naturapass.notification')->generate(
                                'group.join.invited', $groupInvitation->getUser(), array($user), array('group' => $groupInvitation->getGroup()->getName()), array('grouptag' => $groupInvitation->getGroup()->getGrouptag(), $groupInvitation->getGroup()->getId())
                        );
                    }

                    $manager->flush();
                    $this->container->get('session')->set('user.register/notConfirmation', true);
                    $url = $this->container->get('router')->generate($route, array('create' => 1));
                    $redirectResponse = new RedirectResponse($url);

                    $this->authenticateUser($user, $redirectResponse);
                } else if ($confirmationEnabled) {
                    $message = \Swift_Message::newInstance()
                            ->setContentType("text/html")
                            ->setSubject($this->container->get('translator')->trans('user.register_confirmation.subject', array(), $this->container->getParameter("translation_name") . 'email'))
                            ->setFrom($this->container->get('translator')->trans('user.register_confirmation.from', array(), $this->container->getParameter("translation_name") . 'email'))
                            ->addBcc('suivi@naturapass.com')
                            ->setTo($user->getEmail())
                            ->setBody($this->container->get('templating')->render('NaturaPassEmailBundle:User:registration.html.twig', array(
                                    'fullname' => $user->getFullName(),
                                    'link'     => $user->getConfirmationToken()
                            )));

                    $this->container->get('mailer')->send($message);
                    $route = 'fos_user_registration_check_email';

                    $this->container->get('session')->set('fos_user_send_confirmation_email/email', $user->getEmail());

                    return $this->checkEmailAction($user->getEmail());

                    //$url = $this->container->get('router')->generate($route);
                    //$redirectResponse = new RedirectResponse($url);
                } else {

                }

                $this->container->get('session')->set('user.register/just_registered', true);
                $this->setFlash('fos_user_success', 'registration.flash.user_created');
                return $redirectResponse;
            }
        }

        //        return $this->container->get('templating')->renderResponse('NaturaPassUserBundle:Default:inscription.html.' . $this->getEngine(), array(
        //                    'form' => $form->createView(),
        //                    'email' => ($this->container->get('request')->query->has('email')) ? $this->container->get('request')->query->get('email') : false,
        //                    'news' => $news,
        //                    'slides' => $slides
        //        ));
        if ($connect == 1) {
            return $this->container->get('templating')->renderResponse('NaturaPassMainBundle:Default:concours-detail.html.twig', array(
                    'game'    => $game,
                    'connect' => $connect,
            ));
        } else {
            return $this->container->get('templating')->renderResponse('NaturaPassMainBundle:Default:concours-detail.html.twig', array(
                    'form'    => $form->createView(),
                    'game'    => $game,
                    'connect' => $connect,
                    'email'   => ($this->container->get('request')->query->has('email')) ? $this->container->get('request')->query->get('email') : false
            ));
        }
    }

    /**
     * @param \Admin\GameBundle\Entity\Game $game
     *
     * @return \Symfony\Component\HttpFoundation\Response
     *
     * @ParamConverter("game", class="AdminGameBundle:Game")
     */
    public function registerChallengeAction($game, $connect)
    {
        if ($connect == 0) {
            $form = $this->container->get('fos_user.registration.form');
            $formHandler = $this->container->get('naturapass_user.form.handler.registration');
            $confirmationEnabled = $this->container->getParameter('fos_user.registration.confirmation.enabled');
            $process = $formHandler->process($confirmationEnabled);

            if ($process) {
                $user = $form->getData();

                /**                 * *************************************************
                 * Add new functionality (e.g. log the registration) *
                 * *************************************************** */
                $this->container->get('logger')->info(
                        sprintf('New user registration: %s', $user)
                );

                if ($this->container->getParameter("application") == "Naturapass") {
                    $request = new PostRequest('http://webmarketing.e-conception.fr/inscr/naturapass/register.php');
                    $request->setData('email', $user->getEmail())
                            ->setData('nom', $user->getLastname())
                            ->setData('prenom', $user->getFirstname())
                            ->setData('id_groupe', '4011');
                    $request->send();
                }
                if (!$confirmationEnabled) {
                    $route = 'naturapass_concours_detail';

                    $manager = $this->container->get('doctrine')->getManager();
                    $invitations = $manager->getRepository('NaturaPassUserBundle:Invitation')->findBy(array(
                            'email' => $user->getEmail(),
                            'state' => Invitation::INVITATION_SENT,
                    ));

                    foreach ($invitations as $invite) {
                        $userFriend = new UserFriend();
                        $userFriend->setUser($invite->getUser())
                                ->setFriend($user)
                                ->setState(UserFriend::ASKED)
                                ->setType(UserFriend::TYPE_FRIEND);

                        $invite->setState(Invitation::INVITATION_INSCRIPTION_SUCCESS);

                        $manager->persist($userFriend);
                        $manager->persist($invite);

                        $this->container->get('naturapass.notification')->generate(
                                'user.friendship.asked', $invite->getUser(), array($user), array(), array('usertag' => $user->getUsertag()));
                    }

                    $loungeInvitations = $manager->getRepository('NaturaPassLoungeBundle:LoungeInvitation')->findBy(array(
                            'email' => $user->getEmail(),
                            'state' => Invitation::INVITATION_SENT,
                    ));
                    foreach ($loungeInvitations as $loungeInvitation) {
                        $loungeUser = new LoungeUser();
                        $loungeUser->setAccess(LoungeUser::ACCESS_INVITED)
                                ->setUser($user)
                                ->setLounge($loungeInvitation->getLounge());

                        $loungeInvitation->setState(LoungeInvitation::INVITATION_INSCRIPTION_SUCCESS);

                        $manager->persist($loungeUser);
                        $manager->persist($loungeInvitation);

                        $this->container->get('naturapass.notification')->generate(
                                'lounge.join.invited', $loungeInvitation->getUser(), array($user), array('lounge' => $loungeInvitation->getLounge()->getName()), array('loungetag' => $loungeInvitation->getLounge()->getLoungetag(), $loungeInvitation->getLounge()->getId())
                        );
                    }

                    $groupInvitations = $manager->getRepository('NaturaPassGroupBundle:GroupInvitation')->findBy(array(
                            'email' => $user->getEmail(),
                            'state' => Invitation::INVITATION_SENT,
                    ));
                    foreach ($groupInvitations as $groupInvitation) {
                        $groupUser = new GroupUser();
                        $groupUser->setAccess(GroupUser::ACCESS_INVITED)
                                ->setUser($user)
                                ->setGroup($groupInvitation->getGroup());

                        $groupInvitation->setState(GroupInvitation::INVITATION_INSCRIPTION_SUCCESS);

                        $manager->persist($groupUser);
                        $manager->persist($groupInvitation);

                        $this->container->get('naturapass.notification')->generate(
                                'group.join.invited', $groupInvitation->getUser(), array($user), array('group' => $groupInvitation->getGroup()->getName()), array('grouptag' => $groupInvitation->getGroup()->getGrouptag(), $groupInvitation->getGroup()->getId())
                        );
                    }

                    $manager->flush();
                    $this->container->get('session')->set('user.register/notConfirmation', true);

                    $url = $this->container->get('router')->generate($route, array('game' => $game->getId(), 'name' => $game->getTitleFormat()));
                    $redirectResponse = new RedirectResponse($url);

                    $this->authenticateUser($user, $redirectResponse);
                } else if ($confirmationEnabled) {
                    $message = \Swift_Message::newInstance()
                            ->setContentType("text/html")
                            ->setSubject($this->container->get('translator')->trans('user.register_confirmation.subject', array(), $this->container->getParameter("translation_name") . 'email'))
                            ->setFrom($this->container->get('translator')->trans('user.register_confirmation.from', array(), $this->container->getParameter("translation_name") . 'email'))
                            ->addBcc('suivi@naturapass.com')
                            ->setTo($user->getEmail())
                            ->setBody($this->container->get('templating')->render('NaturaPassEmailBundle:User:registration.html.twig', array(
                                    'fullname' => $user->getFullName(),
                                    'link'     => $user->getConfirmationToken()
                            )));

                    $this->container->get('mailer')->send($message);
                    $route = 'fos_user_registration_check_email';

                    $this->container->get('session')->set('fos_user_send_confirmation_email/email', $user->getEmail());

                    return $this->checkEmailAction($user->getEmail());

                    //$url = $this->container->get('router')->generate($route);
                    //$redirectResponse = new RedirectResponse($url);
                } else {

                }

                $this->container->get('session')->set('user.register/just_registered', true);
                $this->setFlash('fos_user_success', 'registration.flash.user_created');
                return $redirectResponse;
            }
        }

        //        return $this->container->get('templating')->renderResponse('NaturaPassUserBundle:Default:inscription.html.' . $this->getEngine(), array(
        //                    'form' => $form->createView(),
        //                    'email' => ($this->container->get('request')->query->has('email')) ? $this->container->get('request')->query->get('email') : false,
        //                    'news' => $news,
        //                    'slides' => $slides
        //        ));
        if ($connect == 1) {
            return $this->container->get('templating')->renderResponse('NaturaPassMainBundle:Default:concours-detail.html.twig', array(
                    'game'    => $game,
                    'connect' => $connect,
            ));
        } else {
            return $this->container->get('templating')->renderResponse('NaturaPassMainBundle:Default:challenge-inscription.html.twig', array(
                    'form'    => $form->createView(),
                    'game'    => $game,
                    'connect' => $connect,
                    'email'   => ($this->container->get('request')->query->has('email')) ? $this->container->get('request')->query->get('email') : false
            ));
        }
    }

    /**
     * @return \Symfony\Component\HttpFoundation\Response
     *
     */
    public function videosAction()
    {
        $user = $this->container->get('security.context')->getToken()->getUser();
        if ($user instanceof User) {
            $doctrine = $this->container->get('doctrine');
            $controller = new ProfileController();
            $controller->setContainer($this->container);
            $manager = $doctrine->getManager();
            return $controller->showAction($manager->getRepository("NaturaPassUserBundle:User")->findOneBy(array("id" => RegistrationController::ID_VIDEOS_USER)));
        } else {
            $this->container->get('session')->set('user.register/byVideos', true);
            return $this->container->get('templating')->renderResponse('NaturaPassUserBundle:Default:videos.html.twig', array());
        }
    }

    /**
     * @return \Symfony\Component\HttpFoundation\Response
     *
     */
    public function registerFacebookGameAction()
    {
        $user = $this->container->get('security.context')->getToken()->getUser();
        if ($user instanceof User) {
            $doctrine = $this->container->get('doctrine');
            $manager = $doctrine->getManager();
            $lounge = $manager->getRepository("NaturaPassLoungeBundle:Lounge")->findOneBy(array("id" => RegistrationController::ID_LOUNGE_FONTAINEBLEAU));
            if (!empty($lounge)) {
                $loungeUser = $manager->getRepository("NaturaPassLoungeBundle:LoungeUser")->findOneBy(array("user" => $user, "lounge" => $lounge));
                if (empty($loungeUser)) {
                    $loungeUser = new LoungeUser();
                    $loungeUser->setAccess(LoungeUser::ACCESS_DEFAULT);
                    $loungeUser->setLounge($lounge);
                    $loungeUser->setUser($user);
                }
                $loungeUser->setParticipation(LoungeUser::PARTICIPATION_YES);
                $manager->persist($loungeUser);
            }
            return $this->container->get('templating')->renderResponse('NaturaPassUserBundle:Default:facebook-valid.html.twig', array());
        } else {
            $this->container->get('session')->set('user.register/byFacebookGame', true);
            return $this->container->get('templating')->renderResponse('NaturaPassUserBundle:Default:facebook-game.html.twig', array());
        }
    }

    public function registerFacebookGameValidAction()
    {
        $user = $this->container->get('security.context')->getToken()->getUser();
        if ($user instanceof User) {
            $doctrine = $this->container->get('doctrine');
            $manager = $doctrine->getManager();
            $lounge = $manager->getRepository("NaturaPassLoungeBundle:Lounge")->findOneBy(array("id" => RegistrationController::ID_LOUNGE_FONTAINEBLEAU));
            //            $lounge = $manager->getRepository("NaturaPassLoungeBundle:Lounge")->findOneBy(array("id" => 384));
            if (!empty($lounge)) {
                $loungeUser = $manager->getRepository("NaturaPassLoungeBundle:LoungeUser")->findOneBy(array("user" => $user, "lounge" => $lounge));
                if (empty($loungeUser)) {
                    $loungeUser = new LoungeUser();
                    $loungeUser->setAccess(LoungeUser::ACCESS_DEFAULT);
                    $loungeUser->setLounge($lounge);
                    $loungeUser->setUser($user);
                }
                $loungeUser->setParticipation(LoungeUser::PARTICIPATION_YES);
                $manager->persist($loungeUser);
            }
            return $this->container->get('templating')->renderResponse('NaturaPassUserBundle:Default:facebook-valid.html.twig', array());
        } else {
            $this->container->get('session')->set('user.register/byFacebookGame', true);
            return $this->container->get('templating')->renderResponse('NaturaPassUserBundle:Default:facebook-game.html.twig', array());
        }
    }

    public function registerFacebookGameFairAction()
    {
        $user = $this->container->get('security.context')->getToken()->getUser();
        if ($user instanceof User) {
            $doctrine = $this->container->get('doctrine');
            $manager = $doctrine->getManager();
            $lounge = $manager->getRepository("NaturaPassLoungeBundle:Lounge")->findOneBy(array("id" => RegistrationController::ID_LOUNGE_GAME_FAIR));
            //            $lounge = $manager->getRepository("NaturaPassLoungeBundle:Lounge")->findOneBy(array("id" => 384));
            if (!empty($lounge)) {
                $loungeUser = $manager->getRepository("NaturaPassLoungeBundle:LoungeUser")->findOneBy(array("user" => $user, "lounge" => $lounge));
                if (empty($loungeUser)) {
                    $loungeUser = new LoungeUser();
                    $loungeUser->setAccess(LoungeUser::ACCESS_DEFAULT);
                    $loungeUser->setLounge($lounge);
                    $loungeUser->setUser($user);
                }
                $loungeUser->setParticipation(LoungeUser::PARTICIPATION_YES);
                $manager->persist($loungeUser);
            }

            $controller = new LoungesController();
            $controller->setContainer($this->container);
            return $controller->showAction($lounge);
        } else {
            $this->container->get('session')->set('user.register/connectGameFair', true);
            return $this->container->get('templating')->renderResponse('NaturaPassUserBundle:Default:facebook-game-fair.html.twig', array());
        }
    }

}
