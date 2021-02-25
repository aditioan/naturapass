<?php

/**
 * Created by PhpStorm.
 * User: nicolasmendez
 * Date: 28/07/15
 * Time: 10:31
 */

namespace Api\ApiBundle\Controller\v2\Serialization;

use Doctrine\ORM\Tools\Pagination\Paginator;
use NaturaPass\UserBundle\Entity\DogBreed;
use NaturaPass\UserBundle\Entity\DogParameter;
use NaturaPass\UserBundle\Entity\DogPhoto;
use NaturaPass\UserBundle\Entity\DogType;


/**
 * Class DogSerialization
 * @package Api\ApiBundle\Controller\v2\Serialization
 *
 * @method static serializeDogs(array $dogs)
 * @method static serializeDogLesss(array $dogs)
 */
class DogSerialization extends Serialization
{

    /**
     * @param DogParameter $dog
     * @return array
     */
    public static function serializeDog(DogParameter $dog)
    {
        $array = array(
            'id' => $dog->getId(),
            'name' => $dog->getName(),
            'type' => !is_null($dog->getType()) ? DogSerialization::serializeDogType($dog->getType()) : null,
            'breed' => !is_null($dog->getBreed()) ? DogSerialization::serializeDogBreed($dog->getBreed()) : null,
            'photo' => $dog->getPhoto() instanceof DogPhoto ? MainSerialization::serializeMedia($dog->getPhoto()) : null,
            'medias' => DogSerialization::serializeDogMedias($dog),
            'name' => $dog->getName(),
            'sex' => $dog->getSex(),
            'birthday' => is_null($dog->getBirthday()) ? null : $dog->getBirthday()->format(\DateTime::ATOM),
            'created' => $dog->getCreated()->format(\DateTime::ATOM),
            'updated' => $dog->getUpdated()->format(\DateTime::ATOM),
        );
        return $array;
    }

    /**
     * @param DogParameter $dog
     * @return array
     */
    public static function serializeLastpicDog(DogParameter $dog)
    {
        $array = array(
            'id' => $dog->getId(),
            'name' => $dog->getName(),
            'type' => !is_null($dog->getType()) ? DogSerialization::serializeDogType($dog->getType()) : null,
            'breed' => !is_null($dog->getBreed()) ? DogSerialization::serializeDogBreed($dog->getBreed()) : null,
            'photo' => $dog->getPhoto() instanceof DogPhoto ? MainSerialization::serializeMedia($dog->getPhoto()) : null,
            'medias' => DogSerialization::serializeLastpicMedias($dog),
            'name' => $dog->getName(),
            'sex' => $dog->getSex(),
            'birthday' => is_null($dog->getBirthday()) ? null : $dog->getBirthday()->format(\DateTime::ATOM),
            'created' => $dog->getCreated()->format(\DateTime::ATOM),
            'updated' => $dog->getUpdated()->format(\DateTime::ATOM),
        );
        return $array;
    }

    /**
     * @param DogType $type
     * @return array
     */
    public static function serializeDogType(DogType $type)
    {
        $array = array(
            'id' => $type->getId(),
            'name' => $type->getName(),
        );
        return $array;
    }

    /**
     * @param DogBreed $breed
     * @return array
     */
    public static function serializeDogBreed(DogBreed $breed)
    {
        $array = array(
            'id' => $breed->getId(),
            'name' => $breed->getName(),
        );
        return $array;
    }

    /**
     * @param DogParameter $dog
     * @return array
     */
    public static function serializeDogMedias(DogParameter $dog)
    {
        $array = array();
        foreach ($dog->getMedias() as $media) {
            $array[] = array(
                'id' => $media->getId(),
                'type' => $media->getType(),
                'path' => $media->getResize()
            );
        }
        return $array;
    }

    /**
     * @param DogParameter $dog
     * @return array
     */
    public static function serializeLastpicMedias(DogParameter $dog)
    {
        $array = array();
        foreach ($dog->getMedias() as $media) {
            $array['lastpic'] = array(
                'id' => $media->getId(),
                'type' => $media->getType(),
                'path' => $media->getResize()
            );
        }
        return $array;
    }

    /**
     * @param DogParameter $dog
     * @return array
     */
    public static function serializeDogLess(DogParameter $dog)
    {
        $array = array(
            'id' => $dog->getId(),
            'name' => $dog->getName(),
        );
        return $array;
    }

    public static function serializeBreedArraySqlite(DogBreed $breed)
    {
        return array(
            "c_id" => $breed->getId(),
            "c_name" => $breed->getName(),
        );
        return $array;
    }

    /**
     * Serialize a dogbreed sqlite with new Point
     *
     * @return array
     */
    public static function serializeBreedSqliteRefresh($breedIds, DogBreed $breed, $sqlFormat = true)
    {
//        return SqliteSerialization::serializeSqliteSqlite($colorIds, false, $color, PublicationSerialization::serializeColorArraySqlite($color), "tb_color", $sqlFormat, false);
        return SqliteSerialization::serializeSqliteReturnArrayData($breedIds, false, $breed, DogSerialization::serializeBreedArraySqlite($breed), false);
    }

    /**
     * Serialize a dogBreed sqlite with new Point
     *
     * @return array
     */
    public static function serializeBreedSqlite($breedIds, $manager, &$sendedBreed, $sqlFormat = true)
    {
        $return = array();
        if (!is_array($breedIds)) {
            $breedIds = explode(",", $breedIds);
        }
        $qb = $manager->createQueryBuilder()->select('b')
            ->from('NaturaPassUserBundle:DogBreed', 'b')
            ->getQuery();

        $paginators = new Paginator($qb, $fetchJoinCollection = true);
        $allIds = array();
        $arrayValues = array();
        foreach ($paginators as $breed) {
            $allIds[] = $breed->getId();
            $val = DogSerialization::serializeBreedSqliteRefresh($breedIds, $breed, $sqlFormat);
            if (!is_null($val)) {
                $arrayValues[] = $val;
            }
        }
        if (!empty($arrayValues)) {
            $return = array_merge($return, SqliteSerialization::serializeSqliteInserOrReplace("tb_dog_breed", $arrayValues));
        }
        $arrayDeleteId = array();
        foreach ($breedIds as $breedId) {
            if (!in_array($breedId, $allIds)) {
                $arrayDeleteId[] = $breedId;
            }
        }
        if (count($arrayDeleteId)) {
            $return[] = "DELETE FROM `tb_dog_breed` WHERE `c_id` IN (" . join(',', $arrayDeleteId) . ");";
        }
        $sendedBreed->setIds(implode(",", $allIds));
        return $return;
    }

    public static function serializeTypeArraySqlite(DogType $type)
    {
        return array(
            "c_id" => $type->getId(),
            "c_name" => $type->getName(),
        );
        return $array;
    }

    /**
     * Serialize a dogtype sqlite with new Point
     *
     * @return array
     */
    public static function serializeTypeSqliteRefresh($typeIds, DogType $type, $sqlFormat = true)
    {
        return SqliteSerialization::serializeSqliteReturnArrayData($typeIds, false, $type, DogSerialization::serializeTypeArraySqlite($type), false);
    }

    /**
     * Serialize a dogType sqlite with new Point
     *
     * @return array
     */
    public static function serializeTypeSqlite($typeIds, $manager, &$sendedType, $sqlFormat = true)
    {
        $return = array();
        if (!is_array($typeIds)) {
            $typeIds = explode(",", $typeIds);
        }
        $qb = $manager->createQueryBuilder()->select('t')
            ->from('NaturaPassUserBundle:DogType', 't')
            ->getQuery();

        $paginators = new Paginator($qb, $fetchJoinCollection = true);
        $allIds = array();
        $arrayValues = array();
        foreach ($paginators as $type) {
            $allIds[] = $type->getId();
            $val = DogSerialization::serializeTypeSqliteRefresh($typeIds, $type, $sqlFormat);
            if (!is_null($val)) {
                $arrayValues[] = $val;
            }
        }
        if (!empty($arrayValues)) {
            $return = array_merge($return, SqliteSerialization::serializeSqliteInserOrReplace("tb_dog_type", $arrayValues));
        }
        $arrayDeleteId = array();
        foreach ($typeIds as $typeId) {
            if (!in_array($typeId, $allIds)) {
                $arrayDeleteId[] = $typeId;
            }
        }
        if (count($arrayDeleteId)) {
            $return[] = "DELETE FROM `tb_dog_type` WHERE `c_id` IN (" . join(',', $arrayDeleteId) . ");";
        }
        $sendedType->setIds(implode(",", $allIds));
        return $return;
    }

}

