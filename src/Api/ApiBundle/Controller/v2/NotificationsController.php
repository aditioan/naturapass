<?php

/**
 * Created by PhpStorm.
 * User: vincentvalot
 * Date: 30/07/15
 * Time: 09:39
 */

namespace Api\ApiBundle\Controller\v2;

use Api\ApiBundle\Controller\v2\Serialization\NotificationSerialization;
use Doctrine\ORM\QueryBuilder;
use FOS\RestBundle\Util\Codes;
use NaturaPass\GroupBundle\Entity\Group;
use NaturaPass\NotificationBundle\Entity\AbstractNotification;
use NaturaPass\NotificationBundle\Entity\NotificationReceiver;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use NaturaPass\UserBundle\Entity\User;
use NaturaPass\UserBundle\Entity\UserFriend;
use NaturaPass\MessageBundle\Entity\OwnerMessage;
use Symfony\Component\Templating\Helper\AssetsHelper;
use NaturaPass\LoungeBundle\Entity\LoungeUser;
use NaturaPass\GroupBundle\Entity\GroupUser;
use Symfony\Component\HttpFoundation\Request;

class NotificationsController extends ApiRestController
{

    /**
     * Retrieve feedback of the notifications
     *
     * @param string $service Which service we want to retrieve feedback from
     * @return \FOS\RestBundle\View\View
     */
    public function getNotificationsFeedbackAction($service)
    {
        $this->authorize(null, 'ROLE_ADMIN');

        if ($service === 'ios') {
            $feedbackService = $this->get('rms_push_notifications.ios.feedback');
            $uuids = $feedbackService->getDeviceUUIDs();

            return $this->view($uuids, Codes::HTTP_OK);
        }

        return $this->view($this->success(), Codes::HTTP_OK);
    }

    /**
     * Get a notification by its id
     *
     * PUT  /v2/notifications/{notification_id}
     *
     * @param AbstractNotification $notification
     * @return \FOS\RestBundle\View\View
     *
     * @ParamConverter("notification", class="NaturaPassNotificationBundle:AbstractNotification")
     */
    public function getNotificationAction(AbstractNotification $notification)
    {
        $this->authorize();

        $receiver = $this->getDoctrine()->getManager()->getRepository('NaturaPassNotificationBundle:NotificationReceiver')->findOneBy(
            array(
                'notification' => $notification,
                'receiver' => $this->getUser()
            )
        );

        if ($receiver instanceof NotificationReceiver) {
            return $this->view(array('notification' => NotificationSerialization::serializeNotification($notification)));
        }

        throw new HttpException(Codes:: HTTP_BAD_REQUEST, $this->message('errors.notification.notreceived'));
    }

    /**
     * Notify the notification that it has been readed
     *
     * PUT  /v2/notifications/{notification_id}/read
     *
     * @param AbstractNotification $notification
     * @return \FOS\RestBundle\View\View
     * @throws \Symfony\Component\HttpKernel\Exception\HttpException
     *
     * @ParamConverter("notification", class="NaturaPassNotificationBundle:AbstractNotification")
     */
    public function putNotificationReadAction(AbstractNotification $notification)
    {
        $this->authorize();

        $manager = $this->getDoctrine()->getManager();
        $receiver = $manager->getRepository('NaturaPassNotificationBundle:NotificationReceiver')->findOneBy(
            array(
                'notification' => $notification,
                'receiver' => $this->getUser()
            )
        );

        if ($receiver instanceof NotificationReceiver) {
            $receiver->setState(NotificationReceiver::STATE_READ);

            $manager->persist($receiver);
            $manager->flush();

            return $this->view($this->success(), Codes::HTTP_OK);
        }

        throw new HttpException(Codes:: HTTP_BAD_REQUEST, $this->message('errors.notification.notreceived'));
    }

    /**
     * Get the connected user's notifications
     *
     * GET /v2/notifications
     */
    public function getNotificationsAction()
    {
        $this->authorize();

        /**
         * @var QueryBuilder $qb
         */
        $qb = $this->getDoctrine()
            ->getManager()
            ->getRepository('NaturaPassNotificationBundle:AbstractNotification')
            ->createQueryBuilder('n');

        $notifications = $qb
            ->select('n')
            ->innerJoin('n.receivers', 'nr')
            ->where('nr.receiver = :receiver')
            ->andWhere('nr.state = :unread')
            ->setParameter('receiver', $this->getUser())
            ->setParameter('unread', NotificationReceiver::STATE_UNREAD)
            ->orderBy('n.updated', 'DESC')
            ->getQuery()
            ->getResult();

        return $this->view(
            array('notifications' => NotificationSerialization::serializeNotifications($notifications)), Codes::HTTP_OK
        );
    }

    /**
     * Get the connected user's notifications of all types
     *
     * GET /v2/notification/all
     */
    public function getNotificationAllAction()
    {
        // return notifications
        $this->authorize();
        $manager = $this->getDoctrine()->getManager();

        //get unread notification
        $qb = $manager->getRepository('NaturaPassNotificationBundle:AbstractNotification')
            ->createQueryBuilder('n');
        $notifications = $qb
            ->select('n')
            ->innerJoin('n.receivers', 'nr')
            ->where('nr.receiver = :receiver')
            ->andWhere('nr.state = :unread')
            ->setParameter('receiver', $this->getUser())
            ->setParameter('unread', NotificationReceiver::STATE_UNREAD)
            ->orderBy('n.updated', 'DESC')
            ->getQuery()
            ->getResult();
        $nbUnreadNotification = count($notifications);

        // get user waitting
        $nbUserWaiting = count($this->getUser()->getFriends(
            UserFriend::TYPE_BOTH, UserFriend::ASKED, User::USERFRIENDWAY_FRIENDTOUSER
        ));

        // get unread messages
        $qb = $manager->createQueryBuilder();
        $qb->select('om')
            ->from('NaturaPassMessageBundle:OwnerMessage', 'om')
            ->where('om.owner = :owner')
            ->andWhere('om.read = :state')
            ->setParameter('owner', $this->getUser()->getId())
            ->setParameter('state', OwnerMessage::MESSAGE_UNREAD);
        $nbUnreadMessage = count($qb->getQuery()->getResult());

        // get chasse invitation
        $nbChasseInvitation = count($this->getUser()->getLounges(array(LoungeUser::ACCESS_INVITED, LoungeUser::ACCESS_RESTRICTED)));

        // get group invitation
        $nbGroupInvitation = count($this->getUser()->getGroupSubscribes(array(GroupUser::ACCESS_INVITED, GroupUser::ACCESS_RESTRICTED)));
        foreach ($this->getUser()->getAllGroups(array(GroupUser::ACCESS_ADMIN)) as $groupAdmin) {
            $subscribers = $groupAdmin->getSubscribers(array(GroupUser::ACCESS_RESTRICTED));
            $nbGroupInvitation += $subscribers->count();
        }
        $nbLive = count($this->getUser()->getAllHuntsLives());

        return $this->view(
            array('nbUnreadNotification' => $nbUnreadNotification, 'nbUserWaiting' => $nbUserWaiting, 'nbUnreadMessage' => $nbUnreadMessage, 'nbChasseInvitation' => $nbChasseInvitation, 'nbGroupInvitation' => $nbGroupInvitation, 'nbLive' => $nbLive), Codes::HTTP_OK
        );
    }

    /**
     * Get the connected user's notifications of all types
     *
     * GET /v2/notification/all/status?limit=30&offset=0
     * @param Request $request
     * @return \FOS\RestBundle\View\View
     */
    public function getNotificationAllStatusAction(Request $request)
    {
        // return notifications
        $this->authorize();
        $limit = $request->query->get('limit', 30);
        $offset = $request->query->get('offset', 0);
        $manager = $this->getDoctrine()->getManager();

        //get unread notification
        $qb = $manager->getRepository('NaturaPassNotificationBundle:AbstractNotification')
            ->createQueryBuilder('n');
        $notifications = $qb
            ->select('n')
            ->innerJoin('n.receivers', 'nr')
            ->where('nr.receiver = :receiver')
            ->setParameter('receiver', $this->getUser())
            ->orderBy('n.updated', 'DESC')
            ->getQuery()
            ->getResult();

        // get unread notifications
        $qb = $manager->getRepository('NaturaPassNotificationBundle:AbstractNotification')
            ->createQueryBuilder('n');
        $notificationsUnread = $qb
            ->select('n')
            ->innerJoin('n.receivers', 'nr')
            ->where('nr.receiver = :receiver')
            ->andWhere('nr.state = :unread')
            ->setParameter('receiver', $this->getUser())
            ->setParameter('unread', NotificationReceiver::STATE_UNREAD)
            ->orderBy('n.updated', 'DESC')
            ->getQuery()
            ->getResult();
        $nbUnreadNotification = count($notificationsUnread);

        return $this->view(
            array('notifications' => NotificationSerialization::serializeNotificationStatuss(array_slice($notifications, $offset, $limit), $this->getUser()), 'nbUnreadNotifications' => $nbUnreadNotification, 'nbTotalNotifications' => count($notifications)), Codes::HTTP_OK
        );
    }


    /**
     * update all notification of current user to read
     *
     * PUT /v2/notifications/readall
     *
     * @param Request $request
     * @return \FOS\RestBundle\View\View
     *
     */
    public function putNotificationsReadallAction(Request $request)
    {
        $this->authorize();

        $qb = $this->getDoctrine()
            ->getManager()
            ->getRepository('NaturaPassNotificationBundle:NotificationReceiver')
            ->createQueryBuilder('nr');

        $notifications = $qb
            ->select('nr')
            ->where('nr.receiver = :receiver')
            ->andWhere('nr.state = :unread')
            ->setParameter('receiver', $this->getUser())
            ->setParameter('unread', NotificationReceiver::STATE_UNREAD)
            ->getQuery()
            ->getResult();

        $manager = $this->getDoctrine()->getManager();
        foreach ($notifications as $notification) {
            $notification->setState(NotificationReceiver::STATE_READ);
            $manager->persist($notification);
        }
        $manager->flush();
        return $this->view($this->success(), Codes::HTTP_OK);
    }

}
