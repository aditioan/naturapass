<?php

namespace Api\ApiBundle\Controller\v2\Serialization;

use Admin\DistributorBundle\Entity\Brand;
use Admin\DistributorBundle\Entity\Distributor;
use NaturaPass\PublicationBundle\Entity\Publication;
use NaturaPass\PublicationBundle\Entity\PublicationColor;
use NaturaPass\UserBundle\Entity\User;
use Api\ApiBundle\Controller\v2\ApiRestController;

/**
 * Class PublicationSerialization
 * @package Api\ApiBundle\Controller\v2\Serialization
 *
 * @method static serializeDistributorSqlites(array $distributors)
 */
class DistributorSerialization extends Serialization
{

    public static function serializeDistributorArraySqlite(Distributor $distributor)
    {
        $brands = array();
        foreach ($distributor->getBrands() as $brand) {
            $brands[] = array("name" => $brand->getName(), "partner" => $brand->getPartner(), "logo" => $brand->getLogo()->getPath());
        }
        if (!is_null($distributor->getGeolocation())) {
            $array = array(
                "c_id" => $distributor->getId(),
                "c_name" => $distributor->getName(),
                "c_logo" => ($distributor->getLogo()) ? $distributor->getLogo()->getWebPath() : "",
                "c_address" => $distributor->getAddress(),
                "c_cp" => $distributor->getCp(),
                "c_city" => $distributor->getCity(),
                "c_tel" => $distributor->getTelephone(),
                "c_email" => $distributor->getEmail(),
                "c_lat" => $distributor->getGeolocation()->getLatitude(),
                "c_lon" => $distributor->getGeolocation()->getLongitude(),
                "c_brands" => json_encode($brands),
                "c_updated" => $distributor->getUpdated()->getTimestamp(),
            );
            return $array;
        }
        return null;
    }


    /**
     * Serialize a distributor sqlite
     *
     * @param Distributor $distributor
     * @param User $connected
     * @return array
     */
    public static function serializeDistributorSqlite(Distributor $distributor)
    {
        if (!is_null($distributor->getGeolocation())) {
            $array = DistributorSerialization::serializeDistributorArraySqlite($distributor);
            $sql = "INSERT INTO `tb_distributor`(`c_id`,`c_name`,`c_address,`c_cp`,`c_city`,`c_tel`,`c_email`,`c_lat`,`c_lon`,`c_brands`,`c_updated`,`c_logo`) VALUES " .
                "(:c_id,:c_name,:c_address,:c_cp,:c_city,:c_tel,:c_email,:c_lat,:c_lon,:c_brands,:c_updated,:c_logo);";
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
     * @param Distributor $distributor
     * @return array
     */
    public static function serializeDistributorSqliteRefresh($updated, Distributor $distributor, $sqlFormat = true)
    {
        $lastUpdated = $distributor->getUpdated();
        if ($updated) {
            $dateTime = new \DateTime();
            $dateTime->setTimestamp($updated);
            $updated = $dateTime;
        }
        if (!is_null($distributor->getGeolocation())) {
            $array = DistributorSerialization::serializeDistributorArraySqlite($distributor);

            $toAdd = false;
            if (!$updated || ($updated && $updated < $lastUpdated)) {
                $toAdd = true;
                $sql = "INSERT INTO `tb_distributor`(`c_id`,`c_name`,`c_address`,`c_cp`,`c_city`,`c_tel`,`c_email`,`c_lat`,`c_lon`,`c_brands`,`c_updated`,`c_logo`) VALUES " .
                    "(:c_id,:c_name,:c_address,:c_cp,:c_city,:c_tel,:c_email,:c_lat,:c_lon,:c_brands,:c_updated,:c_logo);";
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
     * @param Distributor $distributor
     * @return array
     */
    public static function serializeDistributorSqliteInsertOrReplace($updated, Distributor $distributor)
    {
        $lastUpdated = $distributor->getUpdated();
        if ($updated) {
            $dateTime = new \DateTime();
            $dateTime->setTimestamp($updated);
            $updated = $dateTime;
        }
        if (!is_null($distributor->getGeolocation())) {
            if (!$updated || ($updated && $updated <= $lastUpdated)) {
                $toAdd = true;
                return DistributorSerialization::serializeDistributorArraySqlite($distributor);
            }
        }
        return null;
    }
}

