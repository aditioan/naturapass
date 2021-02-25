<?php

/**
 * Created by PhpStorm.
 * User: vincentvalot
 * Date: 07/08/15
 * Time: 10:48
 */

namespace Api\ApiBundle\Controller\v2\Groups;

use Api\ApiBundle\Controller\v2\ApiRestController;
use Api\ApiBundle\Controller\v2\Serialization\GroupSerialization;
use FOS\RestBundle\Util\Codes;
use NaturaPass\GroupBundle\Entity\Group;
use NaturaPass\GroupBundle\Entity\GroupMessage;
use NaturaPass\NotificationBundle\Entity\Group\GroupMessageNotification;
use NaturaPass\GroupBundle\Entity\GroupUser;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Api\ApiBundle\Controller\v2\Serialization\PublicationSerialization;
use NaturaPass\MainBundle\Component\Security\SecurityUtilities;
use NaturaPass\MainBundle\Entity\Geolocation;

class GroupMessagesController extends ApiRestController
{

    /**
     * Retourne les messages du fil de discussion du groupe
     *
     * GET /v2/groups/{group}/messages?limit=10&loaded=5&previous=1
     *
     * previous est un paramètre permettant de spécifier si l'on doit récupérer les messages avant la date spécifiée
     * limit peut être spécifié pour une récupération de commentaire ultérieurs OU pour une première récupération des commentaires
     * offset est utilisé pour la récupération des messages antérieurs
     *
     * @param Request $request
     * @param Group $group
     *
     * @ParamConverter("group", class="NaturaPassGroupBundle:Group")
     *
     * @return \FOS\RestBundle\View\View
     */
    public function getMessagesAction(Request $request, Group $group)
    {
        $this->authorize();
        $this->authorizeGroup($group);
        if ($group->getAllowShowChat() == Group::ALLOW_ADMIN && !$group->isSubscriber($this->getUser(), array(GroupUser::ACCESS_ADMIN))) {
            return $this->view(array('messages' => array(), 'total' => 0), Codes::HTTP_OK);
        } else {
            $limit = $request->query->get('limit', 10);
            $loaded = $request->query->get('loaded', 0);
            $previous = $request->query->get('previous', false);
            $total = count($group->getMessages());
            if ($total - $loaded <= 0) {
                return $this->view(array('messages' => array(), 'total' => $total), Codes::HTTP_OK);
            }
            $qb = $this->getDoctrine()->getManager()->getRepository(
                'NaturaPassGroupBundle:GroupMessage'
            )->createQueryBuilder('m');
            $qb->where('m.group = :group')
                ->setParameter('group', $group)
                ->orderBy('m.created', 'DESC');
            if ($previous) {
                $qb->setFirstResult($total - $loaded);
            }
            if ($limit) {
                $qb->setMaxResults($limit);
            }
            $messages = $qb
                ->getQuery()
                ->getResult();

            return $this->view(array('messages' => GroupSerialization::serializeGroupMessages($messages), 'total' => $total), Codes::HTTP_OK);
        }
    }

    /**
     * Retourne les messages du fil de discussion du group
     *
     * GET /v2/groups/{group}/message/offset?limit=10&offset=10
     *
     * limit peut être spécifié pour une récupération de commentaire ultérieurs OU pour une première récupération des commentaires
     * offset est utilisé pour la récupération des messages antérieurs
     *
     * @param Request $request
     * @param Group $group
     *
     * @ParamConverter("group", class="NaturaPassGroupBundle:Group")
     *
     * @return \FOS\RestBundle\View\View
     */
    public function getMessageOffsetAction(Request $request, Group $group)
    {
        $this->authorize();
        $this->authorizeGroup($group);
        if ($group->getAllowShowChat() == Group::ALLOW_ADMIN && !$group->isSubscriber($this->getUser(), array(GroupUser::ACCESS_ADMIN))) {
            return $this->view(array('messages' => array(), 'total' => 0), Codes::HTTP_OK);
        } else {
            $limit = $request->query->get('limit', 10);
            $offset = $request->query->get('offset', 0);
            $total = count($group->getMessages());

            $qb = $this->getDoctrine()->getManager()->getRepository(
                'NaturaPassGroupBundle:GroupMessage'
            )->createQueryBuilder('m');
            $messages = $qb->where('m.group = :group')
                ->setParameter('group', $group)
                ->orderBy('m.created', 'DESC')
                ->setFirstResult($offset)
                ->setMaxResults($limit)
                ->getQuery()
                ->getResult();

            return $this->view(array('messages' => GroupSerialization::serializeGroupMessages($messages), 'total' => $total), Codes::HTTP_OK);
        }
    }

    /**
     * Ajoute un message au fil de discussion d'un salon pour l'utilisateur connecté, si il est inscrit (defaut ou admin)
     *
     * POST /v2/groups/{group}/messages
     *
     * JSON LIE
     * {
     *      "content": "Ça sent le sapin"
     * }
     *
     * @param Group $group
     * @param Request $request
     *
     * @throws HttpException
     * @return \FOS\RestBundle\View\View
     *
     * @ParamConverter("group", class="NaturaPassGroupBundle:Group")
     */
    public function postMessageAction(Group $group, Request $request)
    {
        $this->authorize();
        $this->authorizeGroup($group);
        if (!$group->checkAllowAddChat($this->getUser())) {
            throw new HttpException(Codes::HTTP_FORBIDDEN, $this->message('codes.403'));
        }
        $content = $request->request->get('content', null);
        $time = $request->request->get('create_time', null);
        if ($group->isSubscriber($this->getUser())) {
            if ($content) {
                $message = new GroupMessage;
                $message->setGroup($group)
                    ->setOwner($this->getUser())
                    ->setContent(SecurityUtilities::sanitize($content))
                    ->setCreated(isset($time) ? new \DateTime($time) : new \DateTime());
                //vietlh
                if(!is_null($guid)){
                    $message->setGuid($guid);
                }
                //
                $manager = $this->getDoctrine()->getManager();
                $manager->persist($message);
                $manager->flush();

//vietlh
                $groupMessage = $message;
                $query = $manager
                    ->createQueryBuilder('p')->select('p')
                    ->from('NaturaPassGroupBundle:GroupMessage', 'p')
                    ->where('p.owner = :user')                    
                    ->andWhere('p.group = :group')
                    ->andWhere('p.id != :id');
                if(!is_null($groupMessage->getGuid())){
                    $query->andWhere('p.guid = :guid');
                    $query->setParameter('guid', $groupMessage->getGuid());
                }
                else{
                    $query->andWhere('p.content = :content');
                    $query->setParameter('content', $groupMessage->getContent());
                    $query->andWhere('p.created = :date');
                    $query->setParameter('date', $groupMessage->getCreated()->format("Y-m-d H:i:s"));
                }
                $query->setParameter('user', $this->getUser());
                $query->setParameter('id', $groupMessage->getId());
                $query->setParameter('group', $groupMessage->getGroup()->getId());
                $response = $query->getQuery()->getResult();
                if (count($response)) {
                    $checkDuplicate = $response;
                }

                if ($checkDuplicate) {
                    foreach ($checkDuplicate as $msg) {
                        $manager = $this->getDoctrine()->getManager();
                        $manager->remove($msg);
                        $manager->flush();
                    }
                    
                }
//end
                
                $groupSubscribers = $group->getSubscribers(
                    array(GroupUser::ACCESS_DEFAULT, GroupUser::ACCESS_ADMIN), true
                );

                $groupSubscribers->removeElement($this->getUser());

                $subscribes = array();
                $ids = array();
                foreach ($groupSubscribers as $groupSubscriber) {
                    if (($group->getAllowShowChat() == Group::ALLOW_ALL_MEMBERS) || $group->isAdmin($groupSubscriber)) {
                        $subscribes[] = $groupSubscriber;
                        $ids[] = $groupSubscriber->getId();
                    }
                }

                $this->getNotificationService()->queue(
                    new GroupMessageNotification($group, $message), $subscribes
                );

                return $this->view(array('message' => GroupSerialization::serializeGroupMessage($message)), Codes::HTTP_OK);
            }
            throw new HttpException(Codes::HTTP_BAD_REQUEST, $this->message('errors.group.message.empty'));
        }
        throw new HttpException(Codes::HTTP_FORBIDDEN, $this->message('errors.group.subscriber.unregistered'));
    }

    /**
     * Supprimer un message du fil de discussion d'un salon
     *
     * DELETE /v2/groups/{message_id}/message
     *
     * @param GroupMessage $message
     * @return \FOS\RestBundle\View\View
     *
     * @ParamConverter("message", class="NaturaPassGroupBundle:GroupMessage")
     */
    public function deleteMessageAction(GroupMessage $message)
    {
        $this->authorize($message->getGroup()->getAdmins());
        $this->authorizeGroup($message->getGroup());
        $manager = $this->getDoctrine()->getManager();
        $manager->remove($message);
        $manager->flush();

        return $this->view($this->success(), Codes::HTTP_OK);
    }

}
