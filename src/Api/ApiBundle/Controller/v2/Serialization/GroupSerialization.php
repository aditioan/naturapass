<?php

/**
 * Created by PhpStorm.
 * User: vincentvalot
 * Date: 28/07/15
 * Time: 10:44
 */

namespace Api\ApiBundle\Controller\v2\Serialization;

use NaturaPass\GroupBundle\Entity\Group;
use NaturaPass\GroupBundle\Entity\GroupMessage;
use NaturaPass\GroupBundle\Entity\GroupUser;
use NaturaPass\UserBundle\Entity\User;

/**
 * Class GroupSerialization
 * @package Api\ApiBundle\Controller\v2\Serialization
 *
 * @method static serializeGroups(array $groups, User $connected)
 * @method static serializeGroupLesss(array $groups, User $connected)
 * @method static serializeGroupSubscribers(array $subscribers, User $connected, $complet)
 * @method static serializeGroupMessages(array $message)
 * @method static serializeGroupSearchs(array $groups)
 */
class GroupSerialization extends Serialization
{

    /**
     * @param Group $group
     * @param User $connected
     * @return array
     */
    public static function serializeGroup(Group $group, User $connected, $force_update = false)
    {
        $subscriber = $group->getSubscriber($connected);

        return array(
            'id' => $group->getId(),
            'owner' => UserSerialization::serializeUser($group->getOwner(), $connected),
            'connected' => $subscriber instanceof GroupUser ? self::serializeGroupSubscriber($subscriber, $connected) : false,
            'access' => $group->getAccess(),
            'name' => $group->getName(),
            'tag' => $group->getGrouptag(),
//            "allow_add" => $group->checkAllowAdd($connected) ? 1 : 0,
            "allow_add" => $group->getAllowAdd() ? 1 : 0,
            "allow_show" => $group->getAllowShow() ? 1 : 0,
            'allow_add_chat' => $group->getAllowAddChat() ? 1 : 0,
            'allow_show_chat' => $group->getAllowShowChat() ? 1 : 0,
            'description' => $group->getDescription(),
            'nbSubscribers' => $group->getSubscribers(array(GroupUser::ACCESS_DEFAULT, GroupUser::ACCESS_ADMIN))->count(),
            'nbAdmins' => $group->getSubscribers(array(GroupUser::ACCESS_ADMIN))->count(),
            'nbPending' => $group->getSubscribers(array(GroupUser::ACCESS_RESTRICTED))->count(),
            'photo' => $group->getPhoto() ? $group->getPhoto()->getThumb() : '/img/interface/default-media.jpg',
            'hunts' => HuntSerialization::serializeHunts($group->getLounges(), $connected),
            'updated' => $force_update ? 0 : $group->getLastUpdated($connected)->format(\DateTime::ATOM),
        );
    }

    /**
     * @param Group $group
     * @param User $connected
     * @return array
     */
    public static function serializeGroupnews(Group $group, User $connected, $force_update = false)
    {
        $subscriber = $group->getSubscriber($connected);
        return array(
            'id' => $group->getId(),
            'owner' => UserSerialization::serializeUser($group->getOwner(), $connected),
            'connected' => $subscriber instanceof GroupUser ? self::serializeGroupSubscriber($subscriber, $connected) : false,
            'access' => $group->getAccess(),
            'name' => $group->getName(),
            'tag' => $group->getGrouptag(),
//            "allow_add" => $group->checkAllowAdd($connected) ? 1 : 0,
            "allow_add" => $group->getAllowAdd() ? 1 : 0,
            "allow_show" => $group->getAllowShow() ? 1 : 0,
            'allow_add_chat' => $group->getAllowAddChat() ? 1 : 0,
            'allow_show_chat' => $group->getAllowShowChat() ? 1 : 0,
            'description' => $group->getDescription(),
            'nbSubscribers' => $group->getSubscribers(array(GroupUser::ACCESS_DEFAULT, GroupUser::ACCESS_ADMIN))->count(),
            'nbAdmins' => $group->getSubscribers(array(GroupUser::ACCESS_ADMIN))->count(),
            'nbPending' => $group->getSubscribers(array(GroupUser::ACCESS_RESTRICTED))->count(),
            'photo' => $group->getPhoto() ? $group->getPhoto()->getThumb() : '/img/interface/default-media.jpg',
            'hunts' => HuntSerialization::serializeHunts($group->getLounges(), $connected),
            'updated' => $force_update ? 0 : $group->getLastUpdated($connected)->format(\DateTime::ATOM),
        );
    }

    /**
     * @param Group $group
     *
     * @return array
     */
    public static function serializeGroupAllow(Group $group)
    {
        $toSend = array(
            'id' => $group->getId(),
            'allow_add' => $group->getAllowAdd() ? 1 : 0,
            'allow_show' => $group->getAllowShow() ? 1 : 0,
            'allow_add_chat' => $group->getAllowAddChat() ? 1 : 0,
            'allow_show_chat' => $group->getAllowShowChat() ? 1 : 0,
        );

        return $toSend;
    }

    /**
     * @param Group $group
     * @param User $connected
     * @return array
     */
    public static function serializeGroupLess(Group $group, User $connected)
    {
        return array(
            'id' => $group->getId(),
            'name' => $group->getName(),
            'tag' => $group->getGrouptag(),
            'photo' => $group->getPhoto() ? $group->getPhoto()->getThumb() : '/img/interface/default-media.jpg',
        );
    }

    /**
     * @param GroupUser $subscriber
     * @param User $connected
     *
     * @return array
     */
    public static function serializeGroupSubscriber(GroupUser $subscriber, User $connected, $complet = false)
    {
        $array = array(
            'user' => UserSerialization::serializeUser($subscriber->getUser(), $connected),
            'mailable' => $subscriber->getMailable(),
            'access' => $subscriber->getAccess(),
        );
        if ($complet) {
            $array["group"] = GroupSerialization::serializeGroup($subscriber->getGroup(), $connected);
        }
        return $array;
    }

    /**
     * @param GroupMessage $message
     *
     * @return array
     */
    public static function serializeGroupMessage(GroupMessage $message)
    {
        $subscriber = $message->getGroup()->isSubscriber(
            $message->getOwner(), array(GroupUser::ACCESS_DEFAULT, GroupUser::ACCESS_INVITED, GroupUser::ACCESS_RESTRICTED, GroupUser::ACCESS_ADMIN)
        );
        return array(
            'id' => $message->getId(),
            'content' => $message->getContent(),
            'created' => $message->getCreated()->format(\DateTime::ATOM),
            'owner' => array(
                'id' => $message->getOwner()->getId(),
                'isAdmin' => $subscriber ? $subscriber->getAccess() === GroupUser::ACCESS_ADMIN : false,
                'fullname' => $message->getOwner()->getFullname(),
                'profilepicture' => $message->getOwner()->getProfilePicture() ? $message->getOwner()->getProfilePicture()->getWebPath() : '/img/default-avatar.jpg',
                'usertag' => $message->getOwner()->getUsertag()
            ),
        );
    }

    /**
     * Serialize a group search
     *
     * @param Group $group
     *
     * @return array|bool
     */
    public static function serializeGroupSearch(Group $group)
    {
        return array(
            'id' => $group->getId(),
            'text' => $group->getName()
        );
    }

    public static function serializeGroupArraySqlite(Group $group, User $connected, $force_updated = false)
    {
        $isAdmin = $group->getOwner()->getId() == $connected->getId() ? true : false;
        return array(
            "c_id" => $group->getId(),
            "c_name" => $group->getName(),
            "c_description" => $group->getDescription(),
            "c_access" => $group->getAccess(),
            "c_allow_add" => $group->getAllowAdd() ? 1 : 0,
            "c_allow_show" => $group->getAllowShow() ? 1 : 0,
            "c_allow_chat_add" => $group->getAllowAddChat() ? 1 : 0,
            "c_allow_chat_show" => $group->getAllowShowChat() ? 1 : 0,
            "c_admin" => $isAdmin == true ? 1 : $group->isAdmin($connected) ? 1 : 0,
            "c_nb_member" => $group->getSubscribers(array(GroupUser::ACCESS_DEFAULT, GroupUser::ACCESS_ADMIN))->count(),
            "c_updated" => $force_updated ? 0 : $group->getLastUpdated($connected)->getTimestamp(),
            "c_user_id" => $connected->getId(),
        );
    }


    /**
     * Serialize a group sqlite with new Point
     *
     * @return array
     */
    public static function serializeGroupSqliteRefresh($groupIds, $updated, Group $group, User $connected, $force_updated = false)
    {
        return SqliteSerialization::serializeSqliteReturnArrayData($groupIds, $updated, $group, GroupSerialization::serializeGroupArraySqlite($group, $connected, $force_updated));
    }


    /**
     * Serialize a group sqlite with new Point
     *
     * @return array
     */
    public static function serializeGroupSqlite($groupIds, $updated, User $connected, &$sendedGroup, $sqlFormat = true)
    {
        $return = array();
        if (!is_array($groupIds)) {
            $groupIds = explode(",", $groupIds);
        }
        $userGroups = $connected->getAllGroups();
        $allIds = array();
        $arrayValues = array();
        foreach ($userGroups as $group) {
            $allIds[] = $group->getId();
            $val = GroupSerialization::serializeGroupSqliteRefresh($groupIds, $updated, $group, $connected);
            if (!is_null($val)) {
                $arrayValues[] = $val;
            }
        }
        if (!empty($arrayValues)) {
            $return = array_merge($return, SqliteSerialization::serializeSqliteInserOrReplace("tb_group", $arrayValues));
        }
        if (!is_null($sendedGroup)) {
            $arrayDeleteId = array();
            foreach ($groupIds as $groupId) {
                if (!in_array($groupId, $allIds)) {
                    $arrayDeleteId[] = $groupId;
                }
            }
            if (count($arrayDeleteId)) {
                $return[] = "DELETE FROM `tb_group` WHERE `c_id` IN (" . join(',', $arrayDeleteId) . ") AND `c_user_id` = '" . $connected->getId() . "';";
            }
        }
        $sendedGroup->setIds(implode(",", $allIds));
        return $return;
    }
}
