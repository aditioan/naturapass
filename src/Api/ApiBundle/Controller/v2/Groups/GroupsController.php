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
use Api\ApiBundle\Controller\v2\Serialization\SqliteSerialization;
use FOS\RestBundle\Util\Codes;
use NaturaPass\GroupBundle\Entity\Group;
use NaturaPass\GroupBundle\Entity\GroupUser;
use NaturaPass\NotificationBundle\Entity\Group\SocketOnly\GroupChangeAllowNotification;
use NaturaPass\UserBundle\Entity\UserDeviceSended;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\HttpException;
use NaturaPass\GroupBundle\Form\Type\GroupType;
use NaturaPass\GroupBundle\Form\Handler\GroupHandler;
use NaturaPass\UserBundle\Entity\ParametersNotification;

class GroupsController extends ApiRestController
{

    /**
     * Return the group information
     *
     * GET /v2/groups/{group_id}
     *
     * @param \NaturaPass\GroupBundle\Entity\Group $group
     * @return array
     *
     * @ParamConverter("group", class="NaturaPassGroupBundle:Group")
     */
    public function getGroupAction(Group $group)
    {
        return $this->view(array('group' => GroupSerialization::serializeGroup($group, $this->getUser())), Codes::HTTP_OK);
    }

    /**
     * get groups invitations
     *
     * GET /v2/group/invitation
     *
     * @return array
     *
     */
    public function getGroupInvitationAction()
    {
        $this->authorize();

        $waitingValidation = array();

        $groupAdmin = $this->getUser()->getAllGroups(array(GroupUser::ACCESS_ADMIN));
        foreach ($groupAdmin as $group_admin) {
            $subscribers = $group_admin->getSubscribers(array(GroupUser::ACCESS_RESTRICTED));
            if ($subscribers->count()) {
                $waitingValidation = array_merge($waitingValidation, $subscribers->toArray());
            }
        }

        $waitingAccess = $this->getUser()->getAllGroups(array(GroupUser::ACCESS_RESTRICTED, GroupUser::ACCESS_INVITED));

        $return = array();
        $return["validations"] = !empty($waitingValidation) ? GroupSerialization::serializeGroupSubscribers($waitingValidation, $this->getUser(), true) : array();
        $return["access"] = !empty($waitingAccess) ? GroupSerialization::serializeGroups($waitingAccess, $this->getUser()) : array();
        return $this->view($return, Codes::HTTP_OK);
    }


    /**
     * FR : Ajoute un groupe à la base de données
     * EN : Add a group to the database
     *
     * POST /v2/groups
     *
     * Content-Type: form-data
     *      group[name] = "Battue dimanche"
     *      group[allow_add] = [0 => ADMIN, 1 => ALL_MEMBERS]
     *      group[allow_show] = [0 => ADMIN, 1 => ALL_MEMBERS]
     *      group[allow_add_chat] = [0 => ADMIN, 1 => ALL_MEMBERS]
     *      group[allow_show_chat] = [0 => ADMIN, 1 => ALL_MEMBERS]
     *      group[description] = "Une bonne battue entre amis"
     *      group[access] = [0 => Privé, 1 => Semi-privé, 2 => Public]
     *      group[photo][file] = Données de photo
     *
     *
     * @param Request $request
     * @return array
     *
     */
    public function postGroupAction(Request $request)
    {
        $this->authorize();
        $manager = $this->container->get('doctrine')->getManager();
        $form = $this->createForm(new GroupType($this->getUser(), $this->container), new Group(), array('csrf_protection' => false));
        $handler = new GroupHandler($form, $request, $this->getDoctrine()->getManager());

        if ($group = $handler->process()) {
            return $this->view(array(
                'group' => GroupSerialization::serializeGroup($group, $this->getUser(), true),
                "sqlite" => SqliteSerialization::serializeSqliteInserOrReplace("tb_group", array(GroupSerialization::serializeGroupSqliteRefresh(array(), null, $group, $this->getUser(), true)))
            ), Codes::HTTP_CREATED);
        }

        return $this->view($form->getErrors(true), Codes::HTTP_BAD_REQUEST);
    }

    /**
     * FR : Ajoute un groupe à la base de données
     * EN : Add a group to the database
     *
     * POST /v2/groupnews
     *
     * Content-Type: form-data
     *      group[name] = "Battue dimanche"
     *      group[allow_add] = [0 => ADMIN, 1 => ALL_MEMBERS]
     *      group[allow_show] = [0 => ADMIN, 1 => ALL_MEMBERS]
     *      group[allow_add_chat] = [0 => ADMIN, 1 => ALL_MEMBERS]
     *      group[allow_show_chat] = [0 => ADMIN, 1 => ALL_MEMBERS]
     *      group[description] = "Une bonne battue entre amis"
     *      group[access] = [0 => Privé, 1 => Semi-privé, 2 => Public]
     *      group[photo][file] = Données de photo
     *
     *
     * @param Request $request
     * @return array
     *
     */
    public function postGroupnewAction(Request $request)
    {
        $this->authorize();
        $manager = $this->container->get('doctrine')->getManager();

        $paramEmails = $manager->getRepository("NaturaPassEmailBundle:EmailModel")->findAll();
        $emails = array();
        foreach ($paramEmails as $email) {
            if (strpos($email->getType(), "group.") !== false) {
                $emails[] = $email;
            }
        }
        $metadata = $manager->getMetadataFactory()->getMetadataFor('NaturaPass\NotificationBundle\Entity\AbstractNotification');
        $arrayNotifications = array();
        foreach ($metadata->discriminatorMap as $notificationType => $notificationClass) {
            $arrayNotifications[$notificationType] = $notificationClass::PERIOD;;
        }
        $notification_parameter = array();
        $i=0;
        foreach ($arrayNotifications as $notification => $period) {
            if (strpos($notification, "group.") !== false) {
                $a[$i]['type'] = $notification;
                $a[$i]['wanted'] = 1;
                $notification_parameter[$notification] = $period;
                $i++;
            }
        }
        $b = $request->request->get('group');
        $b['notifications'] = $a;
        $request->request->set('group',$b);

        $form = $this->createForm(new GroupType($this->getUser(), $this->container), new Group(), array('csrf_protection' => false));

        //
        $handler = new GroupHandler($form, $request, $this->getDoctrine()->getManager());

        if ($group = $handler->process()) {
            //vietlh create parameters default with user when create group
                    $paras = $manager->getRepository("NaturaPassGroupBundle:GroupNotification");
                    $qb = $paras->createQueryBuilder('g')
                        ->where('g.group = :grID')
                        ->setParameter('grID', $group->getId())
                        ->getQuery();

                    $parameters = $qb->getArrayResult();
                    foreach ($parameters as $p) {

                        $parameters_id = $this->getUser()->getParameters();
                        $pref = new ParametersNotification();
                        $pref->setParameters($parameters_id)
                            ->setType($p['type']);

                        $pref->setWanted($p['wanted']);
                        $pref->setObjectID($group->getId());
                        $manager->persist($pref);

                        $manager->flush();

                    }
                    
                //
            return $this->view(array(
                'group' => GroupSerialization::serializeGroupnews($group, $this->getUser(), true),
                "sqlite" => SqliteSerialization::serializeSqliteInserOrReplace("tb_group", array(GroupSerialization::serializeGroupSqliteRefresh(array(), null, $group, $this->getUser(), true)))
            ), Codes::HTTP_CREATED);
        }

        return $this->view($form->getErrors(true), Codes::HTTP_BAD_REQUEST);
    }

    /**
     * Ajoute les chasses aux agendas du groupe
     *
     * POST /v2/groups/{group_id}/hunts
     *
     * Content-Type:
     *      hunts[] = 1, 2, 3
     *
     * @param Request $request
     * @param Group $group
     * @return \FOS\RestBundle\View\View
     *
     * @throws HttpException
     * @ParamConverter("group", class="NaturaPassGroupBundle:Group")
     */
    public function postGroupHuntAction(Request $request, Group $group)
    {
        if (!$group->isAdmin($this->getUser())) {
            throw new HttpException(Codes::HTTP_BAD_REQUEST, $this->message('codes.403'));
        }
        $hunts = $request->request->get('hunts', array());
        if (is_array($group) && strpos($hunts[0], ",")) {
            $hunts = explode(',', $hunts[0]);
        }

        $hunts = array_unique($hunts);
        $allhunt = array();
        $userHunts = $this->getUser()->getAllHunts();

        foreach ($hunts as $hunt) {
            foreach ($userHunts as $userHunt) {
                if ($hunt == $userHunt->getId() && $userHunt->isAdmin($this->getUser())) {
                    $allhunt[] = $hunt;
                    continue;
                }
            }
        }

        if (!$request->request->has('hunts')) {
            throw new HttpException(Codes::HTTP_BAD_REQUEST, $this->message('errors.parameters'));
        } else if (empty($allhunt)) {
            throw new HttpException(Codes::HTTP_BAD_REQUEST, $this->message('errors.lounge_admin'));
        }
        $manager = $this->getDoctrine()->getManager();
        foreach ($allhunt as $id_lounge) {
            $huntObject = $manager->getRepository('NaturaPassLoungeBundle:Lounge')->find($id_lounge);
            if (is_object($huntObject) && !$group->hasHunt($huntObject)) {
                $huntObject->addGroup($group);
                $manager->persist($huntObject);
            }
        }
        $manager->flush();
        return $this->view($this->success(), Codes::HTTP_OK);
    }


    /**
     * FR :
     * EN : Update group information
     *
     * PUT /groups/{group}/information
     *
     * Content-Type: form-data
     *      name = "Battue dimanche"
     *      description = "Une bonne battue entre amis"
     *
     *
     * @param Group $group
     * @param Request $request
     * @return \FOS\RestBundle\View\View
     *
     *
     * @ParamConverter("group", class="NaturaPassGroupBundle:Group")
     */
    public function putGroupInformationAction(Group $group, Request $request)
    {
        $this->authorize($group->getAdmins());
        $em = $this->getDoctrine()->getManager();
        $name = $request->request->get("name");
        $description = $request->request->get("description");
        $group->setName($name);
        $group->setDescription($description);
        $em->persist($group);
        $em->flush();
        $em->refresh($group);
        return $this->view(
            array(
                'success' => true,
                'group' => GroupSerialization::serializeGroup($group, $this->getUser(), true),
                "sqlite" => SqliteSerialization::serializeSqliteInserOrReplace("tb_group", array(GroupSerialization::serializeGroupSqliteRefresh(array(), null, $group, $this->getUser(), true)))
            ), Codes::HTTP_CREATED
        );
    }

    /**
     * FR :
     * EN : Update group access
     *
     * PUT /groups/{group}/access
     *
     * Content-Type: form-data
     *      access = [0 => Privé, 1 => Semi-privé, 2 => Public]
     *
     *
     * @param Group $group
     * @param Request $request
     * @return \FOS\RestBundle\View\View
     *
     *
     * @ParamConverter("group", class="NaturaPassGroupBundle:Group")
     */
    public function putGroupAccessAction(Group $group, Request $request)
    {
        $this->authorize($group->getAdmins());
        $em = $this->getDoctrine()->getManager();
        $access = $request->request->get("access");
        $group->setAccess($access);
        $em->persist($group);
        $em->flush();
        $em->refresh($group);
        return $this->view(
            array(
                'success' => true,
                'group' => GroupSerialization::serializeGroup($group, $this->getUser(), true),
                "sqlite" => SqliteSerialization::serializeSqliteInserOrReplace("tb_group", array(GroupSerialization::serializeGroupSqliteRefresh(array(), null, $group, $this->getUser(), true)))
            ), Codes::HTTP_CREATED
        );
    }

    /**
     * FR :
     * EN : Update group allow_add
     *
     * PUT /v2/groups/{group}/allowadd
     *
     * Content-Type: form-data
     *      allow_add = [0 => ADMIN, 1 => ALL_MEMBERS]
     *
     *
     * @param Group $group
     * @param Request $request
     * @return \FOS\RestBundle\View\View
     *
     *
     * @ParamConverter("group", class="NaturaPassGroupBundle:Group")
     */
    public function putGroupAllowaddAction(Group $group, Request $request)
    {
        $this->authorize($group->getAdmins());
        $em = $this->getDoctrine()->getManager();
        $allow_add = $request->request->get("allow_add", Group::ALLOW_ALL_MEMBERS);
        $group->setAllowAdd($allow_add);
        $em->persist($group);
        $em->flush();
        $em->refresh($group);

        $subscribers = $group->getSubscribers(array(GroupUser::ACCESS_DEFAULT, GroupUser::ACCESS_ADMIN), true);
        $subscribers->removeElement($this->getUser());
        $this->getNotificationService()->queue(
            new GroupChangeAllowNotification($group), $subscribers->toArray()
        );
        return $this->view(
            array(
                'success' => true,
                'group' => GroupSerialization::serializeGroup($group, $this->getUser(), true),
                "sqlite" => SqliteSerialization::serializeSqliteInserOrReplace("tb_group", array(GroupSerialization::serializeGroupSqliteRefresh(array(), null, $group, $this->getUser(), true)))
            ), Codes::HTTP_CREATED
        );
    }

    /**
     * FR :
     * EN : Update group allow_show
     *
     * PUT /v2/groups/{group}/allowshow
     *
     * Content-Type: form-data
     *      allow_show = [0 => ADMIN, 1 => ALL_MEMBERS]
     *
     *
     * @param Group $group
     * @param Request $request
     * @return \FOS\RestBundle\View\View
     *
     *
     * @ParamConverter("group", class="NaturaPassGroupBundle:Group")
     */
    public function putGroupAllowshowAction(Group $group, Request $request)
    {
        $this->authorize($group->getAdmins());
        $em = $this->getDoctrine()->getManager();
        $allow_show = $request->request->get("allow_show", Group::ALLOW_ALL_MEMBERS);
        $group->setAllowShow($allow_show);
        $em->persist($group);
        $em->flush();
        $em->refresh($group);

        $subscribers = $group->getSubscribers(array(GroupUser::ACCESS_DEFAULT, GroupUser::ACCESS_ADMIN), true);
        $subscribers->removeElement($this->getUser());
        $this->getNotificationService()->queue(
            new GroupChangeAllowNotification($group), $subscribers->toArray()
        );
        return $this->view(
            array(
                'success' => true,
                'group' => GroupSerialization::serializeGroup($group, $this->getUser(), true),
                "sqlite" => SqliteSerialization::serializeSqliteInserOrReplace("tb_group", array(GroupSerialization::serializeGroupSqliteRefresh(array(), null, $group, $this->getUser(), true)))
            ), Codes::HTTP_CREATED
        );
    }

    /**
     * FR :
     * EN : Update group allow_add
     *
     * PUT /v2/groups/{group}/allowadd
     *
     * Content-Type: form-data
     *      allow_add = [0 => ADMIN, 1 => ALL_MEMBERS]
     *      allow_show = [0 => ADMIN, 1 => ALL_MEMBERS]
     *
     *
     * @param Group $group
     * @param Request $request
     * @return \FOS\RestBundle\View\View
     *
     *
     * @ParamConverter("group", class="NaturaPassGroupBundle:Group")
     */
    public function putGroupAllowaddshowAction(Group $group, Request $request)
    {
        $this->authorize($group->getAdmins());
        $em = $this->getDoctrine()->getManager();
        $allow_add = $request->request->get("allow_add", Group::ALLOW_ALL_MEMBERS);
        $group->setAllowAdd($allow_add);
        $allow_show = $request->request->get("allow_show", Group::ALLOW_ALL_MEMBERS);
        $group->setAllowShow($allow_show);
        $em->persist($group);
        $em->flush();
        $em->refresh($group);

        $subscribers = $group->getSubscribers(array(GroupUser::ACCESS_DEFAULT, GroupUser::ACCESS_ADMIN), true);
        $subscribers->removeElement($this->getUser());
        $this->getNotificationService()->queue(
            new GroupChangeAllowNotification($group), $subscribers->toArray()
        );
        return $this->view(
            array(
                'success' => true,
                'group' => GroupSerialization::serializeGroup($group, $this->getUser(), true),
                "sqlite" => SqliteSerialization::serializeSqliteInserOrReplace("tb_group", array(GroupSerialization::serializeGroupSqliteRefresh(array(), null, $group, $this->getUser(), true)))
            ), Codes::HTTP_CREATED
        );
    }

    /**
     * FR :
     * EN : Update group allow_add_chat
     *
     * PUT /v2/groups/{group}/allowaddchat
     *
     * Content-Type: form-data
     *      allow_add_chat = [0 => ADMIN, 1 => ALL_MEMBERS]
     *
     *
     * @param Group $group
     * @param Request $request
     * @return \FOS\RestBundle\View\View
     *
     *
     * @ParamConverter("group", class="NaturaPassGroupBundle:Group")
     */
    public function putGroupAllowaddchatAction(Group $group, Request $request)
    {
        $this->authorize($group->getAdmins());
        $em = $this->getDoctrine()->getManager();
        $allow_add_chat = $request->request->get("allow_add_chat", Group::ALLOW_ALL_MEMBERS);
        $group->setAllowAddChat($allow_add_chat);
        $em->persist($group);
        $em->flush();
        $em->refresh($group);

        $subscribers = $group->getSubscribers(array(GroupUser::ACCESS_DEFAULT, GroupUser::ACCESS_ADMIN), true);
        $subscribers->removeElement($this->getUser());
        $this->getNotificationService()->queue(
            new GroupChangeAllowNotification($group), $subscribers->toArray()
        );
        return $this->view(
            array(
                'success' => true,
                'group' => GroupSerialization::serializeGroup($group, $this->getUser(), true),
                "sqlite" => SqliteSerialization::serializeSqliteInserOrReplace("tb_group", array(GroupSerialization::serializeGroupSqliteRefresh(array(), null, $group, $this->getUser(), true)))
            ), Codes::HTTP_CREATED
        );
    }

    /**
     * FR :
     * EN : Update group allow_show_chat
     *
     * PUT /v2/groups/{group}/allowshowchat
     *
     * Content-Type: form-data
     *      allow_show_chat = [0 => ADMIN, 1 => ALL_MEMBERS]
     *
     *
     * @param Group $group
     * @param Request $request
     * @return \FOS\RestBundle\View\View
     *
     *
     * @ParamConverter("group", class="NaturaPassGroupBundle:Group")
     */
    public function putGroupAllowshowchatAction(Group $group, Request $request)
    {
        $this->authorize($group->getAdmins());
        $em = $this->getDoctrine()->getManager();
        $allow_show_chat = $request->request->get("allow_show_chat", Group::ALLOW_ALL_MEMBERS);
        $group->setAllowShowChat($allow_show_chat);
        $em->persist($group);
        $em->flush();
        $em->refresh($group);

        $subscribers = $group->getSubscribers(array(GroupUser::ACCESS_DEFAULT, GroupUser::ACCESS_ADMIN), true);
        $subscribers->removeElement($this->getUser());
        $this->getNotificationService()->queue(
            new GroupChangeAllowNotification($group), $subscribers->toArray()
        );
        return $this->view(
            array(
                'success' => true,
                'group' => GroupSerialization::serializeGroup($group, $this->getUser(), true),
                "sqlite" => SqliteSerialization::serializeSqliteInserOrReplace("tb_group", array(GroupSerialization::serializeGroupSqliteRefresh(array(), null, $group, $this->getUser(), true)))
            ), Codes::HTTP_CREATED
        );
    }

    /**
     * FR :
     * EN : Update group allow_add_chat
     *
     * PUT /v2/groups/{group}/allowaddshowchat
     *
     * Content-Type: form-data
     *      allow_add_chat = [0 => ADMIN, 1 => ALL_MEMBERS]
     *      allow_show_chat = [0 => ADMIN, 1 => ALL_MEMBERS]
     *
     *
     * @param Group $group
     * @param Request $request
     * @return \FOS\RestBundle\View\View
     *
     *
     * @ParamConverter("group", class="NaturaPassGroupBundle:Group")
     */
    public function putGroupAllowaddshowchatAction(Group $group, Request $request)
    {
        $this->authorize($group->getAdmins());
        $em = $this->getDoctrine()->getManager();
        $allow_add_chat = $request->request->get("allow_add_chat", Group::ALLOW_ALL_MEMBERS);
        $group->setAllowAddChat($allow_add_chat);
        $allow_show_chat = $request->request->get("allow_show_chat", Group::ALLOW_ALL_MEMBERS);
        $group->setAllowShowChat($allow_show_chat);
        $em->persist($group);
        $em->flush();
        $em->refresh($group);

        $subscribers = $group->getSubscribers(array(GroupUser::ACCESS_DEFAULT, GroupUser::ACCESS_ADMIN), true);
        $subscribers->removeElement($this->getUser());
        $this->getNotificationService()->queue(
            new GroupChangeAllowNotification($group), $subscribers->toArray()
        );
        return $this->view(
            array(
                'success' => true,
                'group' => GroupSerialization::serializeGroup($group, $this->getUser(), true),
                "sqlite" => SqliteSerialization::serializeSqliteInserOrReplace("tb_group", array(GroupSerialization::serializeGroupSqliteRefresh(array(), null, $group, $this->getUser(), true)))
            ), Codes::HTTP_CREATED
        );
    }

    /**
     * FR :
     * EN : get sqlite
     *
     * PUT /group/sqlite
     *
     * {
     *  "identifier":"1,2,3,4,5",
     *  "updated":"1450860029"
     * }
     *
     * @param Request $request
     * @return \FOS\RestBundle\View\View
     *
     *
     */
    public function putGroupSqliteAction(Request $request)
    {
        $this->authorize();
        $manager = $this->getDoctrine()->getManager();
        $repoSended = $manager->getRepository("NaturaPassUserBundle:UserDeviceSended");
        $identifier = $request->request->get('identifier', false);
        if ($identifier) {
            $updated = $request->request->get('updated', false);
            $reload = $request->request->get('reload', false);
            if (!$updated || $updated == "") {
                $reload = true;
            }
            $sendedGroup = $repoSended->findOneBy(array("user" => $this->getUser(), "guid" => $identifier, "type" => UserDeviceSended::TYPE_GROUP));
            if (!$reload && !is_null($sendedGroup)) {
                $groupIds = $sendedGroup->getIdsArray();
            } else {
                $groupIds = array();
            }
            if (is_null($sendedGroup)) {
                $sendedGroup = new UserDeviceSended();
                $sendedGroup->setUser($this->getUser());
                $sendedGroup->setGuid($identifier);
                $sendedGroup->setType(UserDeviceSended::TYPE_GROUP);
            }
            $return = array("sqlite" => array());
            $groupReturn = GroupSerialization::serializeGroupSqlite($groupIds, $updated, $this->getUser(), $sendedGroup);
            $return["sqlite"] = array_merge($return["sqlite"], $groupReturn);
            $manager->persist($sendedGroup);
            $manager->flush();
            return $this->view($return, Codes::HTTP_OK);
        }
    }

    //vietlh
    /**
     * Return the group parameters default
     *
     * GET /v2/groups/{group_id}/parameters
     *
     * @param \NaturaPass\GroupBundle\Entity\Group $group
     * @return array
     *
     */
    public function getGroupParametersAction($groupID)
    {
        $this->authorize();
        $manager = $this->getDoctrine()->getManager();
        $paras = $manager->getRepository("NaturaPassGroupBundle:GroupNotification");
        $qb = $paras->createQueryBuilder('g')
            ->where('g.group = :grID')
            ->setParameter('grID', $groupID)
            ->getQuery();

        $parameters = $qb->getArrayResult();
        return $this->view(array('group_parameters' => $parameters), Codes::HTTP_OK);
    }

    //vietlh
    /**
     * Return the group parameters for users
     *
     * GET /v2/groups/{group_id}/parameters/user
     *
     * @param \NaturaPass\GroupBundle\Entity\Group $group
     * @return array
     *
     */
    public function getGroupParametersUserAction($groupID)
    {
        $this->authorize();
        $manager = $this->getDoctrine()->getManager();
        $parameters_id = $this->getUser()->getParameters();
        $paras = $manager->getRepository("NaturaPassUserBundle:ParametersNotification");
        $qb = $paras->createQueryBuilder('p')
            ->where('p.objectID = :grID')
            ->andWhere('p.parameters = :prID')
            ->setParameter('grID', $groupID)
            ->setParameter('prID', $parameters_id)
            ->getQuery();

        $parameters = $qb->getArrayResult();
        
        if(count($parameters) == 0){
                    $parameters[0]['type'] = 'group.publication.new';
                    $parameters[0]['wanted'] = '1';

                    foreach ($parameters as $p) {
                        $pref = new ParametersNotification();
                        $pref->setParameters($parameters_id)
                            ->setType($p['type']);

                        $pref->setWanted($p['wanted']);
                        $pref->setObjectID($groupID);

                        $manager->persist($pref);

                        $manager->flush();

                    }
        }

        foreach ($parameters as $notification) {
            if($notification["type"] == 'group.publication.new')
            {
                $notification_parameter[] = array(
                "id" => $notification["type"],
                "label" => $this->get('translator')->trans("description." . $notification["type"], array(), $this->container->getParameter("translation_name") . 'notifications'),
                "wanted" => $notification["wanted"],
                );
            }
            
        }

            $paras = $manager->getRepository("NaturaPassGroupBundle:GroupUser");
                    $qb = $paras->createQueryBuilder('g')
                        ->where('g.group = :grID')
                        ->andWhere('g.user = :grUID')
                        ->setParameter('grID', $groupID)
                        ->setParameter('grUID', $this->getUser()->getId())
                        ->getQuery();

            $mailables = $qb->getArrayResult();
            
            $b = array();
            $a['label'] = 'En temps réel';
            $a['val'] = '1';
            array_push($b,$a);
            $a['label'] = 'Par un résumé quotidien';
            $a['val'] = '2';
            array_push($b,$a);
            $a['label'] = 'Par un résumé hebdomadaire';
            $a['val'] = '3';
            array_push($b,$a);            
            $a['label'] = 'Par un résumé mensuel';
            $a['val'] = '4';
            array_push($b,$a);
            $a['label'] = 'Ne pas être averti par email';
            $a['val'] = '0';
            array_push($b,$a);
            $i=0;
            foreach ($b as $bb) {
                if($bb['val'] == $mailables[0]['mailable']){
                    $b[$i]['active'] =true;
                }
                $i++;
            }
        return $this->view(array('mobile' => $notification_parameter,'email'=>$b), Codes::HTTP_OK);
    }

}
