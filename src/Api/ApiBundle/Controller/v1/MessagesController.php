<?php

namespace Api\ApiBundle\Controller\v1;

use Doctrine\Common\Collections\ArrayCollection;
use NaturaPass\MessageBundle\Entity\Conversation;
use NaturaPass\MessageBundle\Entity\Message;
use NaturaPass\MessageBundle\Entity\OwnerMessage;
use NaturaPass\NotificationBundle\Entity\Chat\SocketOnly\ChatMessageNotification;
use Symfony\Component\HttpFoundation\Request;
use FOS\RestBundle\Controller\Annotations\View;
use FOS\RestBundle\Util\Codes;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;

/**
 * Description of MessageController
 *
 * @author tuan
 */
class MessagesController extends ApiRestController
{

    /**
     * Send message
     *
     * POST /messages
     *
     * JSON lié:
     * {
     *      "message": {
     *           "created": "2015-10-21T13:55:27+02:00",
     *           "conversationId": conversation.id,
     *           "tempid": 1412226442585,
     *           "content": element.val(),
     *           "participants": [],
     *           "pendingParticipants": []
     *      }
     *
     * }
     *
     * @param Request $request
     *
     * @throws HttpException
     *
     * @return \FOS\RestBundle\View\View
     */
    function postMessagesAction(Request $request)
    {
        $this->authorize();

        $messageParams = $request->request->get('message', false);

        $conversation = array();
        $content = isset($messageParams["content"]) ? trim($messageParams["content"]) : "";
        $participants = isset($messageParams["participants"]) ? $messageParams["participants"] : null;
        $pendingParticipants = isset($messageParams["pendingParticipants"]) ? $messageParams["pendingParticipants"] : null;
        $conversationId = isset($messageParams["conversationId"]) ? (int)$messageParams["conversationId"] : null;

        //Validate data
        if (!$content || (!$participants && !$pendingParticipants)) {
            return new BadRequestHttpException($this->message('errors.parameters'));
        }

        $manager = $this->getDoctrine()->getManager();

        //Get or create the conversation for new conversation
        $pendingParticipantIds = array($this->getUser()->getId());
        $conversationParticipants = null;

        if ($conversationId) {
            $conversation = $manager->getRepository('NaturaPassMessageBundle:Conversation')->find($conversationId);
        } elseif (!$conversationId && $pendingParticipants) {
            foreach ($pendingParticipants as $pendingParticipant) {
                $pendingParticipantIds[] = $pendingParticipant["id"];
            }

            sort($pendingParticipantIds);

            //Get conversations of user
            $userConversations = $this->getUser()->getConversations();

            foreach ($userConversations as $userConversation) {
                $conversationParticipanIds = array();

                //Get participiants of the conversation
                $conversationParticipants = $userConversation->getParticipants();

                foreach ($conversationParticipants as $participant) {
                    //Push participant id to array
                    $conversationParticipanIds[] = $participant->getId();
                }

                //Sort participant ids array
                sort($conversationParticipanIds);

                //Compare two array. If true then return conversation object
                if ($pendingParticipantIds == $conversationParticipanIds) {
                    //If only 2 participants
                    if (count($pendingParticipantIds) == 2) {
                        $conversation = $userConversation;
                        break;
                    }

                    //Check creator if more than 2 participants
                    $qb = $manager->createQueryBuilder();

                    //Get lastest message
                    $qb->select('m')
                        ->from('NaturaPassMessageBundle:Message', 'm')
                        ->where('m.conversation = :conversationId')
                        ->orderBy('m.updated', 'ASC')
                        ->setMaxResults(1)
                        ->setParameter('conversationId', $userConversation->getId());

                    $message = $qb->getQuery()->getSingleResult();

                    if ($message && $message->getOwner()->getId() == $this->getUser()->getId()) {
                        $conversation = $userConversation;
                        break;
                    }
                }
            }
        } else {
            return new HttpException(Codes::HTTP_BAD_REQUEST, $this->message('errors.parameters'));
        }

        //Create conersation
        if (!$conversation) {
            $conversation = new Conversation();
            //$pendingParticipantIds = implode(",", $pendingParticipantIds);

            $arrayOldParticipants[] = array();
            if (!is_null($participants)) {
                foreach ($participants as $participant) {
                    $arrayOldParticipants[] = $participant["id"];
                }
            }
            $pendingParticipantIds = array_merge($pendingParticipantIds, $arrayOldParticipants);

            $qb = $manager->createQueryBuilder();
            //Build query to get the participans
            $qb->select('u')
                ->from('NaturaPassUserBundle:User', 'u')
                ->where('u.id IN (:userIds)')
                ->setParameter('userIds', $pendingParticipantIds);

            $tmp = $qb->getQuery()->getResult();


            $participants = array();
            foreach ($tmp as $participant) {
                $conversation->addParticipant($participant);

                if ($participant->getId() != $this->getUser()->getId()) {
                    $participants[] = array(
                        'id' => $participant->getId(),
                        'firstname' => $participant->getFirstname(),
                        'lastname' => $participant->getLastname(),
                        'fullname' => $participant->getFullname(),
                        'usertag' => $participant->getUsertag(),
                        'profilepicture' => $participant->getProfilePicture() ? $participant->getProfilePicture()->getThumb() : '/img/default-avatar.jpg'
                    );
                }
            }

            $manager->persist($conversation);
            $manager->flush();

            $conversationParticipants = $conversation->getParticipants();
        } elseif ($participants === null && $conversationParticipants) {
            $participants = array();

            foreach ($conversationParticipants as $conversationParticipant) {
                if ($conversationParticipant->getId() != $this->getUser()->getId()) {
                    $participants[] = array(
                        'id' => $conversationParticipant->getId(),
                        'firstname' => $conversationParticipant->getFirstname(),
                        'lastname' => $conversationParticipant->getLastname(),
                        'fullname' => $conversationParticipant->getFullname(),
                        'usertag' => $conversationParticipant->getUsertag(),
                        'profilepicture' => $conversationParticipant->getProfilePicture() ? $conversationParticipant->getProfilePicture()->getThumb() : '/img/default-avatar.jpg'
                    );
                }
            }
        } elseif (!$conversationParticipants) {
            $conversationParticipants = $conversation->getParticipants();
        }

        //Save message
        $message = new Message();
        $message->setContent($content)
            ->setCreated(isset($messageParams["created"]) ? new \DateTime($messageParams["created"]) : new \DateTime())
            ->setOwner($this->getUser())
            ->setConversation($conversation);
        if(isset($messageParams["guid"])){
            $message->setGuid($messageParams["guid"]);
        }

        $manager->persist($message);
        $manager->flush();

        $conversation->setUpdated($message->getUpdated());
        $manager->persist($conversation);
        $manager->flush();

        //vietlh
                $checkDuplicate = $this->checkDuplicatedMessage($message);
                if ($checkDuplicate) {
                    foreach ($checkDuplicate as $msg) {
                        $manager = $this->getDoctrine()->getManager();
                        $manager->remove($msg);
                        $manager->flush();
                    }
                }
        //

        $conversationParticipants = $conversationParticipants ? $conversationParticipants : array();

        foreach ($conversationParticipants as $conversationParticipant) {
            //Save message
            $ownerMessage = new OwnerMessage();
            $ownerMessage->setOwner($conversationParticipant)
                ->setMessage($message);

            if ($conversationParticipant->getId() == $this->getUser()->getId()) {
                $ownerMessage->setRead(OwnerMessage::MESSAGE_READ);
            }

            $manager->persist($ownerMessage);
            $manager->flush();
        }

        //Build reviervers data
        $receivers = array();
        $receiversTag = array();
        foreach ($conversationParticipants as $conversationParticipant) {
            if ($this->getUser()->getUsertag() != $conversationParticipant->getUsertag()) {
                $receivers[] = $conversationParticipant;
                $receiversTag[] = $conversationParticipant->getUsertag();
            }
        }

/*foreach ($receivers as $receiver) {
                $user = $receiver->getReceiver();
                $devices = $user->getDevices();
                foreach ($devices as $device) {
                        $arrayDevices[$device->getDevice()->getIdentifier()] = array("silent" => $silent, "device" => $device->getDevice());
                }
            }
        \Doctrine\Common\Util\Debug::dump($arrayDevices);die("fck");*/

        $arrayConversation = array(
            'conversation' => array(
                'id' => $conversation->getId(),
                'created' => $conversation->getCreated()->format(\DateTime::ATOM),
                'updated' => $conversation->getUpdated()->format(\DateTime::ATOM),
                'participants' => $participants
            )
        );
        $arrayMessageData = array_merge($this->getFormatMessage($message), $arrayConversation, array('receivers' => $receiversTag));
        $this->getNotificationService()->queue(
            new ChatMessageNotification($message, $arrayMessageData), $receivers
        );


        return $this->view(array("message" => array_merge($this->getFormatMessage($message), $arrayConversation)), Codes::HTTP_CREATED);
    }

    /**
     * Add more participants to conversation
     *
     * POST /conversations/participants
     *
     * JSON lié:
     * {
     *      "conversation": {
     *          "id": 1,
     *          "participants": []
     *      }
     *
     * }
     *
     * @param Request $request
     *
     * @throws HttpException
     *
     * @return \FOS\RestBundle\View\View
     *
     */
    function postConversationParticipantsAction(Request $request)
    {
        $this->authorize();

        $params = $request->request->get('conversation', false);

        $pendingParticipants = isset($params["participants"]) ? $params["participants"] : null;
        $conversationId = isset($params["id"]) ? (int)$params["id"] : null;

        if (!$conversationId || !$pendingParticipants) {
            throw new BadRequestHttpException($this->message('errors.parameters'));
        }

        $manager = $this->getDoctrine()->getManager();
        $conversation = $manager->getRepository('NaturaPassMessageBundle:Conversation')->find($conversationId);

        if (!$conversation) {
            throw new BadRequestHttpException($this->message('errors.conversation.nonexistent'));
        }

        $qb = $manager->createQueryBuilder();

        $pendingParticipantIds = array_map(
            function ($item) {
                return $item["id"];
            }, $pendingParticipants
        );

        $participants = $conversation->getParticipants();

        foreach ($participants as $participant) {
            if (in_array($participant->getId(), $pendingParticipantIds)) {
                if (($key = array_search($participant->getId(), $pendingParticipantIds)) !== false) {
                    unset($pendingParticipantIds[$key]);
                }
            }
        }

        if ($pendingParticipantIds) {
            //Build query to get the participans
            $qb->select('u')
                ->from('NaturaPassUserBundle:User', 'u')
                ->where('u.id IN (:userIds)')
                ->setParameter('userIds', $pendingParticipantIds);

            $tmp = $qb->getQuery()->getResult();

            foreach ($tmp as $participant) {
                $conversation->addParticipant($participant);
            }

            $manager->persist($conversation);
            $manager->flush();
        }

        return $this->view(array("participants" => $pendingParticipants), Codes::HTTP_OK);
    }

    /**
     * Update owner message
     *
     * PUT /read/message
     *
     * JSON lié:
     * {
     *      "conversation": {
     *          "id": null,
     *          "messageId": 1
     *      }
     *
     * }
     *
     * @param Request $request
     *
     * @throws HttpException
     *
     * @return \FOS\RestBundle\View\View
     *
     */
    function putReadMessageAction(Request $request)
    {
        $this->authorize();

        $params = $request->request->get('conversation', false);
        $messageId = isset($params["messageId"]) ? $params["messageId"] : null;
        $conversationId = isset($params["id"]) ? (int)$params["id"] : null;

        $manager = $this->getDoctrine()->getManager();
        $qb = $manager->createQueryBuilder();

        //Build query to get the messages of conversations in converstaion ids
        $qb->select('om');

        if (!$conversationId) {
            $qb->from('NaturaPassMessageBundle:OwnerMessage', 'om')
                ->where('om.read = :state AND om.message = :messageId AND om.owner = :ownerId')
                ->setParameter('state', OwnerMessage::MESSAGE_UNREAD)
                ->setParameter('messageId', $messageId)
                ->setParameter('ownerId', $this->getUser()->getId());
        } else {
            $qb->from('NaturaPassMessageBundle:Message', 'm')
                ->join('NaturaPassMessageBundle:OwnerMessage', 'om')
                ->where(
                    'm.id = om.message AND om.read = :state AND m.conversation = :conversationId AND om.owner = :ownerId'
                )
                ->setParameter('state', OwnerMessage::MESSAGE_UNREAD)
                ->setParameter('conversationId', $conversationId)
                ->setParameter('ownerId', $this->getUser()->getId());
        }

        $tmp = $qb->getQuery()->getResult();
        $tmp = $tmp ? $tmp : array();

        foreach ($tmp as $ownerMessage) {
            $ownerMessage->setRead(OwnerMessage::MESSAGE_READ);
            $manager->persist($ownerMessage);
        }

        $manager->flush();

        return $this->view(array("readCount" => count($tmp)), Codes::HTTP_OK);
    }

    /**
     * Route get received messages of user
     *
     * GET /user/messages?limit=5&offset=0
     *
     * @param Request $request
     * @return \FOS\RestBundle\View\View
     *
     * @View()
     */
    public function getUserMessagesAction(Request $request)
    {
        $this->authorize();

        $limit = $request->query->get('limit', 10);
        $offset = $request->query->get('offset', 0);

        //Get conversations of user
        $conversations = $this->getUser()->getConversations();
        $conversationIds = array();
        $conversationParticipants = array();

        $messages = array();

        if ($conversations) {
            //Push conversation ids to array
            foreach ($conversations as $conversation) {
                $conversationIds[] = $conversation->getId();
            }

            $manager = $this->getDoctrine()->getManager();
            $qb = $manager->createQueryBuilder();

            //Build query to get the messages of conversations in converstaion ids
            $qb->select('m')
                ->from('NaturaPassMessageBundle:Message', 'm')
                ->where('m.conversation IN (:conversationIdArray) AND m.owner <> :userId')
                ->orderBy('m.updated', 'DESC')
                ->setMaxResults($limit)
                ->setFirstResult($offset)
                ->setParameter('conversationIdArray', $conversationIds)
                ->setParameter('userId', $this->getUser()->getId());

            /**
             * @var Message[] $tmp
             */
            $tmp = $qb->getQuery()->getResult();

            foreach ($tmp as $message) {
                /*
                 * Get conversation participants
                 */
                if (!isset($conversationParticipants[$message->getConversation()->getId()])) {
                    $participants = $message->getConversation()->getParticipants();
                    foreach ($participants as $participant) {
                        if ($this->getUser()->getId() != $participant->getId()) {
                            $conversationParticipants[$message->getConversation()->getId()][] = array(
                                'id' => $participant->getId(),
                                'firstname' => $participant->getFirstname(),
                                'lastname' => $participant->getLastname(),
                                'fullname' => $participant->getFullname(),
                                'usertag' => $participant->getUsertag()
                            );
                        }
                    }
                }

                //Build message data
                $messages[] = array(
                    'userId' => $this->getUser()->getId(),
                    'messageId' => $message->getId(),
                    'content' => $message->getContent(),
                    'updated' => $message->getUpdated()->format(\DateTime::ATOM),
                    'created' => $message->getCreated()->format(\DateTime::ATOM),
                    'owner' => array(
                        'ownerId' => $message->getOwner()->getId(),
                        'firstname' => $message->getOwner()->getFirstname(),
                        'lastname' => $message->getOwner()->getLastname(),
                        'fullname' => $message->getOwner()->getFullname(),
                        'usertag' => $message->getOwner()->getUsertag(),
                        'profilepicture' => $message->getOwner()->getProfilePicture() ? $message->getOwner()->getProfilePicture()->getThumb() : $this->getAssetHelper()->getUrl(
                            'img/interface/default-avatar.jpg'
                        ),
                    ),
                    'conversation' => array(
                        'id' => $message->getConversation()->getId(),
                        'updated' => $message->getConversation()->getUpdated()->format(\DateTime::ATOM),
                        'participants' => $conversationParticipants[$message->getConversation()->getId()]
                    )
                );
            }
        }

        return $this->view(array('messages' => $messages));
    }

    /**
     * Route get received messages of user
     *
     * GET /user/conversations
     *
     * @param Request $request
     *
     * @View()
     *
     * @return \FOS\RestBundle\View\View
     */
    public function getUserConversationsAction(Request $request)
    {
        $this->authorize();

        $manager = $this->getDoctrine()->getManager();
        $limit = $request->query->get('limit', 10);
        $offset = $request->query->get('offset', 0);

        //Get conversations of user
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

                    /*
                     * Get conversation participants
                     */
                    $conversationParticipans = array();
                    $participants = $conversation->getParticipants();

                    foreach ($participants as $participant) {
                        if ($this->getUser()->getId() != $participant->getId()) {
                            $conversationParticipans[] = array(
                                'id' => $participant->getId(),
                                'firstname' => $participant->getFirstname(),
                                'lastname' => $participant->getLastname(),
                                'fullname' => $participant->getFullname(),
                                'usertag' => $participant->getUsertag(),
                                'profilepicture' => $participant->getProfilePicture() ? $participant->getProfilePicture()->getThumb() : $this->getAssetHelper()->getUrl(
                                    'img/interface/default-avatar.jpg'
                                ),
                            );
                        }
                    }

                    //Build message data
                    $messages[] = array(
                        'userId' => $this->getUser()->getId(),
                        'messageId' => $message->getId(),
                        'content' => $message->getContent(),
                        'updated' => $message->getUpdated()->format(\DateTime::ATOM),
                        'created' => $message->getCreated()->format(\DateTime::ATOM),
                        'unreadCount' => $unreadCount,
                        'owner' => array(
                            'ownerId' => $message->getOwner()->getId(),
                            'firstname' => $message->getOwner()->getFirstname(),
                            'lastname' => $message->getOwner()->getLastname(),
                            'fullname' => $message->getOwner()->getFullname(),
                            'usertag' => $message->getOwner()->getUsertag(),
                            'profilepicture' => $message->getOwner()->getProfilePicture() ? $message->getOwner()->getProfilePicture()->getThumb() : $this->getAssetHelper()->getUrl(
                                'img/interface/default-avatar.jpg'
                            ),
                        ),
                        'conversation' => array(
                            'id' => $message->getConversation()->getId(),
                            'updated' => $message->getConversation()->getUpdated()->format(\DateTime::ATOM),
                            'participants' => $conversationParticipans,
                        )
                    );
                }
            }
        }

        //Call socket to send message

        return $this->view(array('messages' => $messages));
    }

    /**
     * Retourne les conversation messages
     *
     * GET /conversation/messages?limit=5&offset=0
     *
     * @param Request $request
     * @return \FOS\RestBundle\View\View
     *
     * @View()
     */
    public function getConversationMessagesAction(Request $request)
    {
        $this->authorize();

        $limit = $request->query->get('limit', 10);
        $offset = $request->query->get('offset', 0);
        $updated = $request->query->get('updated', null);
        $messageId = $request->query->get('messageId', 0);
        $reverse = $request->query->get('reverse', false);

        //Get conversation id
        $conversationId = $request->query->get('conversationId', 0);

        $messages = array();

        if ($conversationId) {
            //Get manager entity
            $manager = $this->getDoctrine()->getManager();
            $qb = $manager->createQueryBuilder();

            //Build query to get messages of a conversation
            $qb->select('m')
                ->from('NaturaPassMessageBundle:Message', 'm')
                ->where('m.conversation = :conversationId')
                ->orderBy('m.updated', 'DESC')
                ->setMaxResults($limit)
                ->setFirstResult($offset)
                ->setParameter('conversationId', $conversationId);

            if ($updated && $messageId) {
                $qb->andWhere('m.id <> :messageId AND m.updated <= :updated')
                    ->setParameter("messageId", $messageId)
                    ->setParameter("updated", date("Y-m-d H:i:s", strtotime($updated)));
            }

            $tmp = $qb->getQuery()->getResult();

            foreach ($tmp as $message) {
                //Build messages data
                $messages[] = array(
                    'messageId' => $message->getId(),
                    'content' => $message->getContent(),
                    'updated' => $message->getUpdated()->format(\DateTime::ATOM),
                    'created' => $message->getCreated()->format(\DateTime::ATOM),
                    'owner' => array(
                        'ownerId' => $message->getOwner()->getId(),
                        'firstname' => $message->getOwner()->getFirstname(),
                        'lastname' => $message->getOwner()->getLastname(),
                        'fullname' => $message->getOwner()->getFullname(),
                        'usertag' => $message->getOwner()->getUsertag(),
                        'profilepicture' => $message->getOwner()->getProfilePicture() ? $message->getOwner()->getProfilePicture()->getThumb() : $this->getAssetHelper()->getUrl(
                            'img/interface/default-avatar.jpg'
                        ),
                    )
                );
            }
        } else {
            throw new HttpException(Codes::HTTP_BAD_REQUEST, $this->message('errors.parameters'));
        }

        //Revese messages array
        if ($reverse) {
            $messages = array_reverse($messages);
        }

        return $this->view(array('messages' => $messages));
    }

    /**
     * Retourne les conversation
     *
     * GET /chat/conversation?participantIds=1,2
     *
     * @param Request $request
     * @return \FOS\RestBundle\View\View
     *
     * @View()
     */
    public function getChatConversationAction(Request $request)
    {
        $this->authorize();

        //Get list ids of participants
        $participantIds = urldecode($request->query->get('participantIds', ''));
        $participantIds = explode(",", $participantIds);

        //Sort ids array
        sort($participantIds);

        //Get convensations of user
        $conversations = $this->getUser()->getConversations();

        $conversationParticipanIds = array();
        $conversationCollection = array();

        foreach ($conversations as $conversation) {
            //Get participiants of the conversation
            $participants = $conversation->getParticipants();

            foreach ($participants as $participant) {
                //Push participant id to array
                $conversationParticipanIds[] = $participant->getId();
            }

            //Sort participant ids array
            sort($conversationParticipanIds);

            //Compare two array. If true then return conversation object
            if ($participantIds == $conversationParticipanIds) {
                $conversationCollection = $conversation;
                break;
            }
        }

        return $this->view(array('conversation' => $conversationCollection));
    }

    /**
     * delete a message
     *
     * DELETE /chats/{ID_MESSAGE}/message
     *
     * @param Message $message
     * @return \FOS\RestBundle\View\View
     *
     * @ParamConverter("message", class="NaturaPassMessageBundle:Message")
     */
    public function deleteChatMessageAction(Message $message)
    {
        $this->authorize();
        $this->authorizeMessage($message);

        $conversation = $message->getConversation();
        $manager = $this->getDoctrine()->getManager();
        $manager->remove($message);
        $manager->flush();
        $manager->refresh($conversation);
        if ($conversation->getMessages()->count() == 0) {
            $manager->remove($conversation);
            $manager->flush();
        }

        return $this->view($this->success(), Codes::HTTP_OK);
    }

}

