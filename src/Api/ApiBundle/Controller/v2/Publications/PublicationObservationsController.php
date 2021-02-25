<?php

/**
 * Created by PhpStorm.
 * User: vincentvalot
 * Date: 28/07/15
 * Time: 15:20
 */

namespace Api\ApiBundle\Controller\v2\Publications;

use Api\ApiBundle\Controller\v2\ApiRestController;
use Api\ApiBundle\Controller\v2\Serialization\PublicationSerialization;
use Api\ApiBundle\Controller\v2\Serialization\ObservationSerialization;
use Api\ApiBundle\Controller\v2\Serialization\AnimalSerialization;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\QueryBuilder;
use FOS\RestBundle\Util\Codes;
use NaturaPass\GraphBundle\Entity\Edge;
use NaturaPass\MainBundle\Component\Security\SecurityUtilities;
use NaturaPass\NotificationBundle\Entity\Publication\PublicationCommentedNotification;
use NaturaPass\NotificationBundle\Entity\Publication\PublicationSameCommentedNotification;
use NaturaPass\ObservationBundle\Entity\AttachmentReceiver;
use NaturaPass\ObservationBundle\Entity\ObservationReceiver;
use NaturaPass\PublicationBundle\Entity\Publication;
use NaturaPass\PublicationBundle\Entity\PublicationAction;
use NaturaPass\PublicationBundle\Entity\PublicationComment;
use NaturaPass\PublicationBundle\Entity\PublicationCommentAction;
use NaturaPass\PublicationBundle\Form\Type\PublicationCommentFormType;
use Symfony\Component\HttpFoundation\Request;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Symfony\Component\HttpKernel\Exception\HttpException;
use NaturaPass\ObservationBundle\Entity\Observation;
use NaturaPass\ObservationBundle\Form\Type\ObservationType;

class PublicationObservationsController extends ApiRestController
{

    /**
     * Récupère les observations d'une publication
     *
     * GET /v2/publications/{publication}/observation
     *
     * @param \NaturaPass\PublicationBundle\Entity\Publication $publication
     *
     * @return \FOS\RestBundle\View\View
     *
     * @ParamConverter("publication", class="NaturaPassPublicationBundle:Publication")
     */
    public function getObservationsAction(Publication $publication)
    {
        return $this->view(
            array(
                'observations' => ObservationSerialization::serializeObservations(
                    $publication->getObservations()
                )
            ), Codes::HTTP_OK
        );
    }

    /**
     * Retourne les animaux
     *
     * GET /v2/publication/observation/animals?filter=lapin&limit=20&offset=0
     *
     * @param Request $request
     * @return \FOS\RestBundle\View\View
     *
     */
    public function getObservationAnimalsAction(Request $request)
    {
        $this->authorize();

        $limit = $request->query->get('limit', 10);
        $offset = $request->query->get('offset', 0);
        $filter = urldecode($request->query->get('filter', ''));
	$k = strlen($filter);
        if ($k > 4 && (substr($filter, -1) == 's'))
        {
            $filter = substr($filter, 0, -1);
        }
        $manager = $this->getDoctrine()->getManager();

        /**
         * @var QueryBuilder $qb
         */
        $qb = $manager->createQueryBuilder()->select('a')
            ->from('AdminAnimalBundle:Animal', 'a')
            ->where('a.name_fr LIKE :name_fr')
            ->orderBy('a.name_fr', 'ASC')
            ->setParameter('name_fr', '%' . $filter . '%')
            ->setFirstResult($offset)
            ->setMaxResults($limit);

        $results = $qb->getQuery()->getResult();

        return $this->view(array('animals' => AnimalSerialization::serializeAnimals($results)), Codes::HTTP_OK);
    }

    /**
     * Ajoute une observation sur une publication
     *
     * POST /v2/publications/{publication}/observations
     *
     * @param Request $request
     * @param \NaturaPass\PublicationBundle\Entity\Publication
     * @return \FOS\RestBundle\View\View
     *
     * @ParamConverter("publication", class="NaturaPassPublicationBundle:Publication")
     */
    public function postObservationAction(Request $request, Publication $publication)
    {
        $this->authorize($publication->getOwner());
        $params = $request->request->get('observation');
        $receivers = $params["receivers"];
        unset($params["receivers"]);
        $arrayObservation = array(
            'observation' => $params);
        $requestObservation = new Request($_GET, $arrayObservation, array(), $_COOKIE, $_FILES, $_SERVER);

        $observation = new Observation();
        $form = $this->createForm(new ObservationType($publication), $observation, array('csrf_protection' => false));

        $form->handleRequest($requestObservation);

        if ($form->isValid()) {
            $manager = $this->getDoctrine()->getManager();

            $attachments = $observation->getAttachments();

            $observation->setAttachments(array());
            $manager->persist($observation);
            $manager->flush();
            foreach ($attachments as $attachment) {
                $manager->persist($attachment);
                $observation->addAttachment($attachment);
            }
            $em = $this->getDoctrine()->getManager();
            foreach ($receivers as $array) {
                $receiver = $em->getRepository('AdminSentinelleBundle:Receiver')->find($array["receiver"]);
                if (!is_null($receiver)) {
                    $observation->addReceiver($receiver);
                    $manager->persist($observation);
                    $manager->flush();
                    $observationReceiver = new ObservationReceiver();
                    $observationReceiver->duplicateObservation($observation);
                    $observationReceiver->setReceiver($receiver);
                    $manager->persist($observationReceiver);
                    $manager->flush();
                    foreach ($observation->getAttachments() as $attachment) {
                        $attachmentreceiver = new AttachmentReceiver();
                        $attachmentreceiver->duplicateAttachment($attachment);
                        $attachmentreceiver->setObservationreceiver($observationReceiver);
                        $manager->persist($attachmentreceiver);
                        $manager->flush();
                    }
                }
            }
            $manager->flush();

            return $this->view(
                array(
                    'publication' => PublicationSerialization::serializePublication(
                        $publication, $this->getUser()
                    )
                ), Codes::HTTP_CREATED
            );
        }

        return $this->view($form->getErrors(true, false), Codes::HTTP_BAD_REQUEST);
    }

    /**
     * Modifie une observation sur une publication
     *
     * PUT /v2/publications/{observation}/observation
     *
     * @param Request $request
     * @param Observation $observation
     *
     * @return \FOS\RestBundle\View\View
     *
     * @ParamConverter("observation", class="NaturaPassObservationBundle:Observation")
     */
    public function putObservationAction(Request $request, Observation $observation)
    {
        $this->authorize($observation->getPublication()->getOwner());
        $receivers = array();
        foreach ($observation->getReceivers() as $receiver) {
            $receivers[] = $receiver;
        }
        $params = $request->request->get('observation');
        unset($params["receivers"]);
        $arrayObservation = array(
            'observation' => $params);
        $requestObservation = new Request($_GET, $arrayObservation, array(), $_COOKIE, $_FILES, $_SERVER);

        $originalAttachments = new ArrayCollection();
        foreach ($observation->getAttachments() as $attachment) {
            $originalAttachments->add($attachment);
        }

        $form = $this->createForm(
            new ObservationType($observation->getPublication()), $observation, array('csrf_protection' => false, 'method' => 'PUT')
        );

        $form->submit($requestObservation);

        if ($form->isValid()) {
            $manager = $this->getDoctrine()->getManager();
            foreach ($receivers as $receiver) {
                $observation->addReceiver($receiver);
                $manager->merge($observation);
            }
            foreach ($observation->getAttachments() as $attachment) {
                $manager->persist($attachment);
            }
            $manager->flush();
            foreach ($originalAttachments as $attachment) {
                if (false === $observation->getAttachments()->contains($attachment)) {
                    $manager->remove($attachment);
                } else {
                    $manager->persist($attachment);
                }
            }

            $manager->persist($observation);
            $manager->flush();

            return $this->view(
                array(
                    'observation' => ObservationSerialization::serializeObservation(
                        $observation
                    )
                ), Codes::HTTP_OK
            );
        }

        return $this->view($form->getErrors(true, false), Codes::HTTP_BAD_REQUEST);
    }

    /**
     * Modifie une observation sur une publication
     *
     * PUT /v2/publications/{observation}/observation/website
     *
     * @param Request $request
     * @param Observation $observation
     *
     * @return \FOS\RestBundle\View\View
     *
     * @ParamConverter("observation", class="NaturaPassObservationBundle:Observation")
     */
    public function putObservationWebsiteAction(Request $request, Observation $observation)
    {
        $this->authorize($observation->getPublication()->getOwner());
        $receivers = array();
        foreach ($observation->getReceivers() as $receiver) {
            $receivers[] = $receiver;
        }
        $params = $request->request->get('observation');
        unset($params["receivers"]);
        $arrayObservation = array('observation' => $params);
        $requestObservation = new Request($_GET, $arrayObservation, array(), $_COOKIE, $_FILES, $_SERVER);

        $originalAttachments = new ArrayCollection();
        foreach ($observation->getAttachments() as $attachment) {
            $originalAttachments->add($attachment);
        }

        $form = $this->createForm(
            new ObservationType($observation->getPublication()), $observation, array('csrf_protection' => false, 'method' => 'PUT')
        );

        $form->handleRequest($requestObservation);

        if ($form->isValid()) {
            $manager = $this->getDoctrine()->getManager();
            foreach ($receivers as $receiver) {
                $observation->addReceiver($receiver);
                $manager->merge($observation);
            }

            foreach ($observation->getAttachments() as $attachment) {
                $manager->persist($attachment);
            }
            $manager->flush();
            foreach ($originalAttachments as $attachment) {
                if (false === $observation->getAttachments()->contains($attachment)) {
                    $manager->remove($attachment);
                } else {
                    $manager->persist($attachment);
                }
            }

            $manager->persist($observation);
            $manager->flush();


            return $this->view(
                array(
                    'publication' => PublicationSerialization::serializePublication(
                        $observation->getPublication(), $this->getUser()
                    )
                ), Codes::HTTP_CREATED
            );
        }

        return $this->view($form->getErrors(true, false), Codes::HTTP_BAD_REQUEST);
    }

    /**
     * supprime une observation
     *
     * DELETE /v2/publications/{observation}/observation
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
