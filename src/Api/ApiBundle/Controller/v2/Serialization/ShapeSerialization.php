<?php

/**
 * Created by PhpStorm.
 * User: Nicolas
 * Date: 23/12/2015
 * Time: 12:39
 */

namespace Api\ApiBundle\Controller\v2\Serialization;

use Admin\DistributorBundle\Entity\Distributor;
use FOS\UserBundle\Entity\Group;
use NaturaPass\MainBundle\Entity\Point;
use NaturaPass\MainBundle\Entity\Shape;
use NaturaPass\MainBundle\Entity\Sharing;
use NaturaPass\UserBundle\Entity\User;
use NaturaPass\UserBundle\Entity\UserFriend;

/**
 * Class PublicationSerialization
 * @package Api\ApiBundle\Controller\v2\Serialization
 *
 * @method static serializeShapes(array $shapes, $connected)
 * @method static serializeShapeMobiles(array $shapes, $connected)
 */
class ShapeSerialization extends Serialization
{
    /**
     * @param Shape $shape
     * @param User $connected
     * @return array
     */
    public static function serializeShape(Shape $shape, User $connected)
    {
        $arrayGroup = array();
        foreach ($shape->getGroups() as $group) {
            $arrayGroup[] = $group->getId();
        }
        $arrayHunt = array();
        foreach ($shape->getHunts() as $hunt) {
            $arrayHunt[] = $hunt->getId();
        }

        return array(
            "id" => $shape->getId(),
            "title" => (string)$shape->getTitle(),
            "description" => (string)$shape->getDescription(),
            "owner" => array(
                "id" => $shape->getOwner()->getId(),
            ),
            "isOwner" => $shape->getOwner()->getId() == $connected->getId(),
            "sharing" => $shape->getSharing()->getShare(),
            "groups" => $arrayGroup,
            "hunts" => $arrayHunt,
            "type" => $shape->getType(),
            "data" => $shape->getData(),
            "created" => $shape->getCreated(),
            "updated" => $shape->getUpdated()
        );
    }

    /**
     * @param Shape $shape
     * @param User $connected
     * @return array
     */
    public static function serializeShapeMobile(Shape $shape, User $connected)
    {
        $data = $shape->getData();
        $arrayPoint = array();

        $rectangle = ($shape->getType() == "rectangle");
        if ($rectangle) {
            $nb = 1;
            $point1 = $shape->getPoints()[0];
            $point2 = $shape->getPoints()[1];
            $ne = array("lat" => floatval($point2->getLatitude()), "lng" => floatval($point1->getLongitude()));
            $so = array("lat" => floatval($point1->getLatitude()), "lng" => floatval($point2->getLongitude()));
        }
        foreach ($shape->getPoints() as $point) {
            $arrayPoint[] = array("lat" => floatval($point->getLatitude()), "lng" => floatval($point->getLongitude()));
            if ($rectangle && $nb == 1) {
                $arrayPoint[] = $ne;
            } else if ($rectangle && $nb == 2) {
                $arrayPoint[] = $so;
            }
            if ($rectangle) {
                $nb++;
            }
        }
        if (isset($data["bounds"])) {
            $data["bounds"] = $arrayPoint;
        }
        if (isset($data["paths"])) {
            $data["paths"] = $arrayPoint;
        }
        if (isset($data["center"])) {
            $data["center"] = array("lat" => $data["center"][0], "lng" => $data["center"][1]);
        }
        if (isset($data["options"]) && isset($data["options"]["color"])) {
            $data["options"]["color"] = str_replace("#", "", $data["options"]["color"]);
        }

        return array(
            "id" => $shape->getId(),
            "title" => (string)$shape->getTitle(),
            "description" => (string)$shape->getDescription(),
            "owner" => array(
                "id" => $shape->getOwner()->getId(),
            ),
            "isOwner" => $shape->getOwner()->getId() == $connected->getId(),
            "sharing" => $shape->getSharing()->getShare(),
            "type" => $shape->getType(),
            "data" => $data,
            "created" => $shape->getCreated(),
            "updated" => $shape->getUpdated()
        );
    }

    public static function serializeShapeFriendMember(Shape $shape, User $connected)
    {
        $cFriend = 0;
        $cMember = 0;
        if ($shape->getOwner()->getId() == $connected->getId()) {
            $cFriend = 1;
            $cMember = 1;
        } else {
            $share = $shape->getSharing()->getShare();
            list($way, $friendship) = $connected->hasFriendshipWith($shape->getOwner(), array(UserFriend::CONFIRMED));
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
     * Serialize a shape sqlite groups
     *
     * @param Shape $shape
     * @param User $connected
     * @return array
     */
    public static function serializeShapeSqliteGroup(Shape $shape, User $connected)
    {
        $arrayGroup = array();
        foreach ($shape->getGroups() as $group) {
//            if ($connected->getAllGroups()->contains($group)) {
            $arrayGroup[] = "[" . $group->getId() . "]";
//            }
        }
        return join(",", $arrayGroup);
    }

    /**
     * Serialize a shape sqlite hunts
     *
     * @param Shape $shape
     * @param User $connected
     * @return array
     */
    public static function serializeShapeSqliteHunt(Shape $shape, User $connected)
    {
        $arrayHunt = array();
        foreach ($shape->getHunts() as $hunt) {
//            if ($connected->getAllGroups()->contains($group)) {
            $arrayHunt[] = "[" . $hunt->getId() . "]";
//            }
        }
        return join(",", $arrayHunt);
    }

    public static function serializeShapeArraySqlite(Shape $shape, User $connected)
    {
        $data = $shape->getData();
        $arrayPoint = array();

        $rectangle = ($shape->getType() == "rectangle");
        if ($rectangle) {
            $nb = 1;
            $point1 = $shape->getPoints()[0];
            $point2 = $shape->getPoints()[1];
            $ne = array("lat" => floatval($point2->getLatitude()), "lng" => floatval($point1->getLongitude()));
            $so = array("lat" => floatval($point1->getLatitude()), "lng" => floatval($point2->getLongitude()));
        }
        foreach ($shape->getPoints() as $point) {
            $arrayPoint[] = array("lat" => floatval($point->getLatitude()), "lng" => floatval($point->getLongitude()));
            if ($rectangle && $nb == 1) {
                $arrayPoint[] = $ne;
            } else if ($rectangle && $nb == 2) {
                $arrayPoint[] = $so;
            }
            if ($rectangle) {
                $nb++;
            }
        }
        if (isset($data["bounds"])) {
            $data["bounds"] = $arrayPoint;
        }
        if (isset($data["paths"])) {
            $data["paths"] = $arrayPoint;
        }
        if (isset($data["center"])) {
            $data["center"] = array("lat" => $data["center"][0], "lng" => $data["center"][1]);
        }
        if (isset($data["options"]) && isset($data["options"]["color"])) {
            $data["options"]["color"] = str_replace("#", "", $data["options"]["color"]);
        }
        $FriendMember = ShapeSerialization::serializeShapeFriendMember($shape, $connected);
        $array = array(
            "c_id" => $shape->getId(),
            "c_owner_id" => $shape->getOwner()->getId(),
            "c_type" => $shape->getType(),
            "c_title" => $shape->getTitle(),
            "c_description" => $shape->getDescription(),
            "c_sharing" => $shape->getSharing()->getShare(),
            "c_groups" => ShapeSerialization::serializeShapeSqliteGroup($shape, $connected),
            "c_hunts" => ShapeSerialization::serializeShapeSqliteHunt($shape, $connected),
            "c_friend" => $FriendMember["c_friend"],
            "c_member" => $FriendMember["c_member"],
            "c_data" => $data,
            "c_updated" => $shape->getLastUpdated($connected)->getTimestamp(),
            "c_ne_lat" => $shape->getNeLatitude(),
            "c_ne_lng" => $shape->getNeLongitude(),
            "c_sw_lat" => $shape->getSwLatitude(),
            "c_sw_lng" => $shape->getSwLongitude(),
            "c_user_id" => $connected->getId(),
            "c_lat_center" => $shape->getLatCenter(),
            "c_lng_center" => $shape->getLonCenter(),
        );
        return $array;
    }

    /**
     * Serialize a publication sqlite with new Point
     *
     * @param Shape $shape
     * @param User $connected
     * @return array
     */
    public static function serializeShapeSqliteRefresh($shapeIds, $updated, Shape $shape, User $connected, $sqlFormat = true, $center = false)
    {
        $lastUpdated = $shape->getLastUpdated($connected);
        if (!$updated) {
            $updated = $lastUpdated;
        } else {
            $dateTime = new \DateTime();
            $dateTime->setTimestamp($updated);
            $updated = $dateTime;
        }
        $array = ShapeSerialization::serializeShapeArraySqlite($shape, $connected);

        $toAdd = false;
        if (!in_array($shape->getId(), $shapeIds)) {
            $insertCenter = $dataCenter = "";
            $toAdd = true;
            if ($center) {
                $insertCenter = ",`c_lat_center`,`c_lng_center`";
                $dataCenter = ",:c_lat_center,:c_lng_center";
            }
            $sql = "INSERT INTO `tb_shape`(`c_id`,`c_owner_id`,`c_type`,`c_title`,`c_description`,`c_sharing`,`c_groups`,`c_hunts`,`c_friend`,`c_member`,`c_data`,`c_updated`,`c_ne_lat`,`c_ne_lng`,`c_sw_lat`,`c_sw_lng`,`c_user_id`" . $insertCenter . ") VALUES " .
                "(:c_id,:c_owner_id,:c_type,:c_title,:c_description,:c_sharing,:c_groups,:c_hunts,:c_friend,:c_member,:c_data,:c_updated,:c_ne_lat,:c_ne_lng,:c_sw_lat,:c_sw_lng,:c_user_id" . $dataCenter . ");";
        } else if (in_array($shape->getId(), $shapeIds) && $updated < $lastUpdated) {
            $updateCenter = "";
            $toAdd = true;
            $sql = "UPDATE `tb_shape` SET `c_owner_id`=:c_owner_id,`c_type`=:c_type,`c_title`=:c_title,`c_description`=:c_description,`c_sharing`=:c_sharing,`c_groups`=:c_groups,`c_hunts`=:c_hunts,`c_friend`=:c_friend,`c_member`=:c_member,`c_data`=:c_data,`c_updated`=:c_updated,`c_ne_lat`=:c_ne_lat,`c_ne_lng`=:c_ne_lng,`c_sw_lat`=:c_sw_lat,`c_sw_lng`=:c_sw_lng" . $updateCenter . " WHERE `c_id`=:c_id AND `c_user_id`=:c_user_id;";
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
        return null;
    }

    /**
     * Serialize a publication sqlite with new Point
     *
     * @param Shape $shape
     * @param User $connected
     * @return array
     */
    public static function serializeShapeSqliteInsertOrReplace($shapeIds, $updated, Shape $shape, User $connected)
    {
        $lastUpdated = $shape->getLastUpdated($connected);
        if (!$updated) {
            $updated = $lastUpdated;
        } else {
            $dateTime = new \DateTime();
            $dateTime->setTimestamp($updated);
            $updated = $dateTime;
        }
        if (!in_array($shape->getId(), $shapeIds)) {
            return ShapeSerialization::serializeShapeArraySqlite($shape, $connected);
        } else if (in_array($shape->getId(), $shapeIds) && $updated < $lastUpdated) {
            return ShapeSerialization::serializeShapeArraySqlite($shape, $connected);
        }
        return null;
    }
}