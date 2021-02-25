<?php

/**
 * Created by PhpStorm.
 * User: nicolasmendez
 * Date: 28/07/15
 * Time: 10:31
 */

namespace Api\ApiBundle\Controller\v2\Serialization;

use NaturaPass\UserBundle\Entity\User;

/**
 * Class SqliteSerialization
 * @package Api\ApiBundle\Controller\v2\Serialization
 *
 */
class SqliteSerialization extends Serialization
{

    /**
     * Serialize an sqlite
     *
     * @return array
     */
    public static function serializeSqliteSqlite($ids, $updated, $element, $array, $tableName, $sqlFormat = true, $hasUpdated = true)
    {
        if ($hasUpdated) {
            $lastUpdated = $element->getLastUpdated();
            if (!$updated) {
                $updated = $lastUpdated;
            } else {
                $dateTime = new \DateTime();
                $dateTime->setTimestamp($updated);
                $updated = $dateTime;
            }
        }

        $arrayKeys = array();
        $arrayInsert = array();
        $arrayUpdate = array();
        foreach (array_keys($array) as $key) {
            $arrayKeys[] = "`" . $key . "`";
            $arrayInsert[] = ":" . $key;
            $arrayUpdate[] = "`" . $key . "`=:" . $key;
        }

        $toAdd = false;
        if (!in_array($element->getId(), $ids)) {
            $toAdd = true;
            $sql = "INSERT INTO `" . $tableName . "`(" . join(',', $arrayKeys) . ") VALUES " .
                "(" . join(',', $arrayInsert) . ");";
        } else if ($hasUpdated && in_array($element->getId(), $ids) && $updated < $lastUpdated) {
            $toAdd = true;
            $sql = "UPDATE `" . $tableName . "` SET " . join(',', $arrayUpdate) . " WHERE `c_id`=:c_id AND `c_user_id`=:c_user_id;";
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
     * Serialize an sqlite
     *
     * @return array
     */
    public static function serializeSqliteReturnArrayData($ids, $updated, $element, $array, $hasUpdated = true)
    {
        if ($hasUpdated) {
            $lastUpdated = $element->getLastUpdated();
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
     * Serialize a sqlite
     *
     * @return array
     */
    public static function serializeSqliteInserOrReplace($table_name, $datas)
    {
        $arrayReturn = array();
        $arraySeparate = array();
        if (!empty($datas)) {
            $arrayColumns = array();
            foreach (array_keys(current($datas)) as $key) {
                $arrayColumns[] = "`" . $key . "`";
            }
            $arrayValues = array();
            foreach ($datas as $data) {
                $arrayKey = array();
                $arrayData = array();
                foreach ($data as $key => $value) {
                    $arrayKey[] = ":" . $key;
                }
                $sql = "(" . implode(",", $arrayKey) . ")";
                foreach ($data as $key => $value) {
                    $value = (is_array($value) ? json_encode($value) : $value);
                    $value = str_replace("'", "&apos;", $value);
                    $value = ($value != "NULL") ? '\'' . $value . '\'' : $value;
                    $sql = str_replace(":" . $key, $value, $sql);
                }
                $arrayValues[] = $sql;
            }
            $indice = 0;
            foreach ($arrayValues as $key => $value) {
                if ($key % 200 == 0) {
                    $indice++;
                    $arraySeparate[$indice] = array();
                }
                $arraySeparate[$indice][] = $value;
            }
            foreach ($arraySeparate as $value) {
                $preSQL = "INSERT OR REPLACE INTO `" . $table_name . "` (" . implode(",", $arrayColumns) . ") VALUES ";
                $preSQL .= implode(",", $value);
                $arrayReturn[] = $preSQL;
            }
            return $arrayReturn;
        }
        return null;
    }
}
