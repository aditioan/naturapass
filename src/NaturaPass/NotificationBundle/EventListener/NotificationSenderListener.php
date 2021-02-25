<?php

/**
 * Created by PhpStorm.
 * User: vincentvalot
 * Date: 15/04/14
 * Time: 23:48
 */

namespace NaturaPass\NotificationBundle\EventListener;

use NaturaPass\NotificationBundle\Component\NotificationMemoryPool;
use NaturaPass\NotificationBundle\Component\PushNotificationService;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\KernelEvents;

class NotificationSenderListener {

    private $container;

    /**
     * Constructor.
     *
     * @param ContainerInterface $container A ContainerInterface instance
     */
    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    public function onKernelTerminate() {
        $pool = $this->container->get('naturapass.notification.pool');
        $push = $this->container->get('naturapass.notification.push');

        if ($pool instanceof NotificationMemoryPool) {
            $pool->flushQueue($push);
        }
    }
}
