<?php
/**
 * Created by PhpStorm.
 * User: vincentvalot
 * Date: 14/08/15
 * Time: 09:56
 */

namespace Api\ApiBundle\EventListener;

use Doctrine\Common\EventSubscriber;
use Doctrine\ORM\Event\PreFlushEventArgs;
use Doctrine\ORM\Events;
use Psr\Log\LoggerInterface;

class ConnectionAbortedListener implements EventSubscriber {

    protected $logger;

    public function __construct(LoggerInterface $logger) {
        $this->logger = $logger;
    }

    /**
     * Returns an array of events this subscriber wants to listen to.
     *
     * @return array
     */
    public function getSubscribedEvents()
    {
        return array(
            Events::preFlush
        );
    }

    public function preFlush(PreFlushEventArgs $args) {
        switch (connection_status()) {
            case CONNECTION_NORMAL:
                $this->logger->debug('Connection normal before flush');
                break;
            case CONNECTION_ABORTED:
                $this->logger->debug('Connection aborted before flush');
                break;
            case CONNECTION_TIMEOUT:
                $this->logger->debug('Connection timeout before flush');
                break;
        }

        if (connection_aborted()) {
            $args->getEntityManager()->rollback();
        }
    }
}