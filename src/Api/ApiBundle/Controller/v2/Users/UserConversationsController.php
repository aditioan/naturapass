<?php

/**
 * Created by PhpStorm.
 * User: vincentvalot
 * Date: 29/07/15
 * Time: 09:11
 */

namespace Api\ApiBundle\Controller\v2\Users;

use Api\ApiBundle\Controller\v2\ApiRestController;
use Api\ApiBundle\Controller\v2\Serialization\ConversationSerialization;
use Api\ApiBundle\Controller\v2\Serialization\UserSerialization;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\QueryBuilder;
use FOS\RestBundle\Util\Codes;
use NaturaPass\GraphBundle\Entity\Edge;
use NaturaPass\GraphBundle\Repository\RecommendationRepository;
use NaturaPass\GroupBundle\Entity\Group;
use NaturaPass\GroupBundle\Entity\GroupUser;
use NaturaPass\LoungeBundle\Entity\Lounge;
use NaturaPass\MessageBundle\Entity\Conversation;
use NaturaPass\MessageBundle\Entity\Message;
use NaturaPass\MessageBundle\Entity\OwnerMessage;
use NaturaPass\NotificationBundle\Entity\User\UserFriendshipAskedNotification;
use NaturaPass\NotificationBundle\Entity\User\UserFriendshipConfirmedNotification;
use NaturaPass\UserBundle\Entity\User;
use NaturaPass\UserBundle\Entity\UserFriend;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;

class UserConversationsController extends ApiRestController
{

    /**
     * Retourne les conversation de l'utilisateur
     *
     * GET /v2/user/conversations?limit=5&offset=0
     *
     * @param Request $request
     *
     * @return \FOS\RestBundle\View\View
     *
     */
    public function getConversationsAction(Request $request)
    {
        $this->authorize();

        $limit = $request->query->get('limit', 5);
        $offset = $request->query->get('offset', 0);
        $manager = $this->getDoctrine()->getManager();

        $checkConversations = $this->getUser()->getConversations();
        $conversations = new ArrayCollection();
        foreach ($checkConversations as $conversation) {
            if ($conversation->getParticipants()->count() <= 1) {
                $manager->remove($conversation);
                $manager->flush();
            } else {
                $conversations->add($conversation);
            }
        }


        //Get conversations of user
        $conversations = $conversations->slice($offset, $limit);

        $messages = array();
        $test = array();

        if ($conversations) {

            foreach ($conversations as $conversation) {
                $qb = $manager->createQueryBuilder();

                //Get lastest message
                $qb->select('m')
                    ->from('NaturaPassMessageBundle:Message', 'm')
                    ->where('m.conversation = :conversationId')
                    ->orderBy('m.updated', 'DESC')
                    ->setMaxResults(1)
                    ->setParameter('conversationId', $conversation->getId());

                $message = $qb->getQuery()->getSingleResult();

                if ($message instanceof Message) {
                    $test[] = "ok";
                    //Count unread message
                    $qb = $manager->createQueryBuilder();
                    $qb->select('count(m.id)')
                        ->from('NaturaPassMessageBundle:Message', 'm')
                        ->join('NaturaPassMessageBundle:OwnerMessage', 'om')
                        ->where(
                            'm.id = om.message AND om.read = :state AND m.conversation = :conversationId AND om.owner = :ownerId'
                        )
                        ->setParameter('state', OwnerMessage::MESSAGE_UNREAD)
                        ->setParameter('conversationId', $conversation->getId())
                        ->setParameter('ownerId', $this->getUser()->getId());

                    $unreadCount = $qb->getQuery()->getSingleScalarResult();

                    $participants = $conversation->getParticipants();
                    $trueParticipants = array();
                    foreach ($participants as $index => $participant) {
                        if ($participant->getId() != $this->getUser()->getId()) {
                            $trueParticipants[] = $participants[$index];
                        }
                    }
                    //Build message data
                    $messages[] = ConversationSerialization::serializeMessage($message, $unreadCount, $trueParticipants, $this->getUser());
                }
            }
        }

        return $this->view(array('messages' => $messages));
    }

    /**
     * Retourne les conversation des chasses de l'utilisateur
     *
     * GET /v2/user/huntconversations?limit=5&offset=0
     *
     * @param Request $request
     *
     * @return \FOS\RestBundle\View\View
     *
     */
    public function getHuntconversationsAction(Request $request)
    {
        $this->authorize();

        $limitHunt = $request->query->get('limit', 3);
        $offsetHunt = $request->query->get('offset', 0);
        $messagesHunt = array();

        foreach ($this->getUser()->getLounges() as $hunt) {
            if (count($hunt->getMessages())) {
                $messagesHunt[] = ConversationSerialization::serializeMessageHunt($hunt, $this->getUser());
            }
        }

        return $this->view(array('messagesHunt' => array_slice($messagesHunt, $offsetHunt, $limitHunt)));
    }

    /**
     * Retourne les conversation des groupes de l'utilisateur
     *
     * GET /v2/user/groupconversations?limit=5&offset=0
     *
     * @param Request $request
     *
     * @return \FOS\RestBundle\View\View
     *
     */
    public function getGroupconversationsAction(Request $request)
    {
        $this->authorize();

        $limitGroup = $request->query->get('limit', 3);
        $offsetGroup = $request->query->get('offset', 0);
        $messagesGroup = array();

        foreach ($this->getUser()->getAllGroups(array(GroupUser::ACCESS_DEFAULT, GroupUser::ACCESS_ADMIN)) as $group) {
            if (count($group->getMessages())) {
                $messagesGroup[] = ConversationSerialization::serializeMessageGroup($group, $this->getUser());
            }
        }

        return $this->view(array('messagesGroup' => array_slice($messagesGroup, $offsetGroup, $limitGroup)));
    }

    /**
     * Retourne l'ensemble des conversations de l'utilisateur
     *
     * GET /v2/user/allconversations?limit=5&offset=0&limit_hunt=5&offset_hunt=0&limit_group=5&offset_group=0
     *
     * @param Request $request
     *
     * @return \FOS\RestBundle\View\View
     *
     */
    public function getAllconversationsAction(Request $request)
    {
        $this->authorize();

        $limit = $request->query->get('limit', 5);
        $offset = $request->query->get('offset', 0);

        //Get conversations of user
        $conversations = $this->getUser()->getConversations()->slice($offset, $limit);

        $messages = array();

        if ($conversations) {
            $manager = $this->getDoctrine()->getManager();

            foreach ($conversations as $conversation) {
                $qb = $manager->createQueryBuilder();

                //Get lastest message
                $qb->select('m')
                    ->from('NaturaPassMessageBundle:Message', 'm')
                    ->where('m.conversation = :conversationId')
                    ->orderBy('m.updated', 'DESC')
                    ->setMaxResults(1)
                    ->setParameter('conversationId', $conversation->getId());

                $message = $qb->getQuery()->getResult();
                $message = $message[0];
                if ($message instanceof Message) {
                    //Count unread message
                    $qb = $manager->createQueryBuilder();
                    $qb->select('count(m.id)')
                        ->from('NaturaPassMessageBundle:Message', 'm')
                        ->join('NaturaPassMessageBundle:OwnerMessage', 'om')
                        ->where(
                            'm.id = om.message AND om.read = :state AND m.conversation = :conversationId AND om.owner = :ownerId'
                        )
                        ->setParameter('state', OwnerMessage::MESSAGE_UNREAD)
                        ->setParameter('conversationId', $conversation->getId())
                        ->setParameter('ownerId', $this->getUser()->getId());

                    $unreadCount = $qb->getQuery()->getSingleScalarResult();

                    $participants = $conversation->getParticipants();
                    $trueParticipants = array();
                    foreach ($participants as $index => $participant) {
                        if ($participant->getId() != $this->getUser()->getId()) {
                            $trueParticipants[] = $participants[$index];
                        }
                    }
                    //Build message data
                    $messages[] = ConversationSerialization::serializeMessage($message, $unreadCount, $trueParticipants, $this->getUser());
                }
            }
        }

        $limitHunt = $request->query->get('limit_hunt', 3);
        $offsetHunt = $request->query->get('offset_hunt', 0);
        $messagesHunt = array();
        $messagesHuntTmp = array();
        $messagesHuntOrder = array();

        foreach ($this->getUser()->getLounges() as $hunt) {
            if (count($hunt->getMessages()) && ($hunt->getAllowShowChat() == Lounge::ALLOW_ALL_MEMBERS || $hunt->isAdmin($this->getUser()))) {
                $messagesHuntTmp[$hunt->getId()] = ConversationSerialization::serializeMessageHunt($hunt, $this->getUser());
                $messagesHuntOrder[$hunt->getId()] = $hunt->getLastMessage()->getCreated()->format("YmdHis");
            }
        }
        arsort($messagesHuntOrder);
        foreach ($messagesHuntOrder as $id_hunt => $created) {
            $messagesHunt[] = $messagesHuntTmp[$id_hunt];
        }

        $limitGroup = $request->query->get('limit_group', 3);
        $offsetGroup = $request->query->get('offset_group', 0);
        $messagesGroup = array();
        $messagesGroupTmp = array();
        $messagesGroupOrder = array();

        foreach ($this->getUser()->getAllGroups(array(GroupUser::ACCESS_DEFAULT, GroupUser::ACCESS_ADMIN)) as $group) {
            if (count($group->getMessages()) && ($group->getAllowShowChat() == Group::ALLOW_ALL_MEMBERS || $group->isAdmin($this->getUser()))) {
                $messagesGroupTmp[$group->getId()] = ConversationSerialization::serializeMessageGroup($group, $this->getUser());
                $messagesGroupOrder[$group->getId()] = $group->getLastMessage()->getCreated()->format("YmdHis");
            }
        }
        arsort($messagesGroupOrder);
        foreach ($messagesGroupOrder as $id_group => $created) {
            $messagesGroup[] = $messagesGroupTmp[$id_group];
        }

        return $this->view(array('messages' => $messages, "messagesHunt" => array_slice($messagesHunt, $offsetHunt, $limitHunt), "messagesGroup" => array_slice($messagesGroup, $offsetGroup, $limitGroup)));
    }
}
