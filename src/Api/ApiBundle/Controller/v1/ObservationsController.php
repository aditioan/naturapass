<?php

/**
 * Created by PhpStorm.
 * User: vincentvalot
 * Date: 25/06/15
 * Time: 14:32
 */

namespace Api\ApiBundle\Controller\v1;

use Doctrine\Common\Collections\ArrayCollection;
use FOS\RestBundle\Util\Codes;
use NaturaPass\ObservationBundle\Entity\Observation;
use NaturaPass\ObservationBundle\Form\Type\ObservationType;
use NaturaPass\PublicationBundle\Entity\Publication;
use Symfony\Component\HttpFoundation\Request;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use FOS\RestBundle\Controller\Annotations\View;

class ObservationsController extends ApiRestController
{

    /**
     * Get observation data
     *
     * @param Observation $observation
     * @return \FOS\RestBundle\View\View
     *
     * @ParamConverter("observation", class="NaturaPassObservationBundle:Observation")
     * @View(serializerGroups={"ObservationDetail", "AttachmentDetail"})
     */
    public function getObservationAction(Observation $observation)
    {
        $this->authorize();

        return $this->view(array('observation' => $this->getFormatObservationDetail($observation)), Codes::HTTP_OK);
    }

    /**
     * Add an observation
     *
     * @param Request $request
     * @param Publication $publication
     *
     * @return \FOS\RestBundle\View\View
     *
     * @ParamConverter("publication", class="NaturaPassPublicationBundle:Publication")
     * @View(serializerGroups={"ObservationDetail", "AttachmentDetail"})
     */
    public function postObservationAction(Request $request, Publication $publication)
    {
        $this->authorize($publication->getOwner());

        $observation = new Observation();
        $form = $this->createForm(new ObservationType($publication), $observation, array('csrf_protection' => false));

        $form->handleRequest($request);

        if ($form->isValid()) {
            $manager = $this->getDoctrine()->getManager();

            $attachments = $observation->getAttachments();
            $observation->setAttachments(array());
            $manager->persist($observation);

            foreach ($observation->getReceivers() as $observationreceiver) {
                $observationreceiver->setAttachmentreceivers(array());
                $manager->persist($observationreceiver);
            }
            $manager->flush();
            foreach ($attachments as $attachment) {
                $manager->persist($attachment);
                $observation->addAttachment($attachment);
                foreach ($observation->getReceivers() as $observationreceiver) {
                    $attachmentreceiver = new AttachmentReceiver();
                    $attachmentreceiver->duplicateAttachment($attachment);
                    $attachmentreceiver->setObservationreceiver($observationreceiver);
                    $manager->persist($attachmentreceiver);
                }
            }

            $manager->flush();

            return $this->view(array('observation' => $this->getFormatObservationDetail($observation)), Codes::HTTP_CREATED);
        }

        return $this->view($form->getErrors(true, false), Codes::HTTP_BAD_REQUEST);
    }

    /**
     * Add an observation
     *
     * @param Request $request
     * @param Observation $observation
     *
     * @return \FOS\RestBundle\View\View
     *
     * @ParamConverter("observation", class="NaturaPassObservationBundle:Observation")
     * @View(serializerGroups={"ObservationDetail", "AttachmentDetail"})
     */
    public function putObservationAction(Request $request, Observation $observation)
    {
        $this->authorize($observation->getPublication()->getOwner());

        $originalAttachments = new ArrayCollection();
        foreach ($observation->getAttachments() as $attachment) {
            $originalAttachments->add($attachment);
        }

        $form = $this->createForm(
            new ObservationType($observation->getPublication()), $observation, array('csrf_protection' => false, 'method' => 'PUT')
        );

        $form->submit($request);

        if ($form->isValid()) {
            $manager = $this->getDoctrine()->getManager();

            foreach ($originalAttachments as $attachment) {
                if (false === $observation->getAttachments()->contains($attachment)) {
                    $manager->remove($attachment);
                } else {
                    $manager->persist($attachment);
                }
            }

            $manager->persist($observation);
            $manager->flush();

            return $this->view(array('observation' => $this->getFormatObservationDetail($observation)), Codes::HTTP_OK);
        }

        return $this->view($form->getErrors(true, false), Codes::HTTP_BAD_REQUEST);
    }

    /**
     * Delete an observation
     *
     * @param Observation $observation
     *
     * @return \FOS\RestBundle\View\View
     *
     * @ParamConverter("observation", class="NaturaPassObservationBundle:Observation")
     */
    public function deleteObservationAction(Observation $observation)
    {
        $this->authorize($observation->getPublication()->getOwner());

        $manager = $this->getDoctrine()->getManager();

        $manager->remove($observation);
        $manager->flush();

        return $this->view($this->success(), Codes::HTTP_OK);
    }

}
