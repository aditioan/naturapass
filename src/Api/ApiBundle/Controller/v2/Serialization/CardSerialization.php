<?php

/**
 * Created by PhpStorm.
 * User: vincentvalot
 * Date: 28/07/15
 * Time: 10:31
 */

namespace Api\ApiBundle\Controller\v2\Serialization;

use Admin\SentinelleBundle\Entity\Card;
use Admin\SentinelleBundle\Entity\CardLabel;
use Admin\SentinelleBundle\Entity\CardLabelContent;

/**
 * Class CardSerialization
 * @package Api\ApiBundle\Controller\v2\Serialization
 *
 * @method static serializeCards(array $cards, $labels = true, $force)
 * @method static serializeCardLabels(array $labels, $force)
 * @method static serializeCardLabelContents(array $contents, $force)
 */
class CardSerialization extends Serialization
{

    /**
     * @param Card $card
     * @param boolean $labels
     * @return array
     */
    public static function serializeCard(Card $card, $labels = true, $force = false)
    {
        $array = array(
            'id' => $card->getId(),
            'name' => $card->getName(),
        );
        if ($labels) {
            $array['labels'] = CardSerialization::serializeCardLabels($card->getLabels(), $force);
        }
        return $array;
    }

    /**
     * @param CardLabel $label
     * @return array
     */
    public static function serializeCardLabel(CardLabel $label, $force = false)
    {
        if ($label->getVisible() == 1 || $force) {
            $arrayChild = array(
                'id' => $label->getId(),
                'name' => $label->getName(),
                'type' => $label->getType(),
                'required' => $label->getRequired()
            );
            if ($label->allowContentType()) {
                $arrayChild['contents'] = CardSerialization::serializeCardLabelContents($label->getContents(), $force);
            }
        } else {
            $arrayChild = array();
        }
        return $arrayChild;
    }

    /**
     * @param CardLabelContent $content
     * @return array
     */
    public static function serializeCardLabelContent(CardLabelContent $content, $force = false)
    {
        if ($content->getVisible() == 1 || $force) {
            $arrayChild = array(
                'id' => $content->getId(),
                'name' => $content->getName(),
            );
        } else {
            $arrayChild = array();
        }
        return $arrayChild;
    }

}
