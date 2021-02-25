<?php

namespace NaturaPass\NotificationBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;

class NotificationsController extends Controller {

    public function indexAction() {
        return $this->render('NaturaPassNotificationBundle:Default:index.html.twig', array(
                    'notifications' => $this->getUser()->getAllNotifications()
        ));
    }

}
