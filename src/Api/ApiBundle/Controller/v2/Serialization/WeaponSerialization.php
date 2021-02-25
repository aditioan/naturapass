<?php

/**
 * Created by PhpStorm.
 * User: nicolasmendez
 * Date: 28/07/15
 * Time: 10:31
 */

namespace Api\ApiBundle\Controller\v2\Serialization;

use Doctrine\ORM\Tools\Pagination\Paginator;
use NaturaPass\UserBundle\Entity\WeaponBrand;
use NaturaPass\UserBundle\Entity\WeaponCalibre;
use NaturaPass\UserBundle\Entity\WeaponParameter;
use NaturaPass\UserBundle\Entity\WeaponPhoto;


/**
 * Class WeaponSerialization
 * @package Api\ApiBundle\Controller\v2\Serialization
 *
 * @method static serializeWeapons(array $weapons)
 * @method static serializeWeaponLesss(array $weapons)
 */
class WeaponSerialization extends Serialization
{

    /**
     * @param WeaponParameter $weapon
     * @return array
     */
    public static function serializeWeapon(WeaponParameter $weapon)
    {
        $array = array(
            'id' => $weapon->getId(),
            'name' => $weapon->getName(),
            'calibre' => !is_null($weapon->getCalibre()) ? WeaponSerialization::serializeWeaponCalibre($weapon->getCalibre()) : null,
            'brand' => !is_null($weapon->getBrand()) ? WeaponSerialization::serializeWeaponBrand($weapon->getBrand()) : null,
            'photo' => $weapon->getPhoto() instanceof WeaponPhoto ? MainSerialization::serializeMedia($weapon->getPhoto()) : null,
            'medias' => WeaponSerialization::serializeWeaponMedias($weapon),
            'name' => $weapon->getName(),
            'type' => $weapon->getType(),
            'created' => $weapon->getCreated()->format(\DateTime::ATOM),
            'updated' => $weapon->getUpdated()->format(\DateTime::ATOM),
        );
        return $array;
    }

    /**
     * @param WeaponCalibre $calibre
     * @return array
     */
    public static function serializeWeaponCalibre(WeaponCalibre $calibre)
    {
        $array = array(
            'id' => $calibre->getId(),
            'name' => $calibre->getName(),
        );
        return $array;
    }

    /**
     * @param WeaponBrand $brand
     * @return array
     */
    public static function serializeWeaponBrand(WeaponBrand $brand)
    {
        $array = array(
            'id' => $brand->getId(),
            'name' => $brand->getName(),
        );
        return $array;
    }

    /**
     * @param WeaponParameter $weapon
     * @return array
     */
    public static function serializeWeaponMedias(WeaponParameter $weapon)
    {
        $array = array();
        foreach ($weapon->getMedias() as $media) {
            $array[] = array(
                'id' => $media->getId(),
                'type' => $media->getType(),
                'path' => $media->getResize()
            );
        }
        return $array;
    }

    /**
     * @param WeaponParameter $weapon
     * @return array
     */
    public static function serializeWeaponLess(WeaponParameter $weapon)
    {
        $array = array(
            'id' => $weapon->getId(),
            'name' => $weapon->getName(),
        );
        return $array;
    }

    public static function serializeBrandArraySqlite(WeaponBrand $brand)
    {
        return array(
            "c_id" => $brand->getId(),
            "c_name" => $brand->getName(),
        );
        return $array;
    }

    /**
     * Serialize a weaponbrand sqlite with new Point
     *
     * @return array
     */
    public static function serializeBrandSqliteRefresh($brandIds, WeaponBrand $brand, $sqlFormat = true)
    {
//        return SqliteSerialization::serializeSqliteSqlite($colorIds, false, $color, PublicationSerialization::serializeColorArraySqlite($color), "tb_color", $sqlFormat, false);
        return SqliteSerialization::serializeSqliteReturnArrayData($brandIds, false, $brand, WeaponSerialization::serializeBrandArraySqlite($brand), false);
    }

    /**
     * Serialize a weaponbrand sqlite with new Point
     *
     * @return array
     */
    public static function serializeBrandSqlite($brandIds, $manager, &$sendedBrand, $sqlFormat = true)
    {
        $return = array();
        if (!is_array($brandIds)) {
            $brandIds = explode(",", $brandIds);
        }
        $qb = $manager->createQueryBuilder()->select('b')
            ->from('NaturaPassUserBundle:WeaponBrand', 'b')
            ->getQuery();

        $paginators = new Paginator($qb, $fetchJoinCollection = true);
        $allIds = array();
        $arrayValues = array();
        foreach ($paginators as $brand) {
            $allIds[] = $brand->getId();
            $val = WeaponSerialization::serializeBrandSqliteRefresh($brandIds, $brand, $sqlFormat);
            if (!is_null($val)) {
                $arrayValues[] = $val;
            }
        }
        if (!empty($arrayValues)) {
            $return = array_merge($return, SqliteSerialization::serializeSqliteInserOrReplace("tb_weapon_brand", $arrayValues));
        }
        $arrayDeleteId = array();
        foreach ($brandIds as $brandId) {
            if (!in_array($brandId, $allIds)) {
                $arrayDeleteId[] = $brandId;
            }
        }
        if (count($arrayDeleteId)) {
            $return[] = "DELETE FROM `tb_weapon_brand` WHERE `c_id` IN (" . join(',', $arrayDeleteId) . ");";
        }
        $sendedBrand->setIds(implode(",", $allIds));
        return $return;
    }


    public static function serializeCalibreArraySqlite(WeaponCalibre $calibre)
    {
        return array(
            "c_id" => $calibre->getId(),
            "c_name" => $calibre->getName(),
        );
        return $array;
    }

    /**
     * Serialize a weaponcalibre sqlite with new Point
     *
     * @return array
     */
    public static function serializeCalibreSqliteRefresh($calibreIds, WeaponCalibre $calibre, $sqlFormat = true)
    {
//        return SqliteSerialization::serializeSqliteSqlite($colorIds, false, $color, PublicationSerialization::serializeColorArraySqlite($color), "tb_color", $sqlFormat, false);
        return SqliteSerialization::serializeSqliteReturnArrayData($calibreIds, false, $calibre, WeaponSerialization::serializeCalibreArraySqlite($calibre), false);
    }

    /**
     * Serialize a weaponcalibre sqlite with new Point
     *
     * @return array
     */
    public static function serializeCalibreSqlite($calibreIds, $manager, &$sendedCalibre, $sqlFormat = true)
    {
        $return = array();
        if (!is_array($calibreIds)) {
            $calibreIds = explode(",", $calibreIds);
        }
        $qb = $manager->createQueryBuilder()->select('b')
            ->from('NaturaPassUserBundle:WeaponCalibre', 'b')
            ->getQuery();

        $paginators = new Paginator($qb, $fetchJoinCollection = true);
        $allIds = array();
        $arrayValues = array();
        foreach ($paginators as $calibre) {
            $allIds[] = $calibre->getId();
            $val = WeaponSerialization::serializeCalibreSqliteRefresh($calibreIds, $calibre, $sqlFormat);
            if (!is_null($val)) {
                $arrayValues[] = $val;
            }
        }
        if (!empty($arrayValues)) {
            $return = array_merge($return, SqliteSerialization::serializeSqliteInserOrReplace("tb_weapon_calibre", $arrayValues));
        }
        $arrayDeleteId = array();
        foreach ($calibreIds as $calibreId) {
            if (!in_array($calibreId, $allIds)) {
                $arrayDeleteId[] = $calibreId;
            }
        }
        if (count($arrayDeleteId)) {
            $return[] = "DELETE FROM `tb_weapon_calibre` WHERE `c_id` IN (" . join(',', $arrayDeleteId) . ");";
        }
        $sendedCalibre->setIds(implode(",", $allIds));
        return $return;
    }

/**
     * @param WeaponParameter $weapon
     * @return array
     */
    public static function serializeLastWeapon(WeaponParameter $weapon)
    {
        $array = array(
            'id' => $weapon->getId(),
            'name' => $weapon->getName(),
            'calibre' => !is_null($weapon->getCalibre()) ? WeaponSerialization::serializeWeaponCalibre($weapon->getCalibre()) : null,
            'brand' => !is_null($weapon->getBrand()) ? WeaponSerialization::serializeWeaponBrand($weapon->getBrand()) : null,
            'photo' => $weapon->getPhoto() instanceof WeaponPhoto ? MainSerialization::serializeMedia($weapon->getPhoto()) : null,
            'medias' => WeaponSerialization::serializeLastPicWeaponMedias($weapon),
            'name' => $weapon->getName(),
            'type' => $weapon->getType(),
            'created' => $weapon->getCreated()->format(\DateTime::ATOM),
            'updated' => $weapon->getUpdated()->format(\DateTime::ATOM),
        );
        return $array;
    }

    /**
     * @param WeaponParameter $weapon
     * @return array
     */
    public static function serializeLastPicWeaponMedias(WeaponParameter $weapon)
    {
        $array = array();
        foreach ($weapon->getMedias() as $media) {
            $array['lastpic'] = array(
                'id' => $media->getId(),
                'type' => $media->getType(),
                'path' => $media->getResize()
            );
        }
        return $array;
    }

}
