<?php

/**
 * Created by PhpStorm.
 * User: vincentvalot
 * Date: 28/07/15
 * Time: 10:34
 */

namespace Api\ApiBundle\Controller\v2\Serialization;

use Admin\SentinelleBundle\Entity\Locality;

/**
 * Class LocalitySerialization
 * @package Api\ApiBundle\Controller\v2\Serialization
 *
 * @method static serializeLocalitys(array $locality)
 * @method static serializeLocalitySearchs(array $locality)
 * @method static serializeLocalityDepartmentSearchs(array $locality)
 */
class LocalitySerialization extends Serialization
{

    /**
     * Serialize a locality
     *
     * @param Locality $locality
     *
     * @return array|bool
     */
    public static function serializeLocality(Locality $locality)
    {
        return array(
            'id' => $locality->getId(),
            'name' => $locality->getName(),
            'department' => $locality->getAdministrativeAreaLevel2(),
            'area' => $locality->getAdministrativeAreaLevel1(),
            'country' => $locality->getCountry(),
            'postal_code' => $locality->getPostal_code(),
        );
    }

    /**
     * Serialize a locality search
     *
     * @param Locality $locality
     *
     * @return array|bool
     */
    public static function serializeLocalitySearch(Locality $locality)
    {
        return array(
            'id' => $locality->getId(),
            'text' => $locality->getPostal_code() . "-" . $locality->getName()
        );
    }

    /**
     * Serialize a locality search
     *
     * @param Locality $locality
     *
     * @return array|bool
     */
    public static function serializeLocalityDepartmentSearch(Locality $locality)
    {
        return array(
            'id' => $locality->getId(),
            'text' => $locality->getAdministrativeAreaLevel2()
        );
    }

}
