<?php

/**
 * Created by PhpStorm.
 * User: vincentvalot
 * Date: 28/07/15
 * Time: 11:21
 */

namespace Api\ApiBundle\Controller\v2\Serialization;

use Doctrine\Common\Collections\Collection;

abstract class Serialization {

    /**
     * is triggered when invoking inaccessible methods in a static context.
     *
     * @param $name string
     * @param $arguments array
     * @return mixed
     * @link http://php.net/manual/en/language.oop5.overloading.php#language.oop5.overloading.methods
     */
    public static function __callStatic($name, $arguments) {
        if ($name[strlen($name) - 1] == 's' && ($arguments[0] instanceof Collection || is_array($arguments[0]))) {
            $method = substr($name, 0, -1);

            $serialized = array();
            $data = array_shift($arguments);

            foreach ($data as $object) {
                $return = forward_static_call_array(array(get_called_class(), $method), array_merge(array($object), $arguments));
                if ($return) {
                    $serialized[] = $return;
                }
            }

            return $serialized;
        }

        return false;
    }

}
