<?php

/**
 * Created by PhpStorm.
 * User: vincentvalot
 * Date: 28/07/15
 * Time: 10:31
 */

namespace Api\ApiBundle\Controller\v2\Serialization;

use Doctrine\ORM\Tools\Pagination\Paginator;
use NaturaPass\MainBundle\Entity\Geolocation;
use NaturaPass\MainBundle\Entity\Sharing;
use NaturaPass\ObservationBundle\Entity\Observation;
use NaturaPass\PublicationBundle\Entity\Publication;
use NaturaPass\PublicationBundle\Entity\PublicationAction;
use NaturaPass\PublicationBundle\Entity\PublicationComment;
use NaturaPass\PublicationBundle\Entity\PublicationMedia;
use NaturaPass\PublicationBundle\Entity\PublicationColor;
use NaturaPass\UserBundle\Entity\User;
use Api\ApiBundle\Controller\v2\ApiRestController;
use NaturaPass\MediaBundle\Entity\Media;
use NaturaPass\UserBundle\Entity\UserFriend;
use Symfony\Component\Validator\Constraints\DateTime;

/**
 * Class PublicationSerialization
 * @package Api\ApiBundle\Controller\v2\Serialization
 *
 * @method static serializePublicationComments(array $comments, User $connected)
 * @method static serializePublicationLikes(array $likes, User $connected)
 * @method static serializePublications(array $publications, User $connected)
 * @method static serializePublicationSqlites(array $publications, User $connected)
 */
class PublicationSerialization extends Serialization
{

    /**
     * @param PublicationAction $action
     * @param User $connected
     * @return array
     */
    public static function serializePublicationLike(PublicationAction $action, User $connected)
    {
        return UserSerialization::serializeUser($action->getUser(), $connected);
    }

    /**
     * @param PublicationComment $comment
     * @param User $connected
     * @return array
     */
    public static function serializePublicationComment(PublicationComment $comment, User $connected)
    {
        return array(
            'id' => $comment->getId(),
            'owner' => UserSerialization::serializeUser($comment->getOwner(), $connected),
            'content' => $comment->getContent(),
	    'content1' => $comment->getContent(),
            'created' => $comment->getCreated()->format(\DateTime::ATOM),
            'likes' => $comment->getActions(PublicationAction::STATE_LIKE)->count(),
            'isUserLike' => $comment->isAction($connected, PublicationAction::STATE_LIKE) > 0 ? true : false,
        );
    }

    /**
     * Serialize a publication
     *
     * @param Publication $publication
     * @param User $connected
     * @return array
     */
    public static function serializePublication(Publication $publication, User $connected, $force_updated = false)
    {

        $tmp = $publication->getComments();
        if (count($publication->getComments()) > 4) {
            $tmp = $tmp->slice(count($publication->getComments()) - 4, 4);
        }
        return array(
            'id' => $publication->getId(),
            'owner' => UserSerialization::serializeUser($publication->getOwner(), $connected),
            'content' => $publication->getContent(),
		'content1' => $publication->getContent(),
            'media' =>
                $publication->getMedia() instanceof PublicationMedia ? MainSerialization::serializeMedia($publication->getMedia()) : false,
            'likes' => $publication->getActions(PublicationAction::STATE_LIKE)->count(),
            'isUserLike' => $publication->isAction($connected, PublicationAction::STATE_LIKE) > 0 ? true : false,
            'comments' => array(
                'total' => $publication->getComments()->count(),
                'unloaded' => $publication->getComments()->count() - count($tmp),
                'data' => self::serializePublicationComments($tmp, $connected)
            ),
            'created' => $publication->getCreated()->format(\DateTime::ATOM),
            'updated' => $publication->getLastUpdated($connected)->format(\DateTime::ATOM),
            "c_updated" => $force_updated ? 0 : $publication->getLastUpdated($connected)->getTimestamp(),
            'legend' => $publication->getLegend(),
            'sharing' => MainSerialization::serializeSharing($publication->getSharing(), $connected),
            'groups' => GroupSerialization::serializeGroups($publication->getGroups(), $connected),
            'shareusers' => UserSerialization::serializeUsers($publication->getShareusers(), $connected), //vietlh I comment bcz it bug
            'hunts' => HuntSerialization::serializeHunts($publication->getHunts(), $connected),
            'date' => $publication->getDate(),
            'reported' => $publication->isReported($connected),
            'geolocation' => $publication->getGeolocation() instanceof Geolocation ? MainSerialization::serializeGeolocation($publication->getGeolocation()) : false,
            'observations' => ObservationSerialization::serializeObservations($publication->getObservations()),
            'markers' => PublicationSerialization::serializePublicationMarker($publication, $connected),
            'mobile_markers' => PublicationSerialization::serializePublicationMobileMarker($publication, $connected),
            'color' => $publication->getPublicationcolor() instanceof PublicationColor ? PublicationSerialization::serializePublicationColor($publication->getPublicationcolor()) : false,
        );
    }

    public static function serializePublicationArraySqlite(Publication $publication, User $connected)
    {
        if (!is_null($publication->getGeolocation())) {
            $FriendMember = PublicationSerialization::serializePublicationFriendMember($publication, $connected);
            $observations = $publication->getObservations();
            if (count($observations)) {
                $observation = $observations[0];
            } else {
                $observation = null;
            }
            $markers = PublicationSerialization::serializePublicationMarker($publication, $connected);
            $array = array(
                "c_id" => $publication->getId(),
                "c_owner_id" => $publication->getOwner()->getId(),
                "c_owner_name" => $publication->getOwner()->getFullName(),
                "c_text" => $publication->getContent(),
                "c_type" => (!is_null($publication->getMedia())) ? $publication->getMedia()->getType() : "",
                "c_lat" => $publication->getGeolocation()->getLatitude(),
                "c_lon" => $publication->getGeolocation()->getLongitude(),
                "c_sharing" => $publication->getSharing()->getShare(),
                "c_groups" => PublicationSerialization::serializePublicationSqliteGroup($publication, $connected),
                "c_shareusers" => PublicationSerialization::serializePublicationSqliteUser($publication, $connected),
                "c_hunts" => PublicationSerialization::serializePublicationSqliteHunt($publication, $connected),
                "c_friend" => $FriendMember["c_friend"],
                "c_member" => $FriendMember["c_member"],
                "c_category_id" => (!is_null($observation) && !is_null($observation->getCategory())) ? $observation->getCategory()->getId() : "NULL",
                "c_marker_picto" => str_replace("website", "mobile", $markers["picto"]),
                "c_legend" => !is_null($publication->getLegend()) ? $publication->getLegend() : "",
                "c_user_id" => $connected->getId(),
//		"c_user_id" => "B74D8A3F-87E9-4CDF-81E7-42B501F67CFC",
                "c_updated" => $publication->getUpdated()->getTimestamp(),
                "c_created" => $publication->getCreated()->getTimestamp(),
                "c_search" => !is_null($publication->getSearch())?$publication->getSearch():"",
            );
            return $array;
        }
        return null;
    }

    public static function serializePublicationFriendMember(Publication $publication, User $connected)
    {
        $cFriend = 0;
        $cMember = 0;
        if ($publication->getOwner()->getId() == $connected->getId()) {
            $cFriend = 1;
            $cMember = 1;
        } else {
            $share = $publication->getSharing()->getShare();
            list($way, $friendship) = $connected->hasFriendshipWith($publication->getOwner(), array(UserFriend::CONFIRMED));
            if ($share == Sharing::NATURAPASS || $share == Sharing::FRIENDS) {
                if ($friendship instanceof UserFriend) {
                    $cFriend = 1;
                    $cMember = 1;
                } else if ($share == Sharing::NATURAPASS) {
                    $cMember = 1;
                }
            }
        }
        return array('c_friend' => $cFriend, "c_member" => $cMember);
    }


    /**
     * Serialize a publication sqlite
     *
     * @param Publication $publication
     * @param User $connected
     * @return array
     */
    public static function serializePublicationSqlite(Publication $publication, User $connected)
    {
        if (!is_null($publication->getGeolocation())) {
            $array = PublicationSerialization::serializePublicationArraySqlite($publication, $connected);
            $sql = "INSERT INTO `tb_carte`(`c_id`,`c_owner_id`,`c_type`,`c_lat`,`c_lon`,`c_sharing`,`c_groups`,`c_hunts`,`c_friend`,`c_member`,`c_category_id`,`c_marker_picto`,`c_legend`,`c_user_id`,`c_updated`) VALUES " .
                "(:c_id,:c_owner_id,:c_type,:c_lat,:c_lon,:c_sharing,:c_groups,:c_hunts,:c_friend,:c_member,:c_category_id,:c_marker_picto,:c_legend,:c_user_id,:c_updated);";
            foreach ($array as $key => $value) {
                $value = (is_array($value) ? json_encode($value) : $value);
                $value = str_replace("'", "&apos;", $value);
                $value = ($value != "NULL") ? '\'' . $value . '\'' : $value;
                $sql = str_replace(":" . $key, $value, $sql);
            }
            return $sql;
        }
        return null;
    }

    /**
     * Serialize a publication sqlite with new Point
     *
     * @param Publication $publication
     * @param User $connected
     * @return array
     */
    public static function serializePublicationSqliteNewPoint($lastId, $updated, Publication $publication, User $connected, $sqlFormat = true)
    {
        PublicationSerialization::serializePublicationMarker($publication, $connected);
        $lastUpdated = $publication->getLastUpdated($connected);
        if (!$updated) {
            $updated = $lastUpdated;
        } else {
            $dateTime = new \DateTime();
            $dateTime->setTimestamp($updated);
            $updated = $dateTime;
        }
        if (!is_null($publication->getGeolocation())) {
            $array = PublicationSerialization::serializePublicationArraySqlite($publication, $connected);

            $toAdd = false;
            if ($publication->getId() > $lastId) {
                $toAdd = true;
                $sql = "INSERT INTO `tb_carte`(`c_id`,`c_owner_id`,`c_type`,`c_lat`,`c_lon`,`c_sharing`,`c_groups`,`c_hunts`,`c_friend`,`c_member`,`c_category_id`,`c_marker_picto`,`c_legend`,`c_user_id`,`c_updated`) VALUES " .
                    "(:c_id,:c_owner_id,:c_type,:c_lat,:c_lon,:c_sharing,:c_groups,:c_hunts,:c_friend,:c_member,:c_category_id,:c_marker_picto,:c_legend,:c_user_id,:c_updated);";
            } else if ($updated < $lastUpdated) {
                $toAdd = true;
                $sql = "UPDATE `tb_carte` SET `c_owner_id`=:c_owner_id,`c_type`=:c_type,`c_lat`=:c_lat,`c_lon`=:c_lon,`c_sharing`=:c_sharing,`c_groups`=:c_groups,`c_hunts`=:c_hunts,`c_friend`=:c_friend,`c_member`=:c_member,`c_category_id`=:c_category_id,`c_marker_picto`=:c_marker_picto,`c_legend`=:c_legend,`c_updated`=:c_updated WHERE `c_id`=:c_id AND `c_user_id`=:c_user_id;";
            }
            if ($toAdd && $sqlFormat) {
                foreach ($array as $key => $value) {
                    $value = (is_array($value) ? json_encode($value) : $value);
                    $value = str_replace("'", "&apos;", $value);
                    $value = ($value != "NULL") ? '\'' . $value . '\'' : $value;
                    $sql = str_replace(":" . $key, $value, $sql);
                }
                return $sql;
            }
            if ($toAdd) {
                $arrayClone = array();
                foreach ($array as $key => $value) {
                    if ($value != "NULL" && $value != "") {
                        $arrayClone[$key] = $array[$key];
                    }
                }
                return $arrayClone;
            }
        }
        return null;
    }

    /**
     * Serialize a hunt sqlite
     *
     * @return array
     */
    public static function serializePublicationSqliteRefresh2($publicationIds, $updated, Publication $publication, User $connected, $force_updated = false)
    {
//        return SqliteSerialization::serializeSqliteSqlite($huntIds, $updated, $hunt, HuntSerialization::serializeHuntArraySqlite($hunt, $connected), "tb_hunt", $sqlFormat);
        $hasUpdated = true;
        $element = $publication;
        $ids = $publicationIds;
        $array = PublicationSerialization::serializePublicationArraySqlite($element, $connected, $force_updated);
        if ($hasUpdated) {
            $lastUpdated = $element->getLastUpdated($connected);
            if (!$updated) {
                $updated = $lastUpdated;
            } else {
                $dateTime = new \DateTime();
                $dateTime->setTimestamp($updated);
                $updated = $dateTime;
            }
        }
        if (!in_array($element->getId(), $ids)) {
            return $array;
        } else if ($hasUpdated && in_array($element->getId(), $ids) && $updated < $lastUpdated) {
            return $array;
        }
        return null;
    }

    /**
     * Serialize a publication sqlite with new Point
     *
     * @param Publication $publication
     * @param User $connected
     * @return array
     */
    public static function serializePublicationSqliteRefresh($publicationIds, $updated, Publication $publication, User $connected, $sqlFormat = true)
    {
        $lastUpdated = $publication->getLastUpdated($connected);
        if (!$updated) {
            $updated = $lastUpdated;
        } else {
            $dateTime = new \DateTime();
            $dateTime->setTimestamp($updated);
            $updated = $dateTime;
        }
        if (!is_null($publication->getGeolocation())) {
            $array = PublicationSerialization::serializePublicationArraySqlite($publication, $connected);

            $toAdd = false;
            if (!in_array($publication->getId(), $publicationIds)) {
                $toAdd = true;
                $sql = "INSERT INTO `tb_carte`(`c_id`,`c_owner_id`,`c_type`,`c_lat`,`c_lon`,`c_sharing`,`c_groups`,`c_hunts`,`c_friend`,`c_member`,`c_category_id`,`c_marker_picto`,`c_legend`,`c_user_id`,`c_updated`) VALUES " .
                    "(:c_id,:c_owner_id,:c_type,:c_lat,:c_lon,:c_sharing,:c_groups,:c_hunts,:c_friend,:c_member,:c_category_id,:c_marker_picto,:c_legend,:c_user_id,:c_updated);";
            } else if (in_array($publication->getId(), $publicationIds) && $updated < $lastUpdated) {
                $toAdd = true;
                $sql = "UPDATE `tb_carte` SET `c_owner_id`=:c_owner_id,`c_type`=:c_type,`c_lat`=:c_lat,`c_lon`=:c_lon,`c_sharing`=:c_sharing,`c_groups`=:c_groups,`c_hunts`=:c_hunts,`c_friend`=:c_friend,`c_member`=:c_member,`c_category_id`=:c_category_id,`c_marker_picto`=:c_marker_picto,`c_legend`=:c_legend,`c_updated`=:c_updated WHERE `c_id`=:c_id AND `c_user_id`=:c_user_id;";
            }
            if ($toAdd && $sqlFormat) {
                foreach ($array as $key => $value) {
                    $value = (is_array($value) ? json_encode($value) : $value);
                    $value = str_replace("'", "&apos;", $value);
                    $value = ($value != "NULL") ? '\'' . $value . '\'' : $value;
                    $sql = str_replace(":" . $key, $value, $sql);
                }
                return $sql;
            }
            if ($toAdd) {
                $arrayClone = array();
                foreach ($array as $key => $value) {
                    if ($value != "NULL" && $value != "") {
                        $arrayClone[$key] = $array[$key];
                    }
                }
                return $arrayClone;
            }
        }
        return null;
    }

    /**
     * Serialize a publication sqlite with new Point
     *
     * @param Publication $publication
     * @param User $connected
     * @return array
     */
    public static function serializePublicationSqliteInsertOrReplace($publicationIds, $updated, Publication $publication, User $connected)
    {
       $lastUpdated = $publication->getLastUpdated($connected);
            if (!$updated) {
                $updated = $lastUpdated;
            } else {
                $dateTime = new \DateTime();
                $dateTime->setTimestamp($updated);
                $updated = $dateTime;
            }
            if (!is_null($publication->getGeolocation())) {
                if (!in_array($publication->getId(), $publicationIds)) {
                    return PublicationSerialization::serializePublicationArraySqlite($publication, $connected);
                } else if (in_array($publication->getId(), $publicationIds) && $updated <= $lastUpdated) {
                    return PublicationSerialization::serializePublicationArraySqlite($publication, $connected);
                }
            }
            return null;
    }

    /**
     * Serialize a publication sqlite hunts
     *
     * @param Publication $publication
     * @param User $connected
     * @return array
     */
    public static function serializePublicationSqliteHunt(Publication $publication, User $connected)
    {
        $arrayHunt = array();
        foreach ($publication->getHunts() as $hunt) {
            $arrayHunt[] = "[" . $hunt->getId() . "]";
        }
        return join(",", $arrayHunt);
    }

    /**
     * Serialize a publication sqlite groups
     *
     * @param Publication $publication
     * @param User $connected
     * @return array
     */
    public static function serializePublicationSqliteGroup(Publication $publication, User $connected)
    {
        $arrayGroup = array();
        foreach ($publication->getGroups() as $group) {
            $arrayGroup[] = "[" . $group->getId() . "]";
        }
        return join(",", $arrayGroup);
    }

    /**
     * Serialize a publication sqlite users
     *
     * @param Publication $publication
     * @param User $connected
     * @return array
     */
    public static function serializePublicationSqliteUser(Publication $publication, User $connected)
    {
        $arrayUser = array();
        foreach ($publication->getShareusers() as $user) {
            $arrayUser[] = "[" . $user->getId() . "]";
        }
        return join(",", $arrayUser);
    }

    /**
     * Serialize a publication
     *
     * @param PublicationColor $color
     * @return array
     */
    public static function serializePublicationColor(PublicationColor $color)
    {
        $aColor = array();
        if ($color->getId()) {
            $aColor['id'] = $color->getId();
            $aColor['color'] = $color->getColor();
            $aColor['name'] = $color->getName();
        }
        return $aColor;
    }


    /**
     * Serialize a publication
     *
     * @param Publication $publication
     * @param User $connected
     * @return array
     */
    public static function serializePublicationMarker(Publication $publication, User $connected)
    {
//        $color = ($publication->getOwner()->getId() == $connected->getId()) ? "green" : "orange";
        $form = ($publication->getOwner()->getId() == $connected->getId()) ? "circle" : "carre";
        $color = "green";
//        $drag = ($publication->getOwner()->getId() == $connected->getId()) ? true : false;
        $drag = false;
        return array(
            'photo' => ApiRestController::getMarker($color, $form, array("type" => "photo", "publication" => $publication), $drag),
            'picto' => ApiRestController::getMarker($color, $form, array("type" => "picto", "publication" => $publication), $drag),
        );
    }

    /**
     * Serialize a publication
     *
     * @param Publication $publication
     * @param User $connected
     * @return array
     */
    public static function serializePublicationMobileMarker(Publication $publication, User $connected)
    {
//        $color = ($publication->getOwner()->getId() == $connected->getId()) ? "green" : "orange";
        $form = ($publication->getOwner()->getId() == $connected->getId()) ? "circle" : "carre";
        $color = "green";
//        $drag = ($publication->getOwner()->getId() == $connected->getId()) ? true : false;
        $drag = false;
        return array(
            'photo' => ApiRestController::getMarker($color, $form, array("type" => "photo", "publication" => $publication), $drag, false, false, false),
            'picto' => ApiRestController::getMarker($color, $form, array("type" => "picto", "publication" => $publication), $drag, false, false, false),
        );
    }


    public static function serializeColorArraySqlite(PublicationColor $color)
    {
        return array(
            "c_id" => $color->getId(),
            "c_name" => $color->getName(),
            "c_color" => $color->getColor(),
        );
        return $array;
    }

    /**
     * Serialize a group sqlite with new Point
     *
     * @return array
     */
    public static function serializColorSqliteRefresh($colorIds, PublicationColor $color, $sqlFormat = true)
    {
//        return SqliteSerialization::serializeSqliteSqlite($colorIds, false, $color, PublicationSerialization::serializeColorArraySqlite($color), "tb_color", $sqlFormat, false);
        return SqliteSerialization::serializeSqliteReturnArrayData($colorIds, false, $color, PublicationSerialization::serializeColorArraySqlite($color), false);
    }

    /**
     * Serialize a group sqlite with new Point
     *
     * @return array
     */
    public static function serializeColorSqlite($colorIds, $manager, &$sendedColor, $sqlFormat = true)
    {
        $return = array();
        if (!is_array($colorIds)) {
            $colorIds = explode(",", $colorIds);
        }
        $qb = $manager->createQueryBuilder()->select('c')
            ->from('NaturaPassPublicationBundle:PublicationColor', 'c')
            ->andWhere('c.active = 1')
            ->getQuery();

        $paginators = new Paginator($qb, $fetchJoinCollection = true);
        $allIds = array();
        $arrayValues = array();
        foreach ($paginators as $color) {
            $allIds[] = $color->getId();
            $val = PublicationSerialization::serializColorSqliteRefresh($colorIds, $color, $sqlFormat);
            if (!is_null($val)) {
                $arrayValues[] = $val;
            }
        }
        if (!empty($arrayValues)) {
            $return = array_merge($return, SqliteSerialization::serializeSqliteInserOrReplace("tb_color", $arrayValues));
        }
        $arrayDeleteId = array();
        foreach ($colorIds as $colorId) {
            if (!in_array($colorId, $allIds)) {
                $arrayDeleteId[] = $colorId;
            }
        }
        if (count($arrayDeleteId)) {
            $return[] = "DELETE FROM `tb_color` WHERE `c_id` IN (" . join(',', $arrayDeleteId) . ");";
        }
        $sendedColor->setIds(implode(",", $allIds));
        return $return;
    }

}

