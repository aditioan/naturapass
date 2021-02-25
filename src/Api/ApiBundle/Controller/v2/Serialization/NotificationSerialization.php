<?php
/**
 * Created by PhpStorm.
 * User: vincentvalot
 * Date: 30/07/15
 * Time: 09:34
 */

namespace Api\ApiBundle\Controller\v2\Serialization;


use NaturaPass\NotificationBundle\Entity\AbstractNotification;
use NaturaPass\UserBundle\Entity\User;

/**
 * Class NotificationSerialization
 * @package Api\ApiBundle\Controller\v2\Serialization
 *
 * @method static serializeNotifications(array $notifications)
 * @method static serializeNotificationStatuss(array $notifications, User $connected)
 */
class NotificationSerialization extends Serialization
{

    public static function serializeNotification(AbstractNotification $notification)
    {
        $data = array(
            'id' => $notification->getId(),
            'content' => $notification->getContent(),
            'link' => $notification->getLink(),
            'updated' => $notification->getUpdated() instanceof \DateTime ? $notification->getUpdated()->format(\DateTime::ATOM) : (new \DateTime())->format(\DateTime::ATOM),
            'type' => $notification->getType(),
            'object_id' => $notification->getObjectID()
        );

        if ($notification->isSenderClient()) {
            $data['sender'] = UserSerialization::serializeUser($notification->getSender());
        } else {
            $data['sender'] = array(
                'firstname' => 'NaturaPass',
                'lastname' => 'NaturaPass',
                'photo' => '/img/default-avatar.jpg',
            );
        }

        return $data;
    }

    public static function serializeNotificationStatus(AbstractNotification $notification, User $connected)
    {
        $read = null;
        foreach ($notification->getReceivers() as $receiver) {
            if ($receiver->getReceiver() == $connected) {
                $read = $receiver->getState();
            }
        }
        $data = array(
            'id' => $notification->getId(),
            'content' => $notification->getContent(),
            'read' => $read,
            'link' => $notification->getLink(),
            'updated' => $notification->getUpdated() instanceof \DateTime ? $notification->getUpdated()->format(\DateTime::ATOM) : (new \DateTime())->format(\DateTime::ATOM),
            'type' => $notification->getType(),
            'object_id' => $notification->getObjectID(),
            'publication_id' => $notification->getPublicationID()
        );

        if ($notification->isSenderClient()) {
            $data['sender'] = UserSerialization::serializeUser($notification->getSender());
        } else {
            $data['sender'] = array(
                'firstname' => 'NaturaPass',
                'lastname' => 'NaturaPass',
                'photo' => '/img/default-avatar.jpg',
            );
        }

        return $data;
    }
}