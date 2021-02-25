<?php

/**
 * Created by PhpStorm.
 * User: vincentvalot
 * Date: 28/07/15
 * Time: 10:31
 */

namespace Api\ApiBundle\Controller\v2\Serialization;

use Admin\AnimalBundle\Entity\Animal;

/**
 * Class AnimalSerialization
 * @package Api\ApiBundle\Controller\v2\Serialization
 *
 * @method static serializeAnimals(array $animals)
 */
class AnimalSerialization extends Serialization {

    /**
     * @param Animal $animal
     * @return array
     */
    public static function serializeAnimal(Animal $animal) {
        $array = array(
            'id' => $animal->getId(),
            'name' => $animal->getName_fr(),
        );
        return $array;
    }

}
