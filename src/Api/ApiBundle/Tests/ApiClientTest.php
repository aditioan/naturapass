<?php
/**
 * Created by PhpStorm.
 * User: vincentvalot
 * Date: 10/09/14
 * Time: 08:32
 */

namespace Api\ApiBundle\Tests;

use NaturaPass\UserBundle\Entity\User;
use Symfony\Bundle\FrameworkBundle\Client;

class ApiClientTest extends Client {

    /**
     * @var integer
     */
    protected $id;

    /**
     * @var \NaturaPass\UserBundle\Entity\User
     */
    protected $user;

    /**
     * @var \Symfony\Component\HttpFoundation\Session\Session
     */
    protected $session;

    /**
     * @param mixed $id
     */
    public function setId($id) {
        $this->id = $id;
    }

    /**
     * @return mixed
     */
    public function getId() {
        return $this->id;
    }

    /**
     * @param \NaturaPass\UserBundle\Entity\User $user
     */
    public function setUser(User $user) {
        $this->user = $user;
        $this->id = $user->getId();
    }

    /**
     * @return \NaturaPass\UserBundle\Entity\User
     */
    public function getUser() {
        return $this->user;
    }

    /**
     * @param \Symfony\Component\HttpFoundation\Session\Session $session
     */
    public function setSession($session)
    {
        $this->session = $session;
    }
} 