<?php
/**
 * Created by PhpStorm.
 * User: vincentvalot
 * Date: 23/07/15
 * Time: 11:21
 */

namespace NaturaPass\UserBundle\Listener;


use NaturaPass\MainBundle\Component\RedisService;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Http\Event\InteractiveLoginEvent;

class LoginListener {

    protected $redisService;

    public function __construct(RedisService $redisService) {
        $this->redisService = $redisService;
    }

    public function onSecurityInteractiveLogin(InteractiveLoginEvent $event)
    {
        $user = $event->getAuthenticationToken()->getUser();

        if ($user instanceof UserInterface) {
            $this->redisService->addSession();
        }
    }
}