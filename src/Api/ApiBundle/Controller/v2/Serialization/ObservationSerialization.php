<?php

/**
 * Created by PhpStorm.
 * User: vincentvalot
 * Date: 28/07/15
 * Time: 10:31
 */

namespace Api\ApiBundle\Controller\v2\Serialization;

use Admin\SentinelleBundle\Entity\CardLabel;
use Admin\SentinelleBundle\Entity\CardLabelContent;
use Admin\SentinelleBundle\Entity\Receiver;
use NaturaPass\ObservationBundle\Entity\AttachmentReceiver;
use NaturaPass\PublicationBundle\Entity\Publication;
use NaturaPass\UserBundle\Entity\User;
use NaturaPass\ObservationBundle\Entity\Observation;
use NaturaPass\ObservationBundle\Entity\Attachment;
use NaturaPass\ObservationBundle\Entity\ObservationReceiver;

/**
 * Class ObservationSerialization
 * @package Api\ApiBundle\Controller\v2\Serialization
 *
 * @method static serializeObservations(array $observations)
 * @method static serializeObservationReceivers(array $observations)
 * @method static serializeObservationAttachments(array $attachements)
 * @method static serializeObservationAttachmentReceivers(array $attachements)
 * @method static serializeObservationAttachmentBOs(array $attachements)
 * @method static serializeObservationSharings(array $receivers)
 */
class ObservationSerialization extends Serialization
{

    /**
     * @param Observation $observation
     * @return array
     */
    public static function serializeObservation(Observation $observation)
    {
        $tree = CategorySerialization::serializeCategoryTree($observation->getCategory());
        if ($observation->getSpecific() == 1 && !is_null($observation->getAnimal())) {
            $tree[] = $observation->getAnimal()->getName_fr();
        }

        return array(
            'id' => $observation->getId(),
            'name' => ($observation->getCategory() instanceof Category) ? $observation->getCategory()->getName() : "",
            'category_id' => (!is_null($observation->getCategory())) ? $observation->getCategory()->getId() : $observation->getCategory(),
            'tree' => $tree,
            'attachments' => ObservationSerialization::serializeObservationAttachmentArrays($observation->getAttachments()),
            'sharing_receiver' => ObservationSerialization::serializeObservationSharings($observation->getReceivers()),
        );
    }

    /**
     * @param Attachment $attachment
     * @return array
     */
    public static function serializeObservationAttachmentArrays($aAttachment)
    {
        $aArray = array();
        foreach ($aAttachment as $attachment) {
            $contentObject = null;
            $allowContent = $attachment->getLabel()->allowContentType();
            if ($allowContent) {
                foreach ($attachment->getLabel()->getContents() as $content) {
                    if ($content->getId() == intval($attachment->getValue())) {
                        $contentObject = $content;
                    }
                }
            }
            if (!is_null($contentObject)) {
                $value = $contentObject->getName();
            } else {
                $value = $attachment->getValue();
            }
            if ($allowContent && (!isset($aArray[$attachment->getLabel()->getName()]) || !is_array($aArray[$attachment->getLabel()->getName()]))) {
                $aArray[$attachment->getLabel()->getName()] = array();
            }
            if ($allowContent) {
                $aArray[$attachment->getLabel()->getName()][] = $value;
            } else {
                $aArray[$attachment->getLabel()->getName()] = $value;
            }
        }
        $aReturn = array();
        foreach ($aArray as $name => $object) {
            if (is_array($object)) {
                $aReturn[] = array("label" => $name, "values" => $object);
            } else {
                $aReturn[] = array("label" => $name, "value" => $object);
            }
        }
        return $aReturn;
    }

    /**
     * @param Attachment $attachment
     * @return array
     */
    public static function serializeObservationAttachment(Attachment $attachment)
    {
        $contentObject = null;
        if ($attachment->getLabel()->allowContentType()) {
            foreach ($attachment->getLabel()->getContents() as $content) {
                if ($content->getId() == intval($attachment->getValue())) {
                    $contentObject = $content;
                }
            }
        }
        if (!is_null($contentObject)) {
            $value = $contentObject->getName();
        } else {
            $value = $attachment->getValue();
        }
        return array(
            'label' => $attachment->getLabel()->getName(),
            'value' => $value,
        );
    }

    /**
     * @param AttachmentReceiver $attachmentreceiver
     * @return array
     */
    public static function serializeObservationAttachmentReceiver(AttachmentReceiver $attachmentreceiver)
    {
        $contentObject = null;
        if ($attachmentreceiver->getLabel()->allowContentType()) {
            foreach ($attachmentreceiver->getLabel()->getContents() as $content) {
                if ($content->getId() == intval($attachmentreceiver->getValue())) {
                    $contentObject = $content;
                }
            }
        }
        if (!is_null($contentObject)) {
            $value = $contentObject->getName();
        } else {
            $value = $attachmentreceiver->getValue();
        }
        return array(
            'label' => $attachmentreceiver->getLabel()->getName(),
            'value' => $value,
        );
    }

    /**
     * @param Attachment $attachment
     * @return array
     */
    public static function serializeObservationAttachmentBO(Attachment $attachment)
    {
        $contentObject = null;
        if ($attachment->getLabel()->allowContentType()) {
            foreach ($attachment->getLabel()->getContents() as $content) {
                if ($content->getId() == intval($attachment->getValue())) {
                    $contentObject = $content;
                }
            }
        }
        if (!is_null($contentObject)) {
            $value = $contentObject->getName();
        } else {
            $value = $attachment->getValue();
        }
        return array(
            'label' => $attachment->getLabel()->getName(),
            'value' => $value,
        );
    }

    /**
     * @param Receiver $receiver
     * @return array
     */
    public static function serializeObservationSharing(Receiver $receiver)
    {
        return array(
            'id' => $receiver->getId(),
            'name' => $receiver->getName(),
        );
    }

    /**
     * @param Observation $observation
     * @return array
     */
    public static function serializeObservationBO(Observation $observation)
    {
        $tree = CategorySerialization::serializeCategoryTree($observation->getCategory());
        if ($observation->getSpecific() == 1 && !is_null($observation->getAnimal())) {
            $tree[] = $observation->getAnimal()->getName_fr();
        }
        $aAttachments = ObservationSerialization::serializeObservationAttachmentBOs($observation->getAttachments());
        $aAttchmentsToDisplay = array();
        foreach ($aAttachments as $attachment) {
            if (!array_key_exists($attachment["label"], $aAttchmentsToDisplay)) {
                $aAttchmentsToDisplay[$attachment["label"]] = $attachment;
            } else {
                $aAttchmentsToDisplay[$attachment["label"]]["value"] = $aAttchmentsToDisplay[$attachment["label"]]["value"] . " - " . $attachment["value"];
            }
        }
        $aAttchmentsToShow = array();
        foreach ($aAttchmentsToDisplay as $attachment) {
            $aAttchmentsToShow[] = $attachment;
        }

        return array(
            'id' => $observation->getId(),
            'name' => ($observation->getCategory() instanceof Category) ? $observation->getCategory()->getName() : "",
            'tree' => $tree,
            'attachments' => $aAttchmentsToShow,
        );
    }

    /**
     * @param ObservationReceiver $observationreceiver
     * @return array
     */
    public static function serializeObservationReceiver(ObservationReceiver $observationreceiver)
    {
        $tree = CategorySerialization::serializeCategoryTree($observationreceiver->getCategory());
        if ($observationreceiver->getSpecific() == 1 && !is_null($observationreceiver->getAnimal())) {
            $tree[] = $observationreceiver->getAnimal()->getName_fr();
        }
        $aAttachments = ObservationSerialization::serializeObservationAttachmentReceivers($observationreceiver->getAttachmentreceivers());
        $aAttchmentsToDisplay = array();
        foreach ($aAttachments as $attachment) {
            if (!array_key_exists($attachment["label"], $aAttchmentsToDisplay)) {
                $aAttchmentsToDisplay[$attachment["label"]] = $attachment;
            } else {
                $aAttchmentsToDisplay[$attachment["label"]]["value"] = $aAttchmentsToDisplay[$attachment["label"]]["value"] . " - " . $attachment["value"];
            }
        }
        $aAttchmentsToShow = array();
        foreach ($aAttchmentsToDisplay as $attachment) {
            $aAttchmentsToShow[] = $attachment;
        }

        return array(
            'id' => $observationreceiver->getId(),
            'name' => ($observationreceiver->getCategory() instanceof Category) ? $observationreceiver->getCategory()->getName() : "",
            'tree' => $tree,
            'attachments' => $aAttchmentsToShow,
        );
    }

}
