<?php

/**
 * Created by PhpStorm.
 * User: nicolasmendez
 * Date: 28/07/15
 * Time: 10:31
 */

namespace Api\ApiBundle\Controller\v2\Serialization;

use NaturaPass\UserBundle\Entity\HuntCityParameter;
use NaturaPass\UserBundle\Entity\HuntCountryParameter;


/**
 * Class HuntLocationSerialization
 * @package Api\ApiBundle\Controller\v2\Serialization
 *
 * @method static serializeHuntCitys(array $huntlocations)
 * @method static serializeHuntCountrys(array $huntlocations)
 */
class HuntLocationSerialization extends Serialization
{

    /**
     * @param HuntCityParameter $huntcity
     * @return array
     */
    public static function serializeHuntCity(HuntCityParameter $huntcity)
    {
        return array(
            'id' => $huntcity->getCity()->getId(),
            'name' => $huntcity->getCity()->getName(),
        );
    }

    /**
     * @param HuntCountryParameter $huntcountry
     * @return array
     */
    public static function serializeHuntCountry(HuntCountryParameter $huntcountry)
    {
        return array(
            'id' => $huntcountry->getCountry()->getId(),
            'name' => $huntcountry->getCountry()->getName(),
        );
    }
}
