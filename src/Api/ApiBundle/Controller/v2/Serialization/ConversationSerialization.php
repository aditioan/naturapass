<?php

/**
 * Created by PhpStorm.
 * User: vincentvalot
 * Date: 28/07/15
 * Time: 10:34
 */

namespace Api\ApiBundle\Controller\v2\Serialization;

use NaturaPass\GroupBundle\Entity\Group;
use NaturaPass\LoungeBundle\Entity\Lounge;
use NaturaPass\MessageBundle\Entity\Message;

/**
 * Class ConversationSerialization
 * @package Api\ApiBundle\Controller\v2\Serialization
 *
 * @method static serializeMessages(array $messages, $unreadCount, $participants, $connected)
 * @method static serializeMessageHunts(array $hunts, $connected)
 * @method static serializeMessageGroups(array $groups, $connected)
 */
class ConversationSerialization extends Serialization
{

    /**
     * Serialize a message
     *
     * @param Message $message
     * @param int $unreadCount
     * @param array $participants
     * @param User $connected
     *
     * @return array|bool
     */
    public static function serializeMessage(Message $message, $unreadCount, $participants, $connected = null)
    {
        return array(
            'userId' => $connected->getId(),
            'messageId' => $message->getId(),
            'content' => $message->getContent(),
            'updated' => $message->getUpdated()->format(\DateTime::ATOM),
            'created' => $message->getCreated()->format(\DateTime::ATOM),
            'unreadCount' => $unreadCount,
            'owner' => UserSerialization::serializeUser($message->getOwner()),
            'conversation' => array(
                'id' => $message->getConversation()->getId(),
                'updated' => $message->getConversation()->getUpdated()->format(\DateTime::ATOM),
                'participants' => UserSerialization::serializeUsers($participants),
            )
        );
    }

    /**
     * Serialize a message of the hunt
     *
     * @param Lounge $hunt
     * @param User $connected
     *
     * @return array|bool
     */
    public static function serializeMessageHunt(Lounge $hunt, $connected)
    {
        $array = array(
            'userId' => $connected->getId(),
            'hunt' => HuntSerialization::serializeHunt($hunt, $connected),
        );
        $message = $hunt->getLastMessage();
        if (!is_null($message)) {
            $array = array_merge(
                $array,
                array(
                    'messageId' => $message->getId(),
                    'content' => $message->getContent(),
                    'created' => $message->getCreated()->format(\DateTime::ATOM),
                    'owner' => UserSerialization::serializeUser($message->getOwner())
                )
            );
        }
        return $array;
    }

    /**
     * Serialize a message of the group
     *
     * @param Group $group
     * @param User $connected
     *
     * @return array|bool
     */
    public static function serializeMessageGroup(Group $group, $connected)
    {
        $array = array(
            'userId' => $connected->getId(),
            'group' => GroupSerialization::serializeGroup($group, $connected),
        );
        $message = $group->getLastMessage();
        if (!is_null($message)) {
            $array = array_merge(
                $array,
                array(
                    'messageId' => $message->getId(),
                    'content' => $message->getContent(),
                    'created' => $message->getCreated()->format(\DateTime::ATOM),
                    'owner' => UserSerialization::serializeUser($message->getOwner())
                )
            );
        }
        return $array;
    }


}
