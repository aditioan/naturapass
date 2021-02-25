<?php

/**
 * Created by PhpStorm.
 * User: nicolasmendez
 * Date: 28/07/15
 * Time: 10:31
 */

namespace Api\ApiBundle\Controller\v2\Serialization;

use NaturaPass\UserBundle\Entity\HuntTypeParameter;


/**
 * Class HuntTypeSerialization
 * @package Api\ApiBundle\Controller\v2\Serialization
 *
 */
class HuntTypeSerialization extends Serialization
{

    /**
     * @param HuntTypeParameter $hunttype
     * @return array
     */
    public static function serializeHuntType(HuntTypeParameter $hunttype)
    {
        $array = array();
        foreach ($hunttype->getHunttypes() as $hunttype_sub) {
            $array[] = array(
                'id' => $hunttype_sub->getId(),
                'name' => $hunttype_sub->getName(),
            );
        }
        return $array;
    }
}
