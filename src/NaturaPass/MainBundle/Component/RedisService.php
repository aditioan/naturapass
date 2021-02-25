<?php
/**
 * Created by PhpStorm.
 * User: vincentvalot
 * Date: 23/07/15
 * Time: 11:31
 */

namespace NaturaPass\MainBundle\Component;

use Predis\Client as RedisClient;
use Predis\Connection\ConnectionException;
use Symfony\Component\HttpFoundation\Session\Session;

class RedisService {

    const SESSION_STORE_NAME = 'sid:';

    /**
     * @var RedisClient
     */
    protected $client;

    /**
     * @var Session
     */
    protected $session;

    public function __construct(Session $session) {
        $this->session = $session;

        $this->client = new RedisClient(
            array(),
            array()
        );
    }

    /**
     * Add current session to Redis Store
     */
    public function addSession() {
        try {
            $this->client->set(self::SESSION_STORE_NAME . $this->session->getId(), 10800000);
            $this->client->expire(self::SESSION_STORE_NAME . $this->session->getId(), 10800000);
        } catch (ConnectionException $exception) {

        }
    }

    /**
     * Remove current session from Redis Store
     */
    public function removeSession() {
        try {
            $this->client->del(array(self::SESSION_STORE_NAME . $this->session->getId()));
        } catch (ConnectionException $exception) {

        }
    }
}
