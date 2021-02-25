<?php

/**
 * Created by PhpStorm.
 * User: vincentvalot
 * Date: 09/07/15
 * Time: 16:23
 */

namespace NaturaPass\NotificationBundle\Component;

use NaturaPass\NotificationBundle\Entity\AbstractNotification;
use NaturaPass\NotificationBundle\Entity\SocketPoolNotification;
use Doctrine\ORM\EntityManagerInterface;
use NaturaPass\UserBundle\Entity\Device;
use Psr\Log\LoggerInterface;
use RMS\PushNotificationsBundle\Device\Types;
use RMS\PushNotificationsBundle\Message\AndroidMessage;
use RMS\PushNotificationsBundle\Message\iOSMessage;
use RMS\PushNotificationsBundle\Message\MessageInterface;
use RMS\PushNotificationsBundle\Service\Notifications;
use ElephantIO\Client as ElephantIOClient;
use ElephantIO\Engine\SocketIO\Version1X as ElephantIOVersion1X;

class PushNotificationService
{

    const SOCKET_PORT = 3000;

    private $logger;
    private $push;
    protected $manager;

    public function __construct(LoggerInterface $logger, Notifications $push, EntityManagerInterface $manager)
//    public function __construct(LoggerInterface $logger, Notifications $push)
    {
        $this->logger = $logger;
        $this->push = $push;
        $this->manager = $manager;
    }

    /**
     * Processing the pushing of a notification
     *
     * @param AbstractNotification $notification
     */
    public function process(AbstractNotification $notification, $projectID)
    {
        if ($notification->isSocketEnabled())
            $this->pushOnSocket($notification, $projectID);
        if ($notification->isPushEnabled())
            $this->pushOnDevices($notification, $projectID);
    }

    /**
     * Envoi les notifications push sur les appareils des utilisateurs
     *
     * @param AbstractNotification $notification Notification envoyée aux appareils mobile
     *
     * @throws \RuntimeException
     */
    protected function pushOnDevices(AbstractNotification $notification, $projectID)
    {
        

        try {
            $receivers = $notification->getReceivers();
            
            $arrayDevices = array();
            foreach ($receivers as $receiver) {
                $user = $receiver->getReceiver();

                $devices = $user->getDevices();

                $silent = $notification->isPushSilent();

                if (!$notification->isPushSilent()) {
                    $parameter = $user->getParameters()->getNotificationByType($notification->getType(), $notification->getObjectIDModel());
                    if (!is_null($parameter) && $parameter->getWanted() != 1) {
                        $silent = true;
                    }
                }
                foreach ($devices as $device) {
                    if ($device->isAuthorized() && !in_array($device->getDevice()->getIdentifier(), $arrayDevices)) {
                        $arrayDevices[$device->getDevice()->getIdentifier()] = array("silent" => $silent, "device" => $device->getDevice());
                    }
                }
            }
            $pushData = $notification->getPushData();
            $pushData["projectID"] = $projectID;
            $contentMessage = $notification->getContent();
            foreach ($arrayDevices as $identifier => $array) {
                $device = $array["device"];
                $silent = $array["silent"];
                switch ($device->getType()) {
                    case Device::IOS:
                        $message = new iOSMessage();
                        if (!$silent) {
                            $message->setAPSBadge(1);
                            $message->setAPSSound('default');
                        }
                        break;

                    case Device::ANDROID:
                        $message = new AndroidMessage();
                        $message->setGCM(false);
                        break;
                }

                if ($message instanceof MessageInterface) {
                    //
                    if (!$silent) {
                    $id = $device->getIdentifier();
                    $mess = $contentMessage;
                    $pushData['message'] = $contentMessage;
                    $url = 'https://fcm.googleapis.com/fcm/send';
                    /*if($projectID == '891258442461111111111111'){
                        $pushData['message'] = 'Testez GRATUITEMENT des modérateurs de son à balles réelles !';
                        $pushData['content'] = 'Dépêchez-vous de vous inscrire en cliquant ici';
                        $fields = array (
                            'to' => $id,
                            'notification' => array (
                                "body" => 'Dépêchez-vous de vous inscrire en cliquant ici',
                                "title" => 'Testez GRATUITEMENT des modérateurs de son à balles réelles !',
                                "icon" => "myicon"
                            ),
                            'data' => $pushData,
                        );*/
//                    }else{
                        $fields = array (
                            'to' => $id,
                            'notification' => array (
                                "body" => $mess,
                                "icon" => "myicon"
                            ),
                            'data' => $pushData,
                        );
//                    }

                    $fields = json_encode ( $fields );
                    $headers = array (
                            'Authorization: key=' . "AIzaSyCGFu9AeT0NM0UOdpI91EX1MVS6OzMmD6I",
                            'Content-Type: application/json'
                    );

                    $ch = curl_init ();
                    curl_setopt ( $ch, CURLOPT_URL, $url );
                    curl_setopt ( $ch, CURLOPT_POST, true );
                    curl_setopt ( $ch, CURLOPT_HTTPHEADER, $headers );
                    curl_setopt ( $ch, CURLOPT_RETURNTRANSFER, true );
                    curl_setopt ( $ch, CURLOPT_POSTFIELDS, $fields );

                    $result = curl_exec ( $ch );
                    curl_close ( $ch );
                    //
                    }
                    $message->setData($pushData);
                    $message->setDeviceIdentifier($device->getIdentifier());

                    if (!$silent) {
                        $message->setMessage($contentMessage);
                    }

                    $this->push->send($message);

                    $responses = $this->push->getResponses(Types::OS_ANDROID_GCM);
/*                    foreach ($responses as $response) {
                        $message = json_decode($response->getContent());
                        if ($message->canonical_ids == 1) {
                            $this->manager->remove($device);
                            $this->manager->flush();
                        }
                    }*/
                }
            }
        } catch (\RuntimeException $exception) {
            $this->logger->error($exception->getMessage());
        }
    }

    /**
     * Envoie un message depuis une socket serveur
     * Attention à l'objet DateTime qui est envoyé car json_encode le transforme en array plutôt qu'en string
     *
     * @param AbstractNotification $notification
     */
    public function pushOnSocket(AbstractNotification $notification, $projectID)
    {
        $url = 'http://localhost:' . self::SOCKET_PORT;


        try {
            $toSend = array('data' => $notification->getSocketData());
            $toSend["projectID"] = $projectID;

            if ($notification instanceof SocketPoolNotification) {
                $toSend['pool'] = $notification->getPoolName();
            } else {
                $receivers = $notification->getReceivers();
                $toSend['receivers'] = array();

                foreach ($receivers as $receiver) {
                    $toSend['receivers'][] = $receiver->getReceiver()->getUsertag();
                }
            }

            $elephant = new ElephantIOClient(new ElephantIOVersion1X($url));
            $elephant->initialize();


            $elephant->emit($notification->getSocketEventName(), $toSend);

            $elephant->close();
        } catch (\Exception $exception) {
            $this->logger->error($exception->getMessage());
        }
    }

}

