<?php

namespace Api\ApiBundle\Controller\Admin;

use Symfony\Component\HttpFoundation\Request;
use FOS\RestBundle\Controller\Annotations\View;
use FOS\RestBundle\Util\Codes;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Admin\SentinelleBundle\Entity\Receiver;
use Api\ApiBundle\Controller\v1\ApiRestController;

/**
 * Description of GroupsController
 *
 * @author vincentvalot
 */
class ReceiverController extends ApiRestController {

    /**
     * FR : Retourne les donnÃ©es d'un destinataire
     * EN : Returns data of a receiver
     *
     * GET /admin/receivers/{ID_RECEVER}
     *
     * @param \Admin\SentinelleBundle\Entity\Receiver $receiver
     * @return \FOS\RestBundle\View\View
     *
     * @View(serializerGroups={"ReceiverDetail","GeolocationDetail"})
     * @ParamConverter("receiver", class="AdminSentinelleBundle:Receiver")
     */
    public function getReceiverAction(Receiver $receiver) {
        $this->authorize(null, 'ROLE_ADMIN');

        return $this->view(array('receiver' => $this->getFormatReceiverDetail($receiver)), Codes::HTTP_OK);
    }

    /**
     * FR : Retourne les donnÃ©es des localités d'un destinataire
     * EN : Returns locality's data of a receiver
     *
     * GET /admin/receivers/{ID_RECEVER}/locality
     *
     * @param \Admin\SentinelleBundle\Entity\Receiver $receiver
     * @return \FOS\RestBundle\View\View
     *
     * @View(serializerGroups={"ReceiverDetail","GeolocationDetail"})
     * @ParamConverter("receiver", class="AdminSentinelleBundle:Receiver")
     */
    public function getReceiverLocalityAction(Receiver $receiver) {
        $this->authorize(null, 'ROLE_ADMIN');
        return $this->view(array('localities' => \Api\ApiBundle\Controller\v2\Serialization\LocalitySerialization::serializeLocalitySearchs($receiver->getLocalities())), Codes::HTTP_OK);
    }

    /**
     * FR : Retourne les donnÃ©es des groupes d'un destinataire
     * EN : Returns group's data of a receiver
     *
     * GET /admin/receivers/{ID_RECEVER}/group
     *
     * @param \Admin\SentinelleBundle\Entity\Receiver $receiver
     * @return \FOS\RestBundle\View\View
     *
     * @View(serializerGroups={"ReceiverDetail","GeolocationDetail"})
     * @ParamConverter("receiver", class="AdminSentinelleBundle:Receiver")
     */
    public function getReceiverGroupAction(Receiver $receiver) {
        $this->authorize(null, 'ROLE_ADMIN');
        return $this->view(array('groups' => \Api\ApiBundle\Controller\v2\Serialization\GroupSerialization::serializeGroupSearchs($receiver->getGroups())), Codes::HTTP_OK);
    }

    /**
     * FR : Retourne les donnÃ©es des utilisateurs d'un destinataire
     * EN : Returns user's data of a receiver
     *
     * GET /admin/receivers/{ID_RECEVER}/user
     *
     * @param \Admin\SentinelleBundle\Entity\Receiver $receiver
     * @return \FOS\RestBundle\View\View
     *
     * @View(serializerGroups={"ReceiverDetail","GeolocationDetail"})
     * @ParamConverter("receiver", class="AdminSentinelleBundle:Receiver")
     */
    public function getReceiverUserAction(Receiver $receiver) {
        $this->authorize(null, 'ROLE_ADMIN');
        return $this->view(array('users' => \Api\ApiBundle\Controller\v2\Serialization\UserSerialization::serializeUserLesss($receiver->getUsers())), Codes::HTTP_OK);
    }

    /**
     * FR : Retourne les destinataires
     * EN : Returns receivers
     *
     * GET /admin/receivers?limit=10&offset=0&filter=test
     *
     * @param Request $request
     * @return \FOS\RestBundle\View\View
     */
    public function getReceiversAction(Request $request) {
        $this->authorize(null, 'ROLE_ADMIN');

        $limit = $request->query->get('limit', 10);
        $offset = $request->query->get('offset', 0);
        $filter = urldecode($request->query->get('filter', ''));

        $manager = $this->getDoctrine()->getManager();
        $qb = $manager->createQueryBuilder()->select('f')
                ->from('AdminSentinelleBundle:Receiver', 'f')
                ->where('f.name LIKE :name')
                ->orderBy('f.name', 'ASC')
                ->setParameter('name', '%' . $filter . '%')
                ->setFirstResult($offset)
                ->setMaxResults($limit);

        $results = $qb->getQuery()->getResult();
        $receivers = array();

        foreach ($results as $result) {
            $receivers[] = $this->getFormatReceiverDetail($result);
        }

        return $this->view(array('receivers' => $receivers), Codes::HTTP_OK);
    }

    /**
     * FR : Supprime un destinataire de la BDD
     * EN : Remove a receiver of database
     *
     * GET /admins/receivers/{federation_id}
     *
     * @param Receiver $receiver
     * @return \FOS\RestBundle\View\View
     *
     * @ParamConverter("receiver", class="AdminSentinelleBundle:Receiver")
     */
    public function deleteReceiverAction(Receiver $receiver) {
        $this->authorize(null, 'ROLE_ADMIN');

        $manager = $this->getDoctrine()->getManager();

        $manager->remove($receiver);
        $manager->flush();

        return $this->view($this->success(), Codes::HTTP_NO_CONTENT);
    }

}
