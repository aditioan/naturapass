<?php
/**
 * Created by PhpStorm.
 * User: vincentvalot
 * Date: 23/07/15
 * Time: 10:59
 */

namespace NaturaPass\UserBundle\Handler;

use NaturaPass\MainBundle\Component\RedisService;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Http\Logout\LogoutHandlerInterface;

class LogoutHandler implements LogoutHandlerInterface {

    protected $redisService;

    public function __construct(RedisService $redisService) {
        $this->redisService = $redisService;
    }

    /**
     * @param Request $request
     * @param Response $response
     * @param TokenInterface $tokenInterface
     */
    public function logout(Request $request, Response $response, TokenInterface $tokenInterface) {
        $this->redisService->removeSession();
    }

}