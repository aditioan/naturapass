<?php

/**
 * Created by PhpStorm.
 * User: vincentvalot
 * Date: 28/07/15
 * Time: 10:34
 */

namespace Api\ApiBundle\Controller\v2\Serialization;

use Admin\SentinelleBundle\Entity\CardLabel;
use NaturaPass\PublicationBundle\Entity\Favorite;
use NaturaPass\PublicationBundle\Entity\FavoriteAttachment;
use NaturaPass\UserBundle\Entity\DeviceDbVersion;
use NaturaPass\UserBundle\Entity\HuntType;
use NaturaPass\UserBundle\Entity\HuntTypeParameter;
use NaturaPass\UserBundle\Entity\PaperMedia;
use NaturaPass\UserBundle\Entity\PaperParameter;
use NaturaPass\UserBundle\Entity\User;
use NaturaPass\UserBundle\Entity\UserAddress;
use NaturaPass\UserBundle\Entity\UserFriend;

/**
 * Class UserSerialization
 * @package Api\ApiBundle\Controller\v2\Serialization
 *
 * @method static serializePapers(array $papers)
 * @method static serializeUsers(array $users, User $connected)
 * @method static serializeUserLesss(array $users, User $connected)
 * @method static serializeUserFullLesss(array $users)
 */
class UserSerialization extends Serialization
{

    /**
     * Serialize an user relation
     *
     * @param User $user
     * @param User $connected
     *
     * @return array|bool
     */
    public static function serializeUserRelation(User $user, $connected = null)
    {
        if ($connected instanceof User && $user->getId() != $connected->getId()) {
            list($way, $friendship) = $connected->hasFriendshipWith($user, array(UserFriend::ASKED, UserFriend::CONFIRMED));

            return array(
                'mutualFriends' => $connected->getMutualFriendsWith($user)->count(),
                'friendship' => $friendship instanceof UserFriend ? array('state' => $friendship->getState(), 'way' => $way) : false
            );
        }

        return null;
    }

    /**
     * Serialize an user
     *
     * @param User $user
     * @param User|null $connected
     * @return array
     */
    public static function serializeUser(User $user, $connected = null)
    {
        $serialize = array(
            'id' => $user->getId(),
            'fullname' => $user->getFullName(),
            'firstname' => $user->getFirstname(),
            'lastname' => $user->getLastname(),
            'usertag' => $user->getUsertag(),
            'courtesy' => $user->getCourtesy(),
            'photo' => $user->getProfilePicture() ? $user->getProfilePicture()->getThumb() : User::DEFAULT_AVATAR,
            'relation' => self::serializeUserRelation($user, $connected),
            'parameters' => array("friend" => (boolean)$user->getParameters()->getFriends())
        );

        return $serialize;
    }

    /**
     * Serialize an user
     *
     * @param User $user
     * @param User|null $connected
     * @return array
     */
    public static function serializeUserLess(User $user, $connected = null)
    {
        $serialize = array(
            'id' => $user->getId(),
            'text' => $user->getFullName(),
        );

        return $serialize;
    }

    /**
     * Serialize an user
     *
     * @param User $user
     * @return array
     */
    public static function serializeUserFullLess(User $user)
    {
        $serialize = array(
            'id' => $user->getId(),
            'fullname' => $user->getFullName(),
            'usertag' => $user->getUsertag(),
            'photo' => $user->getProfilePicture() ? $user->getProfilePicture()->getThumb() : User::DEFAULT_AVATAR,
        );

        return $serialize;
    }

    /**
     * Serialize an user paper
     *
     * @param PaperParameter $paper
     * @return array
     */
    public static function serializePaper(PaperParameter $paper, $title = "Description")
    {
        $serialize = array(
            'id' => $paper->getId(),
            'name' => $paper->getName(),
            'text' => $paper->getText(),
            'type' => $paper->getType(),
            'title' => $title,
            'deletable' => $paper->getDeletable(),
            'medias' => UserSerialization::serializePaperMedias($paper),
        );

        return $serialize;
    }

    /**
     * @param PaperParameter $paper
     * @return array
     */
    public static function serializePaperMedias(PaperParameter $paper)
    {
        $array = array();
        foreach ($paper->getMedias() as $media) {
            $array[] = array(
                'id' => $media->getId(),
                'type' => $media->getType(),
                'path' => $media->getResize()
            );
        }
        return $array;
    }

    /**
     * Serialize an user hunttype
     *
     * @param HuntType $hunttype
     * @return array
     */
    public static function serializeHunttype(HuntType $hunttype, User $connected = null)
    {
        $array = array();
        if (!is_null($connected)) {
            foreach ($connected->getHunttypes() as $hunttype_sub) {
                if ($hunttype_sub->getType() == HuntTypeParameter::TYPE_LIKED) {
                    $array["liked"] = $hunttype_sub->getHunttypes()->contains($hunttype) ? 1 : 0;
                } elseif ($hunttype_sub->getType() == HuntTypeParameter::TYPE_PRACTICED) {
                    $array["practiced"] = $hunttype_sub->getHunttypes()->contains($hunttype) ? 1 : 0;
                }
            }
        }
        $serialize = array(
            'id' => $hunttype->getId(),
            'name' => $hunttype->getName(),
        );
        $serialize = array_merge($serialize, $array);

        return $serialize;
    }

    public static function serializeAddressArraySqlite(UserAddress $address, User $connected)
    {
        return array(
            "c_id" => $address->getId(),
            "c_title" => $address->getTitle(),
            "c_favorite" => $address->isFavorite() ? 1 : 0,
            "c_lat" => $address->getLatitude(),
            "c_lon" => $address->getLongitude(),
            "c_user_id" => $connected->getId(),
        );
        return $array;
    }


    /**
     * Serialize a group sqlite with new Point
     *
     * @return array
     */
    public static function serializeAddressSqliteRefresh($addressIds, UserAddress $address, User $connected, $sqlFormat = true)
    {
//        return SqliteSerialization::serializeSqliteSqlite($addressIds, false, $address, UserSerialization::serializeAddressArraySqlite($address, $connected), "tb_favorite_address", $sqlFormat, false);
        return SqliteSerialization::serializeSqliteReturnArrayData($addressIds, false, $address, UserSerialization::serializeAddressArraySqlite($address, $connected), false);
    }

    /**
     * Serialize a group sqlite with new Point
     *
     * @return array
     */
    public static function serializeAddressSqlite($addressIds, User $connected, &$sendedAddress, $sqlFormat = true)
    {
        $return = array();
        if (!is_array($addressIds)) {
            $addressIds = explode(",", $addressIds);
        }
        $userAddresses = $connected->getAddresses();
        $allIds = array();
        $arrayValues = array();
        foreach ($userAddresses as $address) {
            $allIds[] = $address->getId();
            $val = UserSerialization::serializeAddressSqliteRefresh($addressIds, $address, $connected, $sqlFormat);
            if (!is_null($val)) {
                $arrayValues[] = $val;
            }
        }
        if (!empty($arrayValues)) {
            $return = array_merge($return, SqliteSerialization::serializeSqliteInserOrReplace("tb_favorite_address", $arrayValues));
        }
        $arrayDeleteId = array();
        foreach ($addressIds as $addressId) {
            if (!in_array($addressId, $allIds)) {
                $arrayDeleteId[] = $addressId;
            }
        }
        if (count($arrayDeleteId)) {
            $return[] = "DELETE FROM `tb_favorite_address` WHERE `c_id` IN (" . join(',', $arrayDeleteId) . ") AND `c_user_id` = '" . $connected->getId() . "';";
        }
        $sendedAddress->setIds(implode(",", $allIds));
        return $return;
    }

    public static function serializeFavoriteArraySqlite(Favorite $favorite, User $connected, $hasUpdated = true)
    {
        $tree = CategorySerialization::serializeCategoryTree($favorite->getCategory());
        if ($favorite->getSpecific() == 1 && !is_null($favorite->getAnimal())) {
            $tree[] = $favorite->getAnimal()->getName_fr();
        }
        $tree_default = false;
        if (!is_null($favorite->getCategory())) {
            $checkRight = $favorite->getCategory()->checkRightToSee($connected);
            ($checkRight && (is_null($favorite->getCategory()->getCard()) || is_null($favorite->getCard()) || (!is_null($favorite->getCategory()->getCard()) && !is_null($favorite->getCard()) && $favorite->getCategory()->getCard()->getId() == $favorite->getCard()->getId()))) ? $tree_default = true : "";
        } else {
            $tree_default = true;
        }

        return array(
            "c_id" => $favorite->getId(),
            "c_name" => $favorite->getName(),
            "c_legend" => $favorite->getLegend(),
            "c_color" => (is_null($favorite->getPublicationcolor())) ? "" : $favorite->getPublicationcolor()->getId(),
            "c_category" => (is_null($favorite->getCategory())) ? "" : $favorite->getCategory()->getId(),
            "c_tree" => implode("/", $tree),
            "c_card" => (is_null($favorite->getCard())) ? "" : $favorite->getCard()->getId(),
            "c_animal" => (is_null($favorite->getAnimal())) ? "" : $favorite->getAnimal()->getId(),
            "c_specific" => $favorite->getSpecific(),
            "c_sharing" => (is_null($favorite->getSharing())) ? "" : $favorite->getSharing()->getShare(),
            "c_groups" => UserSerialization::serializeFavoriteSqliteGroup($favorite, $connected),
            "c_shareusers" => UserSerialization::serializeFavoriteSqliteUser($favorite, $connected),
            "c_hunts" => UserSerialization::serializeFavoriteSqliteHunt($favorite, $connected),
            "c_attchments" => UserSerialization::serializeFavoriteSqliteAttachment($favorite),
            "c_updated" => ($hasUpdated) ? $favorite->getLastUpdated($connected)->getTimestamp() : 0,
            "c_user_id" => $connected->getId(),
            "c_default" => $tree_default ? 1 : 0,
        );
    }

    /**
     * Serialize a favorite sqlite hunts
     *
     * @param Favorite $favorite
     * @return array
     */
    public static function serializeFavoriteSqliteAttachment(Favorite $favorite)
    {
        $aArray = array();
        if (!is_null($favorite->getCard())) {
            foreach ($favorite->getCard()->getLabelsVisible() as $label) {
                foreach ($favorite->getAttachments() as $attachment) {
                    if ($attachment->getLabel()->getId() == $label->getId()) {
                        $contentObject = null;
                        $allowContent = $attachment->getLabel()->allowContentType();
                        if ($allowContent) {
                            foreach ($attachment->getLabel()->getContents() as $content) {
                                if ($content->getId() == intval($attachment->getValue())) {
                                    $contentObject = $content;
                                }
                            }
                        }
                        if (!is_null($contentObject)) {
                            $value = $contentObject->getName();
                        } else {
                            $value = $attachment->getValue();
                        }
                        if ($allowContent && (!isset($aArray[$attachment->getLabel()->getName()]) || !is_array($aArray[$attachment->getLabel()->getName()]))) {
                            $aArray[$attachment->getLabel()->getName()] = array();
                        }
                        if ($allowContent) {
                            $aArray[$attachment->getLabel()->getName()][] = $value;
                        } else {
                            $aArray[$attachment->getLabel()->getName()] = $value;
                        }
                    }
                }
                if (!isset($aArray[$label->getName()])) {
                    $aArray[$label->getName()] = (($label->getType() == CardLabel::TYPE_INT || $label->getType() == CardLabel::TYPE_FLOAT) ? "0" : "");
                }
            }
        }
        $aReturn = array();
        foreach ($aArray as $name => $object) {
            if (is_array($object)) {
                $aReturn[] = array("label" => $name, "values" => $object);
            } else {
                $aReturn[] = array("label" => $name, "value" => $object);
            }
        }

//        $arrayAttachment = array();
//        if (!is_null($favorite->getCard())) {
//            foreach ($favorite->getCard()->getLabelsVisible() as $label) {
//                $favValue = null;
//                foreach ($favorite->getAttachments() as $attachement) {
//                    if ($attachement->getLabel()->getId() == $label->getId()) {
//                        $favValue = $attachement->getValue();
//                    }
//                }
//                $arrayAttachment[] = array(
//                    "label_id" => $label->getId(),
//                    "label_name" => $label->getName(),
//                    'value' => (!is_null($favValue) ? $favValue : (($label->getType() == CardLabel::TYPE_INT || $label->getType() == CardLabel::TYPE_FLOAT) ? "0" : "")),
//                );
//            }
//        }

        return json_encode($aReturn);
    }

    /**
     * Serialize a favorite sqlite hunts
     *
     * @param Favorite $favorite
     * @param User $connected
     * @return array
     */
    public static function serializeFavoriteSqliteHunt(Favorite $favorite, User $connected)
    {
        $arrayHunt = array();
        foreach ($favorite->getHunts() as $hunt) {
            $arrayHunt[] = $hunt->getId();
        }
        return join(",", $arrayHunt);
    }

    /**
     * Serialize a favorite sqlite groups
     *
     * @param Favorite $favorite
     * @param User $connected
     * @return array
     */
    public static function serializeFavoriteSqliteGroup(Favorite $favorite, User $connected)
    {
        $arrayGroup = array();
        foreach ($favorite->getGroups() as $group) {
            $arrayGroup[] = $group->getId();
        }
        return join(",", $arrayGroup);
    }

    /**
     * Serialize a favorite sqlite users
     *
     * @param Favorite $favorite
     * @param User $connected
     * @return array
     */
    public static function serializeFavoriteSqliteUser(Favorite $favorite, User $connected)
    {
        $arrayUser = array();
        foreach ($favorite->getShareusers() as $user) {
            $arrayUser[] = $user->getId()."_".$user->getFullName();
        }
        return join(",", $arrayUser);
    }


    /**
     * Serialize a favorite sqlite
     *
     * @return array
     */
    public static function serializeFavoriteSqliteRefresh($favoriteIds, $updated, Favorite $favorite, User $connected, $hasUpdated = true)
    {
//        return SqliteSerialization::serializeSqliteSqlite($favoriteIds, $updated, $favorite, UserSerialization::serializeFavoriteArraySqlite($favorite, $connected), "tb_favorite", $sqlFormat);
        return SqliteSerialization::serializeSqliteReturnArrayData($favoriteIds, $updated, $favorite, UserSerialization::serializeFavoriteArraySqlite($favorite, $connected, $hasUpdated));
    }

    /**
     * Serialize a favorite sqlite
     *
     * @return array
     */
    public static function serializeFavoriteSqlite($favoriteIds, $updated, User $connected, &$sendedFavorite, $sqlFormat = true)
    {
        $return = array();
        if (!is_array($favoriteIds)) {
            $favoriteIds = explode(",", $favoriteIds);
        }
        $userFavorites = $connected->getFavoritesOwner();
        $allIds = array();
        $arrayValues = array();
        foreach ($userFavorites as $favorite) {
            $allIds[] = $favorite->getId();
            $val = UserSerialization::serializeFavoriteSqliteRefresh($favoriteIds, $updated, $favorite, $connected, $sqlFormat);
            if (!is_null($val)) {
                $arrayValues[] = $val;
            }
        }
        if (!empty($arrayValues)) {
            $return = array_merge($return, SqliteSerialization::serializeSqliteInserOrReplace("tb_favorite", $arrayValues));
        }
        $arrayDeleteId = array();
        foreach ($favoriteIds as $favoriteId) {
            if (!in_array($favoriteId, $allIds)) {
                $arrayDeleteId[] = $favoriteId;
            }
        }
        if (count($arrayDeleteId)) {
            $return[] = "DELETE FROM `tb_favorite` WHERE `c_id` IN (" . join(',', $arrayDeleteId) . ") AND `c_user_id` = '" . $connected->getId() . "';";
        }
        $sendedFavorite->setIds(implode(",", $allIds));
        return $return;
    }

    /**
     * Serialize a favorite sqlite
     *
     * @return array
     */
    public static function serializeVersionSqlite(DeviceDbVersion $version)
    {
        return array(
            "version" => $version->getVersion(),
            "sqlite" => explode(";", $version->getSqlite())
        );
    }

}

