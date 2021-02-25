<?php
/**
 * Created by PhpStorm.
 * User: vincentvalot
 * Date: 07/07/14
 * Time: 18:29
 */

namespace NaturaPass\MainBundle\EventListener;

use NaturaPass\LoungeBundle\Entity\LoungeUser;
use NaturaPass\UserBundle\Controller\RegistrationController;
use NaturaPass\UserBundle\Entity\User;
use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\HttpKernel;

class KernelListener
{

    protected $container;

    public function __construct(Container $container)
    {
        $this->container = $container;
    }

    public function onKernelRequest(GetResponseEvent $event)
    {
        $token = $this->container->get('security.token_storage')->getToken();
        $sessionVideos = $this->container->get('session')->get('user.register/byVideos');
        $sessionRambouillet = $this->container->get('session')->get('user.register/byFacebookGame');
        $sessionGameFair = $this->container->get('session')->get('user.register/connectGameFair');
        $sessionGameFair2 = $this->container->get('session')->get('user.register/RegisterGameFair');

        if (HttpKernel::MASTER_REQUEST === $event->getRequestType()) {
            if (is_object($token)) {
                $user = $token->getUser();
                $check = false;
                $userToken = NULL;
                if (is_object($user)) {
                    $userToken = $user->getConfirmationToken();
                }
                if ($userToken) {
                    $this->container->get('session')->set('user.register/just_registered', true);
                }
                if (($sessionGameFair || $sessionGameFair2) && $user instanceof User) {
                    $check = true;
                    $doctrine = $this->container->get('doctrine');
                    $manager = $doctrine->getManager();
                    $lounge = $manager->getRepository("NaturaPassLoungeBundle:Lounge")->findOneBy(array("id" => RegistrationController::ID_LOUNGE_GAME_FAIR));
                    $this->container->get('session')->remove('user.register/connectGameFair');
                    $this->container->get('session')->remove('user.register/RegisterGameFair');
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
                    $event->setResponse(
                        new RedirectResponse($this->container->get('router')->generate(
                            'naturapass_lounge_show', array("loungetag" => $lounge->getLoungetag()), true
                        ))
                    );
                } else if ($sessionRambouillet && $user instanceof User) {
                    $check = true;
                    $this->container->get('session')->remove('user.register/byFacebookGame');
                    $event->setResponse(
                        new RedirectResponse($this->container->get('router')->generate(
                            'naturapass_user_facebook_valid'
                        ))
                    );
                } else if ($sessionVideos && $user instanceof User) {
                    $check = true;
                    $this->container->get('session')->remove('user.register/byVideos');
                    $doctrine = $this->container->get('doctrine');
                    $manager = $doctrine->getManager();
                    $uservideos = $manager->getRepository("NaturaPassUserBundle:User")->findOneBy(array("id" => RegistrationController::ID_VIDEOS_USER));
                    $event->setResponse(
                        new RedirectResponse($this->container->get('router')->generate(
                            'fos_user_profile_show_name', array("usertag" => $uservideos->getUsertag()), true
                        ))
                    );
                }
                if ($user instanceof User && $user->hasRole('ROLE_PENDING')) {
                    $user->removeRole('ROLE_PENDING');
                    $manager = $this->container->get('doctrine')->getManager();

                    $manager->persist($user);
                    $manager->flush();

                    if (!$check) {
                        $event->setResponse(
                            new RedirectResponse($this->container->get('router')->generate(
                                'naturapass_user_invitation',
                                array('creation' => 1)
                            ))
                        );
                    }

                } else if ($this->container->get('request')->get('_route') == 'hwi_oauth_connect') {
                    $event->setResponse(new RedirectResponse($this->container->get('router')->generate('naturapass_main_homepage') . '?redirect=0'));
                }
            }
        }
    }
}
