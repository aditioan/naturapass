<?php

/**
 * Created by PhpStorm.
 * User: vincentvalot
 * Date: 29/07/15
 * Time: 16:52
 */

namespace Api\ApiBundle\Controller\v2\Serialization;

use NaturaPass\LoungeBundle\Entity\Lounge;
use NaturaPass\LoungeBundle\Entity\LoungeMedia;
use NaturaPass\LoungeBundle\Entity\LoungeMessage;
use NaturaPass\LoungeBundle\Entity\LoungeNotMember;
use NaturaPass\LoungeBundle\Entity\LoungeUser;
use NaturaPass\MainBundle\Entity\Geolocation;
use NaturaPass\UserBundle\Entity\User;

/**
 * Class HuntSerialization
 * @package Api\ApiBundle\Controller\v2\Serialization
 *
 * @method static serializeHunts(array $hunt, $connected = null)
 * @method static serializeHuntAllows(array $hunt)
 * @method static serializeHuntMessages(array $messages)
 * @method static serializeHuntLesss(array $hunt, $connected = null)
 * @method static serializeHuntSubscribers(array $subscribers, $connected = null)
 * @method static serializeHuntNotMembers(array $notMembers)
 */
class HuntSerialization extends Serialization
{

    /**
     * @param Lounge $hunt
     * @param User|null $connected
     *
     * @return array
     */
    public static function serializeHunt(Lounge $hunt, $connected = null, $force_updated = false)
    {
        if ($connected instanceof User) {
            $subscriber = $hunt->isSubscriber(
                $connected, array(
                    LoungeUser::ACCESS_DEFAULT,
                    LoungeUser::ACCESS_INVITED,
                    LoungeUser::ACCESS_RESTRICTED,
                    LoungeUser::ACCESS_ADMIN
                )
            );
        } else {
            $subscriber = null;
        }

        $toSend = array(
            'id' => $hunt->getId(),
            'owner' => UserSerialization::serializeUser($hunt->getOwner()),
            'geolocation' => $hunt->getGeolocation() ? 1 : 0,
            'meeting' => array(
                'address' => $hunt->getMeetingAddress() instanceof Geolocation ? MainSerialization::serializeGeolocation($hunt->getMeetingAddress()) : false,
                'date_begin' => (!is_null($hunt->getMeetingDate())) ? $hunt->getMeetingDate()->format(\DateTime::ATOM) : "",
                'date_end' => (!is_null($hunt->getEndDate())) ? $hunt->getEndDate()->format(\DateTime::ATOM) : "",
            ),
            'allow_add' => $hunt->getAllowAdd() ? 1 : 0,
            'allow_show' => $hunt->getAllowShow() ? 1 : 0,
            'allow_add_chat' => $hunt->getAllowAddChat() ? 1 : 0,
            'allow_show_chat' => $hunt->getAllowShowChat() ? 1 : 0,
            'access' => $hunt->getAccess(),
            'name' => $hunt->getName(),
            'loungetag' => $hunt->getLoungeTag(),
            'description' => $hunt->getDescription(),
            'nbSubscribers' => $hunt->getSubscribers(array(LoungeUser::ACCESS_DEFAULT, LoungeUser::ACCESS_ADMIN))->count(),
            'photo' => ($hunt->getPhoto() instanceof LoungeMedia ? $hunt->getPhoto()->getThumb() : '/img/interface/default-media.jpg'),
            'connected' => $subscriber instanceof LoungeUser ? self::serializeHuntSubscriber($subscriber) : false,
            'updated' => $force_updated ? 0 : $hunt->getLastUpdated($connected)->format(\DateTime::ATOM),
        );
        if ($connected instanceof User) {
            if ($hunt->isLiveActive($connected)) {
                $toSend = array_merge($toSend, array("live" => $hunt->getId()));
            }
        }

        return $toSend;
    }

    /**
     * @param Lounge $hunt
     *
     * @return array
     */
    public static function serializeHuntAllow(Lounge $hunt)
    {
        $toSend = array(
            'id' => $hunt->getId(),
            'allow_add' => $hunt->getAllowAdd() ? 1 : 0,
            'allow_show' => $hunt->getAllowShow() ? 1 : 0,
            'allow_add_chat' => $hunt->getAllowAddChat() ? 1 : 0,
            'allow_show_chat' => $hunt->getAllowShowChat() ? 1 : 0,
        );

        return $toSend;
    }

    /**
     * @param LoungeMessage $message
     *
     * @return array
     */
    public static function serializeHuntMessage(LoungeMessage $message)
    {
        $subscriber = $message->getLounge()->isSubscriber(
            $message->getOwner(), array(LoungeUser::ACCESS_DEFAULT, LoungeUser::ACCESS_INVITED, LoungeUser::ACCESS_RESTRICTED, LoungeUser::ACCESS_ADMIN)
        );

        return array(
            'id' => $message->getId(),
            'content' => $message->getContent(),
            'created' => $message->getCreated()->format(\DateTime::ATOM),
            'owner' => array(
                'id' => $message->getOwner()->getId(),
                'isAdmin' => $subscriber ? $subscriber->getAccess() === LoungeUser::ACCESS_ADMIN : false,
                'fullname' => $message->getOwner()->getFullname(),
                'profilepicture' => $message->getOwner()->getProfilePicture() ? $message->getOwner()->getProfilePicture()->getWebPath() : 'img/default-avatar.jpg',
                'usertag' => $message->getOwner()->getUsertag()
            ),
        );
    }

    /**
     * @param Lounge $hunt
     * @param User|null $connected
     *
     * @return array
     */
    public static function serializeHuntLess(Lounge $hunt, $connected = null)
    {
        return array(
            'id' => $hunt->getId(),
            'name' => $hunt->getName(),
            'loungetag' => $hunt->getLoungeTag(),
            'photo' => ($hunt->getPhoto() instanceof LoungeMedia ? $hunt->getPhoto()->getThumb() : '/img/interface/default-media.jpg'),
        );
    }

    /**
     * @param LoungeUser $subscriber
     * @param User|null $connected
     * @return array
     */
    public static function serializeHuntSubscriber(LoungeUser $subscriber, $connected = null)
    {
        return array(
            'user' => UserSerialization::serializeUser($subscriber->getUser(), $connected),
            'quiet' => $subscriber->getQuiet() ? 1 : 0,
            'geolocation' => $subscriber->getGeolocation(),
            'publicComment' => $subscriber->getPublicComment(),
            'privateComment' =>
                $subscriber->getAccess() === LoungeUser::ACCESS_ADMIN ? $subscriber->getPrivateComment() : false,
            'participation' => $subscriber->getParticipation(),
            'access' => $subscriber->getAccess(),
        );
    }

    public static function serializeHuntNotMember(LoungeNotMember $loungeNotMember)
    {
        return array(
            'id' => $loungeNotMember->getId(),
            'fullname' => $loungeNotMember->getFullName(),
            'firstname' => $loungeNotMember->getFirstname(),
            'lastname' => $loungeNotMember->getLastname(),
            'publicComment' => $loungeNotMember->getPublicComment(),
            'privateComment' => $loungeNotMember->getPrivateComment(),
            'participation' => $loungeNotMember->getParticipation(),
        );
    }

    public static function serializeHuntArraySqlite(Lounge $hunt, User $connected, $force_updated = false)
    {
        $isAdmin = $hunt->getOwner()->getId() == $connected->getId() ? true : false;
        return array(
            "c_id" => $hunt->getId(),
            "c_name" => $hunt->getName(),
            "c_description" => $hunt->getDescription(),
            "c_access" => $hunt->getAccess(),
            "c_admin" => $isAdmin == true ? 1 : $hunt->isAdmin($connected) ? 1 : 0,
            "c_allow_add" => $hunt->getAllowAdd() ? 1 : 0,
            "c_allow_show" => $hunt->getAllowShow() ? 1 : 0,
            "c_allow_chat_add" => $hunt->getAllowAddChat() ? 1 : 0,
            "c_allow_chat_show" => $hunt->getAllowShowChat() ? 1 : 0,
            "c_nb_participant" => $hunt->getNbParticipants(),
            "c_start_date" => (is_null($hunt->getMeetingDate())) ? "" : $hunt->getMeetingDate()->getTimestamp(),
            "c_end_date" => (is_null($hunt->getEndDate())) ? "" : $hunt->getEndDate()->getTimestamp(),
            "c_meeting_address" => (is_null($hunt->getMeetingAddress())) ? "" : $hunt->getMeetingAddress()->getAddress(),
            "c_meeting_lat" => (is_null($hunt->getMeetingAddress())) ? "" : $hunt->getMeetingAddress()->getLatitude(),
            "c_meeting_lon" => (is_null($hunt->getMeetingAddress())) ? "" : $hunt->getMeetingAddress()->getLongitude(),
            "c_updated" => $force_updated ? 0 : $hunt->getLastUpdated($connected)->getTimestamp(),
            "c_user_id" => $connected->getId(),
        );
    }


    /**
     * Serialize a hunt sqlite
     *
     * @return array
     */
    public static function serializeHuntSqliteRefresh($huntIds, $updated, Lounge $hunt, User $connected, $force_updated = false)
    {
//        return SqliteSerialization::serializeSqliteSqlite($huntIds, $updated, $hunt, HuntSerialization::serializeHuntArraySqlite($hunt, $connected), "tb_hunt", $sqlFormat);
        return SqliteSerialization::serializeSqliteReturnArrayData($huntIds, $updated, $hunt, HuntSerialization::serializeHuntArraySqlite($hunt, $connected, $force_updated));
    }

    /**
     * Serialize a hunt sqlite
     *
     * @return array
     */
    public static function serializeHuntSqlite($huntIds, $updated, User $connected, &$sendedHunt, $sqlFormat = true)
    {
        $return = array();
        if (!is_array($huntIds)) {
            $huntIds = explode(",", $huntIds);
        }
        $userHunts = $connected->getAllHunts();
        $allIds = array();
        $arrayValues = array();
        foreach ($userHunts as $hunt) {
            $allIds[] = $hunt->getId();
            $val = HuntSerialization::serializeHuntSqliteRefresh($huntIds, $updated, $hunt, $connected);
            if (!is_null($val)) {
                $arrayValues[] = $val;
            }
        }
        if (!empty($arrayValues)) {
            $return = array_merge($return, SqliteSerialization::serializeSqliteInserOrReplace("tb_hunt", $arrayValues));
        }
        $arrayDeleteId = array();
        foreach ($huntIds as $huntId) {
            if (!in_array($huntId, $allIds)) {
                $arrayDeleteId[] = $huntId;
            }
        }
        if (count($arrayDeleteId)) {
            $return[] = "DELETE FROM `tb_hunt` WHERE `c_id` IN (" . join(',', $arrayDeleteId) . ") AND `c_user_id` = '" . $connected->getId() . "';";
        }
        $sendedHunt->setIds(implode(",", $allIds));
        return $return;
    }

    /**
     * Serialize all hunt sqlite
     *
     * @return array
     */
    public static function serializeAllHuntSqlite($hunts, User $connected)
    {
        $return = array();
        $arrayValues = array();
        foreach ($hunts as $hunt) {
            $val = HuntSerialization::serializeAllHuntArraySqlite($hunt, $connected);
            if (!is_null($val)) {
                $arrayValues[] = $val;
            }
        }

        if (!empty($arrayValues)) {
            $return = array_merge($return, SqliteSerialization::serializeSqliteInserOrReplace("tb_agenda", $arrayValues));
        }
        return $return;
    }

    /**
     * Serialize a hunt sqlite
     *
     * @return array
     */
    public static function serializeAllHuntSqliteRefresh(Lounge $hunt, User $connected)
    {
        return HuntSerialization::serializeAllHuntArraySqlite($hunt, $connected);
    }

    public static function serializeAllHuntArraySqlite(Lounge $hunt, User $connected)
    {
        return array(
            "c_id" => $hunt->getId(),
            "c_name" => $hunt->getName(),
            "c_description" => $hunt->getDescription(),
            "c_access" => $hunt->getAccess(),
            "c_admin" => $hunt->isAdmin($connected) ? 1 : 0,
            "c_allow_add" => $hunt->getAllowAdd() ? 1 : 0,
            "c_allow_show" => $hunt->getAllowShow() ? 1 : 0,
            "c_allow_chat_add" => $hunt->getAllowAddChat() ? 1 : 0,
            "c_allow_chat_show" => $hunt->getAllowShowChat() ? 1 : 0,
            "c_nb_participant" => $hunt->getNbParticipants(),
            "c_start_date" => (is_null($hunt->getMeetingDate())) ? "" : $hunt->getMeetingDate()->getTimestamp(),
            "c_end_date" => (is_null($hunt->getEndDate())) ? "" : $hunt->getEndDate()->getTimestamp(),
            "c_meeting_address" => (is_null($hunt->getMeetingAddress())) ? "" : $hunt->getMeetingAddress()->getAddress(),
            "c_meeting_lat" => (is_null($hunt->getMeetingAddress())) ? "" : $hunt->getMeetingAddress()->getLatitude(),
            "c_meeting_lon" => (is_null($hunt->getMeetingAddress())) ? "" : $hunt->getMeetingAddress()->getLongitude(),
            "c_updated" => $hunt->getLastUpdated($connected)->getTimestamp(),
            "c_user_id" => $connected->getId(),
            "c_photo" => ($hunt->getPhoto() instanceof LoungeMedia ? $hunt->getPhoto()->getThumb() : '/img/interface/default-media.jpg'),
            "c_subscribers" => $hunt->getSubscriberAccess($connected)?$hunt->getSubscriberAccess($connected):"-1",
        );
    }

}