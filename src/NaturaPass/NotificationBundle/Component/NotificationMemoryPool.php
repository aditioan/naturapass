<?php
/**
 * Created by PhpStorm.
 * User: vincentvalot
 * Date: 09/07/15
 * Time: 16:24
 */

namespace NaturaPass\NotificationBundle\Component;

use NaturaPass\NotificationBundle\Entity\AbstractNotification;

class NotificationMemoryPool
{

    protected $notifications = array();
    protected $projectID;

    /**
     * Add a notification to the queue
     *
     * @param AbstractNotification $notification
     */
    public function queueNotification(AbstractNotification $notification, $projectID)
    {
        $this->notifications[] = $notification;
        $this->projectID = $projectID;
    }

    /**
     * Process the queue
     *
     * @param PushNotificationService $push
     */
    public function flushQueue(PushNotificationService $push)
    {
        foreach ($this->notifications as $notification) {
            $push->process($notification, $this->projectID);
        }
    }
}