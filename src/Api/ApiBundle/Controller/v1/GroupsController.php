<?php

namespace Api\ApiBundle\Controller\v1;

use Api\ApiBundle\Controller\v2\Serialization\GroupSerialization;
use Api\ApiBundle\Controller\v2\Serialization\SqliteSerialization;
use Doctrine\Common\Collections\ArrayCollection;
use NaturaPass\GraphBundle\Entity\Edge;
use NaturaPass\GroupBundle\Entity\GroupMedia;
use NaturaPass\NotificationBundle\Entity\Group\GroupJoinAcceptedNotification;
use NaturaPass\NotificationBundle\Entity\Group\GroupJoinInvitedNotification;
use NaturaPass\NotificationBundle\Entity\Group\GroupJoinRefusedNotification;
use NaturaPass\NotificationBundle\Entity\Group\GroupSubscriberBannedNotification;
use NaturaPass\NotificationBundle\Entity\Group\GroupValidationAskedNotification;
use NaturaPass\NotificationBundle\Entity\Group\GroupMessageNotification;
use NaturaPass\NotificationBundle\Entity\Group\SocketOnly\GroupSubscriberAdminNotification;
use NaturaPass\NotificationBundle\Entity\Group\SocketOnly\GroupSubscriberRemoveNotification;
use NaturaPass\NotificationBundle\Entity\Group\GroupStatusAdminNotification;
use Symfony\Component\Config\Definition\Exception\Exception;
use Symfony\Component\HttpFoundation\Request;
use FOS\RestBundle\Controller\Annotations\View;
use FOS\RestBundle\Util\Codes;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use NaturaPass\GroupBundle\Entity\Group;
use NaturaPass\GroupBundle\Entity\GroupUser;
use NaturaPass\GroupBundle\Entity\GroupInvitation;
use NaturaPass\UserBundle\Entity\Invitation;
use NaturaPass\GroupBundle\Form\Type\GroupType;
use NaturaPass\GroupBundle\Form\Handler\GroupHandler;
use NaturaPass\UserBundle\Entity\User;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use NaturaPass\UserBundle\Entity\ParametersNotification;

/**
 * Description of GroupsController
 *
 * @author vincentvalot
 */
class GroupsController extends ApiRestController
{

    /**
     * FR : Retourne tous les groupes matchant le paramètre
     * EN : Returns all groups according to the parameters
     *
     * GET /groups/search?name=Blabla&select=false
     *
     * search contient le nom recherché encodé en format URL
     *
     * @param Request $request
     * @return \FOS\RestBundle\View\View
     *
     * @View(serializerGroups={"GroupLess", "UserLess"})
     */
    public function getGroupsSearchAction(Request $request)
    {
        $this->authorize();
        $search = urldecode($request->query->get('name', ''));
        $groups = $this->getDoctrine()->getManager()
            ->createQueryBuilder()
            ->select('g')
            ->from('NaturaPassGroupBundle:Group', 'g')
            ->where('g.name LIKE :name OR g.grouptag LIKE :grouptag')
            ->setParameter('name', '%' . $search . '%')
            ->setParameter('grouptag', '%' . $search . '%')
            ->orderBy('g.created', 'DESC')
            ->getQuery()
            ->getResult();
        $views = array();
        $array = array();
        foreach ($groups as $group) {
            if ($group->isSubscriber($this->getUser(), array(GroupUser::ACCESS_DEFAULT, GroupUser::ACCESS_ADMIN)) && $group->checkAllowAdd($this->getUser())) {
                $view = array(
                    'group' => $group,
                    'photo' => $this->getBaseUrl() . ($group->getPhoto() ? $group->getPhoto()->getResize() : $this->getAssetHelper()->getUrl('/img/interface/default-media.jpg'))
                );
                if ($request->query->get('select', false)) {
                    $array[] = array(
                        'id' => $group->getId(),
                        'text' => $group->getName()
                    );
                } else {
                    array_push($array, $view);
                }
            }
        }
        $views['groups'] = $array;

        return $this->view($views, Codes::HTTP_OK);
    }

    /**
     * FR : Retourne tous les groupes filtré par le parametre "name"
     * EN : Returns all groups filtered by the parameter "name"
     *
     * GET /groups/filtering?name=test
     *
     * @return array
     *
     * @param Request $request
     *
     * @View(serializerGroups={"GroupLess", "UserLess"})
     *
     */
    public function getGroupsFilteringAction(Request $request)
    {
        $this->authorize();
        $groupUser = $this->getUser()->getGroupSubscribes();
        $groups = new ArrayCollection();
        foreach ($groupUser as $gu) {
            $groups->add($gu->getGroup());
        }
        if ($name = $request->query->get('name', false)) {
            $pending = $groups->filter(
                function ($element) use ($name) {
                    return stristr($element->getName(), $name) ? true : false;
                }
            );
            $group = array();
            foreach ($pending as $gr) {
                $group[] = array(
                    'id' => $gr->getId(),
                    'text' => $gr->getName()
                );
            }

            return $this->view(
                array(
                    'groups' => $group
                )
            );
        }

        return $this->view(
            array(
                'groups' => $groups,
                'nbGroups' => $groups->count()
            ), Codes::HTTP_OK
        );
    }

    /**
     * FR : Retourne tous les groupes auquel l'utilisateur est connecté (membre, admin)
     * EN : Returns all groups which the user is attached (member, admin)
     *
     * GET /groups/owning?limit=10&offset=0&filter=test
     *
     *
     * @param Request $request
     * @return \FOS\RestBundle\View\View
     *
     * @View(serializerGroups={"GroupUserLess", "UserLess"})
     */
    public function getGroupsOwningAction(Request $request)
    {
        $this->authorize();
        $limit = $request->query->get('limit', 10);
        $offset = $request->query->get('offset', 0);
        $filter = $request->query->get('filter', false);
        if ($filter != false) {
            $filter = urldecode($filter);
        }
        $results = $this->getUser()->getAllGroups(array(GroupUser::ACCESS_DEFAULT, GroupUser::ACCESS_ADMIN));
        $groups = array();
        foreach ($results as $group) {
            $add = true;
            if ($filter != false && $filter != '') {
                if (preg_match('/' . strtolower($filter) . '/', strtolower($group->getName()))) {
                    $add = true;
                } else {
                    $add = false;
                }
            }
            if ($add) {
                $groups [] = $this->getFormatGroup($group);
            }
        }

        return $this->view(array('groups' => array_slice($groups, $offset, $limit)), Codes::HTTP_OK);
    }

    /**
     * FR : Retourne tous les groupes qui sont en attente de validation
     * EN : Returns all the groups that are awaiting approval
     *
     * GET /groups/pending?limit=10&offset=0&filter=test
     *
     * @param Request $request
     * @return \FOS\RestBundle\View\View
     *
     * @View(serializerGroups={"GroupUserLess", "UserLess"})
     */
    public function getGroupsPendingAction(Request $request)
    {
        $this->authorize();
        $limit = $request->query->get('limit', 10);
        $offset = $request->query->get('offset', 0);
        $filter = $request->query->get('filter', false);
        if ($filter != false) {
            $filter = urldecode($filter);
        }
        $results = $this->getUser()->getAllGroups(array(GroupUser::ACCESS_RESTRICTED, GroupUser::ACCESS_INVITED));
        $groups = array();
        foreach ($results as $group) {
            $add = true;
            if ($filter != false && $filter != '') {
                if (preg_match('/' . strtolower($filter) . '/', strtolower($group->getName()))) {
                    $add = true;
                } else {
                    $add = false;
                }
            }
            if ($add) {
                $groups[] = $this->getFormatGroup($group);
            }
        }

        return $this->view(array('groups' => array_slice($groups, $offset, $limit)), Codes::HTTP_OK);
    }

    /**
     * FR : Retourne tous les groupes qui sont en attente de validation
     * EN : Returns all the groups that are awaiting approval
     *
     * GET /groups/{GROUP_ID}/pending/users
     *
     * @return \FOS\RestBundle\View\View
     *
     * @param \NaturaPass\GroupBundle\Entity\Group $group
     * @return array
     *
     * @View(serializerGroups={"GroupUserLess", "UserLess"})
     *
     * @ParamConverter("group", class="NaturaPassGroupBundle:Group")
     *
     */
    public function getGroupsPendingUsersAction(Group $group)
    {
        $this->authorize();
        $suscibers = $group->getSubscribers(array(GroupUser::ACCESS_RESTRICTED, GroupUser::ACCESS_INVITED));
        $users = array();
        foreach ($suscibers as $susciber) {
            $users[] = $this->getFormatGroupSubscriber($susciber);
        }

        return $this->view(array('users' => $users), Codes::HTTP_OK);
    }

    /**
     * FR : Retourne tous les groupes de la base de données ou l'utilisateur a été invité
     *
     * GET /groups/invited
     *
     * @return array
     *
     * @View(serializerGroups={"GroupUserLess", "UserLess"})
     *
     */
    public function getGroupsInvitedAction()
    {
        $this->authorize();
        $groups = $this->getUser()->getAllGroups(array(GroupUser::ACCESS_INVITED));
        $views = array();
        $array = array();
        foreach ($groups as $group) {
            $view = array(
                '$group' => $group,
                'photo' => $this->getBaseUrl() . ($group->getPhoto() ? $group->getPhoto()->getResize() : $this->getAssetHelper()->getUrl('/img/interface/default-media.jpg'))
            );
            array_push($array, $view);
        }
        $views['$groups'] = $array;

        return $this->view($views, Codes::HTTP_OK);
    }

    /**
     * FR : Retourne tous les groupes de la base de données
     * EN : Returns all groups in the database
     *
     * GET /groups?limit=10&offset=0&filter=test
     *
     *
     * @param Request $request
     * @return \FOS\RestBundle\View\View
     *
     * @View(serializerGroups={"GroupUserLess", "UserLess"})
     *
     */
    public function getGroupsAction(Request $request)
    {
        $this->authorize();
        $limit = $request->query->get('limit', 10);
        $offset = $request->query->get('offset', 0);
        $filter = $request->query->get('filter', false);
        if ($filter != false) {
            $filter = urldecode($filter);
        }
        $manager = $this->getDoctrine()->getManager();
        $qb = $manager->createQueryBuilder()->select('g')
            ->from('NaturaPassGroupBundle:Group', 'g');
        $results = $qb->getQuery()->getResult();
        $groups = array();
        $arrayOrder = array();
        foreach ($results as $result) {
            $subscriber = $result->isSubscriber(
                $this->getUser(), array(
                    GroupUser::ACCESS_DEFAULT,
                    GroupUser::ACCESS_ADMIN,
                    GroupUser::ACCESS_INVITED,
                    GroupUser::ACCESS_RESTRICTED
                )
            );
            $add = false;
            if (($result->getAccess() == Group::ACCESS_PROTECTED && $subscriber) || in_array(
                    $result->getAccess(), array(Group::ACCESS_PUBLIC, Group::ACCESS_SEMIPROTECTED)
                )
            ) {
                $add = true;
            }
            if ($filter != false && $filter != '' && $add) {
                if (preg_match('/' . strtolower($filter) . '/', strtolower($result->getName()))) {
                    $add = true;
                } else {
                    $add = false;
                }
            }
            if ($add) {
//                $group = $this->getFormatGroup($result);
//                $group= new Group();
                $arrayOrder[$result->getId()] = $result->getSubscribers()->count();
//                $groups[] = $group;
            }
        }
        arsort($arrayOrder);
        foreach ($arrayOrder as $id_group => $count) {
            $groupFind = $manager->getRepository("NaturaPassGroupBundle:Group")->find($id_group);
            $group = $this->getFormatGroup($groupFind);
            $groups[] = $group;
        }

        return $this->view(array('groups' => array_slice($groups, $offset, $limit)), Codes::HTTP_OK);
    }

    /**
     * FR : Retourne les données d'un groupe (sans les publications)
     * EN : Returns datas of a group (without publications)
     *
     * GET /groups/{group}
     *
     *
     * @param \NaturaPass\GroupBundle\Entity\Group $group
     * @return array
     *
     * @View(serializerGroups={"GroupUserLess", "UserLess"})
     *
     * @ParamConverter("group", class="NaturaPassGroupBundle:Group")
     */
    public function getGroupAction(Group $group)
    {
        return $this->view(array('group' => $this->getFormatGroup($group)), Codes::HTTP_OK);
    }

    /**
     * FR : Retourne les utilisateurs d'un groupe
     * EN : Returns the users of a group
     *
     * GET /groups/{group_id}/subscribers?all=1
     *
     *
     * @param \NaturaPass\GroupBundle\Entity\Group $group
     * @param Request $request
     *
     * @View(serializerGroups={"GroupSubscribers", "GroupUserLess", "UserLess"})
     * @ParamConverter("group", class="NaturaPassGroupBundle:Group")
     *
     * @return \FOS\RestBundle\View\View
     *
     * @throws HttpException
     */
    public function getGroupSubscribersAction(Group $group, Request $request)
    {
        $this->authorize();
        if ($group->getAccess() == Group::ACCESS_PROTECTED && !$group->isSubscriber(
                $this->getUser(), array(GroupUser::ACCESS_INVITED, GroupUser::ACCESS_DEFAULT, GroupUser::ACCESS_ADMIN)
            )
        ) {
            throw new HttpException(Codes::HTTP_FORBIDDEN, $this->message('codes.403'));
        }
        $accesses = array(GroupUser::ACCESS_DEFAULT, GroupUser::ACCESS_ADMIN);
        if ($request->query->get('all', false)) {
            $accesses[] = GroupUser::ACCESS_INVITED;
            $accesses[] = GroupUser::ACCESS_RESTRICTED;
        }
        $array = array();
        foreach ($group->getSubscribers($accesses) as $subscriber) {
            $array[] = $this->getFormatGroupSubscriber($subscriber);
        }

        return $this->view(array('subscribers' => $array), Codes::HTTP_OK);
    }

    /**
     * FR : Retourne les membres d'un groupe ainsi que mes amis
     * EN : Returns the suscribers of a group and my friend
     *
     * GET /groups/{group_id}/subscribers/friend?all=1
     *
     * @param \NaturaPass\GroupBundle\Entity\Group $group
     * @param Request $request
     *
     * @View(serializerGroups={"GroupSubscribers", "GroupUserLess", "UserLess"})
     * @ParamConverter("group", class="NaturaPassGroupBundle:Group")
     *
     * @return \FOS\RestBundle\View\View
     *
     * @throws HttpException
     */
    public function getGroupSubscribersFriendAction(Group $group, Request $request)
    {
        $this->authorize();
        if ($group->getAccess() == Group::ACCESS_PROTECTED && !$group->isSubscriber(
                $this->getUser(), array(GroupUser::ACCESS_INVITED, GroupUser::ACCESS_DEFAULT, GroupUser::ACCESS_ADMIN)
            )
        ) {
            throw new HttpException(Codes::HTTP_FORBIDDEN, $this->message('codes.403'));
        }
        $accesses = array(GroupUser::ACCESS_DEFAULT, GroupUser::ACCESS_ADMIN);
        if ($request->query->get('all', false)) {
            $accesses[] = GroupUser::ACCESS_INVITED;
            $accesses[] = GroupUser::ACCESS_RESTRICTED;
        }
        $array = array();
        foreach ($group->getSubscribers($accesses) as $subscriber) {
            $array[] = $this->getFormatGroupSubscriberFriend($subscriber);
        }

        $friends = $this->getUser()->getFriends();
        $arrayFriend = array();
        foreach ($friends as $friend) {
            $arrayFriend[] = $this->getFormatUser($friend, true);
        }

        return $this->view(array('subscribers' => $array, 'myfriends' => $arrayFriend), Codes::HTTP_OK);
    }

    /**
     * FR : Retourne les demandes d'accès pour un groupe
     * EN : Returns the access requests for a group
     *
     * GET /groups/{group_id}/asks
     *
     *
     * @param \NaturaPass\GroupBundle\Entity\Group $group
     * @return \FOS\RestBundle\View\View
     *
     * @View(serializerGroups={"GroupSubscribers", "GroupUserLess", "UserLess"})
     *
     * @ParamConverter("group", class="NaturaPassGroupBundle:Group")
     */
    public function getGroupAsksAction(Group $group)
    {
        $this->authorize($group->getAdmins());
        $result = $group->getSubscribers(array(GroupUser::ACCESS_RESTRICTED));
        $subscribers = array();
        foreach ($result as $subscriber) {
            $subscribers[] = $this->getFormatGroupSubscriber($subscriber);
        }

        return $this->view(array('subscribers' => $subscribers), Codes::HTTP_OK);
    }

    /**
     * FR : Récupère les publications (et le groupe) avec les options passées en paramètre
     * EN : Get publications (and the group) with options passed as parameter
     *
     * GET /groups/{group_id}/publications?limit=30&offset=0
     *
     * limit:   Nombre de valeurs retournés
     * offset:  Position à partir de laquelle il faut récupérer
     *
     *
     * @param Request $request
     * @param Group $group
     * @return \FOS\RestBundle\View\View
     *
     * @View(serializerGroups={"MediaDetail", "TagLess", "SharingLess", "SharingWithouts", "GroupDetail", "GroupPhoto", "UserLess", "GroupUserLess", "GeolocationDetail"})
     * @ParamConverter("group", class="NaturaPassGroupBundle:Group")
     *
     */
    public function getGroupPublicationsAction(Request $request, Group $group)
    {
        $results = $group->getPublications()->slice(
            $request->query->get('offset', 0), $request->query->get('limit', 5)
        );
        $publications = array();
        foreach ($results as $publication) {
            $publications[] = $this->getFormatPublicationComment($publication);
        }
        $return = array(
            'publications' => $publications
        );

        return $this->view($return, Codes::HTTP_OK);
    }

    /**
     * FR : Permet de toggle l'accès administrateur d'un utilisateur du groupe (si admin, on l'enlève, si normal, on le mets admin)
     * EN : Allows you to toggle access admin for a group of users (if admin, it is removed, and if normal, we change to admin)
     *
     * PUT /groups/{group}/subscribers/{subscriber}/admin
     *
     *
     * @param \NaturaPass\GroupBundle\Entity\Group $group
     * @param \NaturaPass\UserBundle\Entity\User $subscriber
     *
     * @ParamConverter("group", class="NaturaPassGroupBundle:Group")
     * @ParamConverter("subscriber", class="NaturaPassUserBundle:User")
     *
     * @return \FOS\RestBundle\View\View
     * @throws HttpException
     */
    public function putGroupSubscriberAdminAction(Group $group, User $subscriber)
    {
        $this->authorize($group->getAdmins());

        if ($subscriber->getId() == $group->getOwner()->getId()) {
            throw new HttpException(Codes::HTTP_FORBIDDEN, $this->message('errors.group.subscriber.unadmin_owner'));
        }

        $manager = $this->getDoctrine()->getManager();
        $groupUser = $manager->getRepository('NaturaPassGroupBundle:GroupUser')->findOneBy(
            array(
                'user' => $subscriber,
                'group' => $group
            )
        );
        if ($groupUser instanceof GroupUser) {
            $groupUser->setAccess(
                $groupUser->getAccess() === GroupUser::ACCESS_ADMIN ? GroupUser::ACCESS_DEFAULT : GroupUser::ACCESS_ADMIN
            );
            $manager->persist($groupUser);
            $manager->flush();

            $this->getNotificationService()->queue(
                new GroupSubscriberAdminNotification($group, $groupUser), array()
            );

            $this->getNotificationService()->queue(
                new GroupStatusAdminNotification($group), $subscriber
            );

            return $this->view(
                array(
                    'isAdmin' => $groupUser->getAccess() === GroupUser::ACCESS_ADMIN
                ), Codes::HTTP_OK
            );
        }
        throw new HttpException(Codes::HTTP_BAD_REQUEST, $this->message('errors.parameters'));
    }

    /**
     * FR : Modifie un groupe dans la base de données
     * EN : Update a group in database
     *
     * PUT /groups/{group}
     *
     * Content-Type: form-data
     *      group[name] = "Battue dimanche"
     *      group[allow_add] = [0 => ADMIN, 1 => ALL_MEMBERS]
     *      group[allow_show] = [0 => ADMIN, 1 => ALL_MEMBERS]
     *      group[allow_show_chat] = [0 => ADMIN, 1 => ALL_MEMBERS]
     *      group[allow_add_chat] = [0 => ADMIN, 1 => ALL_MEMBERS]
     *      group[description] = "Une bonne battue entre amis"
     *      group[access] = [0 => Privé, 1 => Semi-privé, 2 => Public]
     *      group[photo][file] = Données FILES
     *
     *
     * @param Group $group
     * @param Request $request
     * @return \FOS\RestBundle\View\View
     *
     * @View(serializerGroups={"GroupLess", "UserLess"})
     *
     * @ParamConverter("group", class="NaturaPassGroupBundle:Group")
     */
    public function putGroupAction(Group $group, Request $request)
    {
        $this->authorize($group->getAdmins());
        $update = $group->getUpdated()->getTimestamp();
        $form = $this->createForm(new GroupType($this->getUser(), $this->container), $group, array('csrf_protection' => false, 'method' => 'PUT'));
        $handler = new GroupHandler($form, $request, $this->getDoctrine()->getManager());
        if ($group = $handler->process()) {
            return $this->view(array(
                'group' => $this->getFormatGroup($group, true),
                "sqlite" => SqliteSerialization::serializeSqliteInserOrReplace("tb_group", array(GroupSerialization::serializeGroupSqliteRefresh(array(), null, $group, $this->getUser(), true)))
            ), Codes::HTTP_OK);
        }

        return $this->view($form->getErrors(true), Codes::HTTP_BAD_REQUEST);
    }

    /**
     * FR : Mets à jour le paramètre permettant de savoir si l'utilisateur souhaite recevoir par email des notifications pour ce groupe
     * EN : Upgrade the parameter to receive email notifications for this group
     *
     * PUT /groups/{group}/subscribers/{mailable}/mailable
     *
     *
     * @param \NaturaPass\GroupBundle\Entity\Group $group
     * @param integer $mailable
     * @return \FOS\RestBundle\View\View
     * @throws HttpException
     *
     * @ParamConverter("group", class="NaturaPassGroupBundle:Group")
     * @View()
     */
    public function putGroupSubscriberMailableAction(Group $group, $mailable)
    {
        $this->authorize();
        if ($subscriber = $group->isSubscriber($this->getUser())) {
            $subscriber->setMailable($mailable);
            $this->getDoctrine()->getManager()->persist($subscriber);
            $this->getDoctrine()->getManager()->flush();

            return $this->view($this->success(), Codes::HTTP_OK);
        }
        throw new HttpException(Codes::HTTP_BAD_REQUEST, $this->message('errors.group.subscriber.unregistered'));
    }

    /**
     * FR : Confirme le rattachement d'un utilisateur à un groupe, par le créateur du groupe ou un administrateur
     * EN : Confirms the connection of a user in a group
     *
     * PUT /groups/{group}/users/{user}/join
     *
     *
     * @param \NaturaPass\GroupBundle\Entity\Group $group
     * @param \NaturaPass\UserBundle\Entity\User $user
     * @return \FOS\RestBundle\View\View
     * @throws HttpException
     *
     * @ParamConverter("group", class="NaturaPassGroupBundle:Group")
     * @ParamConverter("user", class="NaturaPassUserBundle:User")
     * @View(serializerGroups={"GroupUserLess", "GroupUserDetail", "UserDetail", "UserLess"})
     */
    public function putGroupUserJoinAction(Group $group, User $user)
    {        
        $this->authorize(array_merge(array($user, $this->getUser()), $group->getAdmins()->toArray()));

        $manager = $this->getDoctrine()->getManager();
        $repository = $manager->getRepository('NaturaPassGroupBundle:GroupUser');
        $groupUser = $repository->findOneBy(
            array(
                'user' => $user,
                'group' => $group
            )
        );
        if ($groupUser) {

            if ($groupUser->getAccess() === GroupUser::ACCESS_INVITED && $this->getUser() == $user) {
                $groupUser->setAccess(GroupUser::ACCESS_DEFAULT);

                $manager->persist($groupUser);
                $manager->flush();

                return $this->view(array_merge(
                    $this->success(),
                    array(
                        "sqlite" => SqliteSerialization::serializeSqliteInserOrReplace("tb_group", array(GroupSerialization::serializeGroupSqliteRefresh(array(), null, $group, $this->getUser(), true)))
                    )), Codes::HTTP_OK);
            } else {

                if ($groupUser->getAccess() === GroupUser::ACCESS_RESTRICTED && $group->isAdmin($this->getUser())) {
                    $groupUser->setAccess(GroupUser::ACCESS_DEFAULT);
                    $manager->persist($groupUser);
                    $manager->flush();

//                    $this->delay(function () use ($group, $user) {
//                        $this->getGraphService()->generateEdge(
//                            $user, $group->getSubscribers(array(GroupUser::ACCESS_ADMIN, GroupUser::ACCESS_DEFAULT), true), Edge::GROUP_SUBSCRIBER, $group->getId()
//                        );
//                    });

                    $this->getNotificationService()->queue(
                        new GroupJoinAcceptedNotification($group), $user
                    );

                    $this->getEmailService()->generate(
                        'group.access-validated', array('%groupname%' => $group->getName(), '%fullname%' => $this->getUser()->getFullName()), array($user), 'NaturaPassEmailBundle:Group:access-validated.html.twig', array('group' => $group, 'fullname' => $this->getUser()->getFullName())
                    );

                    return $this->view(
                        array('subscriber' => $this->getFormatGroupSubscriber($groupUser),
                            "sqlite" => SqliteSerialization::serializeSqliteInserOrReplace("tb_group", array(GroupSerialization::serializeGroupSqliteRefresh(array(), null, $group, $this->getUser(), true)))
                            ), Codes::HTTP_OK
                    );
                }
            }
            throw new HttpException(Codes::HTTP_BAD_REQUEST, $this->message('errors.group.subscriber.already'));
        }
        throw new HttpException(Codes::HTTP_BAD_REQUEST, $this->message('errors.group.subscriber.unregistered'));
    }

    /**
     * FR : Un utilisateur invite un ami à souscrire à un groupe
     * EN : A user invites a friend to subscribe to a group
     *
     * POST /groups/{group}/invites/{receiver}/users
     *
     * @deprecated Utiliser la fonction postGroupJoinAction
     *
     * @ParamConverter("group", class="NaturaPassGroupBundle:Group")
     * @ParamConverter("receiver", class="NaturaPassUserBundle:User")
     */
    public function postGroupInviteUserAction(Group $group, User $receiver)
    {
        if (in_array($group->getAccess(), array(Group::ACCESS_SEMIPROTECTED, Group::ACCESS_PROTECTED))) {
            $this->authorize($group->getAdmins()->toArray());
        } else {
            $this->authorize($receiver);
        }
        // On vérifie qu'il n'existe pas déjà
        if (!$group->isSubscriber(
            $receiver, array(GroupUser::ACCESS_DEFAULT, GroupUser::ACCESS_ADMIN, GroupUser::ACCESS_INVITED)
        )
        ) {
            $manager = $this->getDoctrine()->getManager();

            $this->getNotificationService()->queue(
                new GroupJoinInvitedNotification($group), $receiver
            );

            $this->getEmailService()->generate(
                'group.invite', array('%groupname%' => $group->getName(), '%fullname%' => $this->getUser()->getFullName()), array($receiver), 'NaturaPassEmailBundle:Group:invite-email-without-message.html.twig', array('group' => $group, 'fullname' => $this->getUser()->getFullName())
            );

//            $this->delay(function () use ($group, $receiver) {
//                $this->getGraphService()->generateEdge(
//                    $receiver, $group->getSubscribers(array(GroupUser::ACCESS_ADMIN, GroupUser::ACCESS_DEFAULT, GroupUser::ACCESS_INVITED), true), Edge::GROUP_SUBSCRIBER, $group->getId()
//                );
//            });
            $groupUser = new GroupUser();
            $groupUser->setUser($receiver)
                ->setGroup($group)
                ->setAccess(GroupUser::ACCESS_INVITED);
            $manager->persist($groupUser);
            $manager->flush();

            return $this->view($this->success(), Codes::HTTP_OK);
        }
        throw new HttpException(Codes::HTTP_BAD_REQUEST, $this->message("errors.group.subscriber.already"));
    }

    /**
     * Modifie le visuel d'un group
     *
     * POST /groups/{group}/photos
     *
     * Content-Type: form-data
     *      group[photo] = Données FILES
     *
     * @param Group $group
     * @param Request $request
     *
     * @return \FOS\RestBundle\View\View
     * @throws HttpException
     *
     * @ParamConverter("group", class="NaturaPassGroupBundle:Group")
     */
    public function postGroupPhotoAction(Request $request, Group $group)
    {
        $this->authorize($group->getAdmins()->toArray());

        if ($file = $request->files->get('group[photo]', false, true)) {
            $media = new GroupMedia();
            $media->setFile($file);

            $manager = $this->getDoctrine()->getManager();

            $manager->persist($media);
            $manager->flush();

            $group->setPhoto($media);
            $manager->persist($group);
            $manager->flush();

            return $this->view(array('group' => $this->getFormatGroup($group)), Codes::HTTP_OK);
        }

        throw new HttpException(Codes::HTTP_BAD_REQUEST, $this->message('errors.parameters'));
    }

    /**
     * FR : Un utilisateur invite un groupe à souscrire à un autre groupe
     * EN : A user invites a group to subscribe to another group
     *
     * POST /groups/{group}/invites/{group}/groups
     *
     * @param Group $group
     * @param Group $groupFriends
     *
     * @ParamConverter("group", class="NaturaPassGroupBundle:Group")
     * @ParamConverter("groupFriends", class="NaturaPassGroupBundle:Group")
     *
     * @return \FOS\RestBundle\View\View
     */
    public function postGroupInviteGroupAction(Group $group, Group $groupFriends)
    {
        $this->authorize($group->getAdmins()->toArray());
        $manager = $this->getDoctrine()->getManager();
        $receivers = array();
        foreach ($groupFriends->getSubscribers() as $subscriber) {
            if (!$group->isSubscriber(
                $subscriber->getUser(), array(
                    GroupUser::ACCESS_DEFAULT,
                    GroupUser::ACCESS_ADMIN,
                    GroupUser::ACCESS_INVITED,
                    GroupUser::ACCESS_RESTRICTED
                )
            )
            ) {
                $receivers[] = $subscriber->getUser();
                $groupUser = new GroupUser();
                $groupUser->setUser($subscriber->getUser())
                    ->setGroup($group)
                    ->setAccess(GroupUser::ACCESS_INVITED);
                $manager->persist($groupUser);
            }
        }

        $this->getNotificationService()->queue(
            new GroupJoinInvitedNotification($group), $receivers
        );

        $manager->flush();

        return $this->view($this->success(), Codes::HTTP_OK);
    }

    /**
     * Do the attachment to a group
     *
     * @param Group $group
     * @param User $user
     *
     * @throws HttpException
     * @return GroupUser|void
     */
    protected function addSubscriberToGroup(Group $group, User $user)
    {
        $groupUser = new GroupUser;
        $groupUser->setUser($user)
            ->setGroup($group);

        $isUserAdmin = $group->isAdmin($this->getUser());

        if ($group->getAccess() === Group::ACCESS_PUBLIC) {
            if ($isUserAdmin) {
                $groupUser->setAccess(GroupUser::ACCESS_INVITED);

//                $this->delay(function () use ($user, $group) {
//                    $this->getGraphService()->generateEdge($user, $group->getSubscribers(array(GroupUser::ACCESS_INVITED, GroupUser::ACCESS_ADMIN, GroupUser::ACCESS_DEFAULT), true), Edge::GROUP_SUBSCRIBER, $group->getId());
//                });

                $this->getNotificationService()->queue(
                    new GroupJoinInvitedNotification($group), $user
                );

                $this->getEmailService()->generate(
                    'group.invite', array('%groupname%' => $group->getName(), '%fullname%' => $this->getUser()->getFullName()), array($user), 'NaturaPassEmailBundle:Group:invite-email-without-message.html.twig', array('group' => $group, 'fullname' => $this->getUser()->getFullName())
                );
            } else {
                $groupUser->setAccess(GroupUser::ACCESS_DEFAULT);
//                $this->delay(function () use ($group, $user) {
//                    $this->getGraphService()->generateEdge(
//                        $user, $group->getSubscribers(array(GroupUser::ACCESS_ADMIN, GroupUser::ACCESS_DEFAULT), true), Edge::GROUP_SUBSCRIBER, $group->getId()
//                    );
//                });
            }
        } else if ($group->getAccess() === Group::ACCESS_SEMIPROTECTED) {
            if ($isUserAdmin) {
                $groupUser->setAccess(GroupUser::ACCESS_INVITED);

//                $this->delay(function () use ($user, $group) {
//                    $this->getGraphService()->generateEdge($user, $group->getSubscribers(array(GroupUser::ACCESS_INVITED, GroupUser::ACCESS_ADMIN, GroupUser::ACCESS_DEFAULT), true), Edge::GROUP_SUBSCRIBER, $group->getId());
//                });

                $this->getNotificationService()->queue(
                    new GroupJoinInvitedNotification($group), $user
                );

                $this->getEmailService()->generate(
                    'group.invite', array('%groupname%' => $group->getName(), '%fullname%' => $this->getUser()->getFullName()), array($user), 'NaturaPassEmailBundle:Group:invite-email-without-message.html.twig', array('group' => $group, 'fullname' => $this->getUser()->getFullName())
                );
            } else {
                $groupUser->setAccess(GroupUser::ACCESS_RESTRICTED);

                $this->getNotificationService()->queue(
                    new GroupValidationAskedNotification($group), $group->getAdmins()->toArray()
                );

                $adminEmail = array();
                foreach ($group->getAdmins() as $admins) {
                    $adminEmail[] = $admins->getEmail();
                }

                $this->getEmailService()->generate(
                    'group.valid-invite', array('%groupname%' => $group->getName(), '%fullname%' => $this->getUser()->getFullName()), $adminEmail, 'NaturaPassEmailBundle:Group:valid-invite.html.twig', array('group' => $group, 'fullname' => $this->getUser()->getFullName())
                );
            }
        } else if ($group->getAccess() === Group::ACCESS_PROTECTED) {
            if ($isUserAdmin) {
                $groupUser->setAccess(GroupUser::ACCESS_INVITED);

//                $this->delay(function () use ($user, $group) {
//                    $this->getGraphService()->generateEdge($user, $group->getSubscribers(array(GroupUser::ACCESS_INVITED, GroupUser::ACCESS_ADMIN, GroupUser::ACCESS_DEFAULT), true), Edge::GROUP_SUBSCRIBER, $group->getId());
//                });

                $this->getNotificationService()->queue(
                    new GroupJoinInvitedNotification($group), $user
                );

                $this->getEmailService()->generate(
                    'group.invite',
                    array(
                        '%groupname%' => $group->getName(),
                        '%fullname%' => $this->getUser()->getFullName()
                    ),
                    array($user), 'NaturaPassEmailBundle:Group:invite-email-without-message.html.twig', array('group' => $group, 'fullname' => $this->getUser()->getFullName())
                );
            } else {
                throw new HttpException(Codes::HTTP_BAD_REQUEST, $this->message('errors.group.private'));
            }
        } else {
            throw new HttpException(Codes::HTTP_BAD_REQUEST, $this->message('errors.unknown'));
        }

        return $groupUser;
    }

    /**
     * FR : Rattache un où plusieurs utilisateurs à un groupe
     * EN : Connects one or more users to a group
     *
     * POST /api/groups/{group}/joins/multiples
     *
     * JSON DATA:
     * {"groupe": {"subscribers": [1,3,10]}}
     *
     * Where subscribers contains the identifier of the invited users
     *
     * @param Request $request
     * @param \NaturaPass\GroupBundle\Entity\Group $group
     *
     * @return \FOS\RestBundle\View\View
     * @throws HttpException
     *
     * @ParamConverter("group", class="NaturaPassGroupBundle:Group")
     *
     * @View(serializerGroups={"GroupUserLess", "GroupUserDetail", "UserDetail", "UserLess"})
     */
    public function postGroupJoinMultipleAction(Request $request, Group $group)
    {
        $this->authorize();

        $subscribers = $request->request->get('group[subscribers]', null, true);

        $response = array();

        if (is_array($subscribers) && count($subscribers) > 0) {
            foreach ($subscribers as $user_id) {
                try {
                    $manager = $this->getDoctrine()->getManager();

                    $user = $manager->getRepository('NaturaPassUserBundle:User')
                        ->find($user_id);

                    if ($user instanceof User) {
                        $groupUser = $manager->getRepository('NaturaPassGroupBundle:GroupUser')
                            ->findOneBy(array('user' => $user, 'group' => $group));

                        if (!$groupUser) {
                            $groupUser = $this->addSubscriberToGroup($group, $user);

                            $manager->persist($groupUser);
                            $manager->flush();

                            $response[] = array(
                                'added' => true,
                                'user_id' => $user_id,
                                'access' => $groupUser->getAccess()
                            );
                        } else {
                            $response[] = array(
                                'added' => false,
                                'user_id' => $user_id,
                                'error' => $this->message('errors.group.subscriber.already')
                            );
                        }
                    } else {
                        $response[] = array(
                            'added' => false,
                            'user_id' => $user_id,
                            'error' => $this->message('errors.user.nonexistent')
                        );
                    }
                } catch (HttpException $exception) {
                    $response[] = array(
                        'added' => false,
                        'user_id' => $user_id,
                        'error' => $exception->getMessage()
                    );
                }
            }
        } else {
            throw new BadRequestHttpException($this->message('errors.parameters'));
        }

        return $this->view(array('subscribers' => $response), Codes::HTTP_OK);
    }

    /**
     * FR : Rattache un utilisateur à un groupe
     * EN : Connects a user to a group
     *
     * POST /groups/{user}/joins/{group}
     *
     *
     * @param \NaturaPass\UserBundle\Entity\User $user
     * @param \NaturaPass\GroupBundle\Entity\Group $group
     *
     * @return \FOS\RestBundle\View\View
     * @throws HttpException
     *
     * @ParamConverter("user", class="NaturaPassUserBundle:User")
     * @ParamConverter("group", class="NaturaPassGroupBundle:Group")
     *
     * @View(serializerGroups={"GroupUserLess", "GroupUserDetail", "UserDetail", "UserLess"})
     */
    public function postGroupJoinAction(User $user, Group $group)
    {
        $this->authorize();

        $manager = $this->getDoctrine()->getManager();
        $repository = $manager->getRepository('NaturaPassGroupBundle:GroupUser');
        $groupUser = $repository->findOneBy(array('user' => $user, 'group' => $group));

        if (!$groupUser) {

            $groupUser = $this->addSubscriberToGroup($group, $user);

            $manager->persist($groupUser);
            $manager->flush();

            if ($user == $this->getUser() && $group->getAccess() === Group::ACCESS_PUBLIC) {
                return $this->view(array(
                    'access' => $groupUser->getAccess(),
                    "sqlite" => SqliteSerialization::serializeSqliteInserOrReplace("tb_group", array(GroupSerialization::serializeGroupSqliteRefresh(array(), null, $group, $this->getUser(), true)))
                ), Codes::HTTP_OK);
            } else {
                return $this->view(array('access' => $groupUser->getAccess()), Codes::HTTP_OK);
            }
        }

        throw new HttpException(Codes::HTTP_BAD_REQUEST, $this->message('errors.group.subscriber.already'));
    }

    /**
     * FR : Ajoute un groupe à la base de données
     * EN : Add a group to the database
     *
     * POST /groups
     *
     * Content-Type: form-data
     *      group[name] = "Battue dimanche"
     *      group[allow_add] = [0 => ADMIN, 1 => ALL_MEMBERS]
     *      group[allow_show] = [0 => ADMIN, 1 => ALL_MEMBERS]
     *      group[allow_show_chat] = [0 => ADMIN, 1 => ALL_MEMBERS]
     *      group[allow_add_chat] = [0 => ADMIN, 1 => ALL_MEMBERS]
     *      group[description] = "Une bonne battue entre amis"
     *      group[access] = [0 => Privé, 1 => Semi-privé, 2 => Public]
     *      group[photo][file] = Données de photo
     *
     *
     * @param Request $request
     * @return \FOS\RestBundle\View\View
     *
     * @View()
     */
    public function postGroupAction(Request $request)
    {
        $this->authorize();
        $form = $this->createForm(new GroupType($this->getUser(), $this->container), new Group(), array('csrf_protection' => false));
        $handler = new GroupHandler($form, $request, $this->getDoctrine()->getManager());
        if ($group = $handler->process()) {
            return $this->view(
                array(
                    'group_id' => $group->getId(),
                    "sqlite" => SqliteSerialization::serializeSqliteInserOrReplace("tb_group", array(GroupSerialization::serializeGroupSqliteRefresh(array(), null, $group, $this->getUser(), true)))
                ), Codes::HTTP_CREATED);
        }

        return $this->view($form->getErrors(true), Codes::HTTP_BAD_REQUEST);
    }

    /**
     * FR : Envoi un email d'invitation pour le groupe
     * Vérifie auparavant que les emails spécifiés n'existent pas déjà; le cas échéant, on envoie une notification
     * EN : Send an email invitation to the group
     * Verifies that emails do not exist; if an email exist, a notification is sent
     *
     * POST /groups/{group}/invites/mails
     *
     *
     * JSON Lié:
     *
     * {
     *      "email": {
     *          "to": "v.valot@e-conception.fr;n.mendez@e-conception.fr",
     *          "subject": "Invitation",
     *          "body": "Rejoignez"
     *      }
     * }
     *
     * @param Request $request
     * @param \NaturaPass\GroupBundle\Entity\Group $group
     *
     * @ParamConverter("group", class="NaturaPassGroupBundle:Group")
     *
     * @return \FOS\RestBundle\View\View
     */
    public function postGroupInviteMailAction(Request $request, Group $group)
    {
        $this->authorize($group->getAdmins()->toArray());
        $manager = $this->getDoctrine()->getManager();
        $repo = $manager->getRepository('NaturaPassUserBundle:User');
        $to = explode(';', $request->request->get('email[to]', '', true));
        $receivers = array();
        $addresses = array();

        foreach ($to as $email) {

            $user = $repo->findOneBy(
                array(
                    'email' => $email
                )
            );
            if ($user && !$group->isSubscriber(
                    $user, array(
                        GroupUser::ACCESS_ADMIN,
                        GroupUser::ACCESS_DEFAULT,
                        GroupUser::ACCESS_INVITED,
                        GroupUser::ACCESS_RESTRICTED
                    )
                )
            ) {
                $groupUser = new GroupUser();
                $groupUser->setAccess(GroupUser::ACCESS_INVITED)
                    ->setUser($user)
                    ->setGroup($group);

                $receivers[] = $user;

                $manager->persist($groupUser);
                $manager->flush();
            } else {

                $groupInvitation = $manager->getRepository('NaturaPassGroupBundle:GroupInvitation')->findOneBy(
                    array(
                        'email' => $email,
                        'group' => $group
                    )
                );

                if (!$groupInvitation) {

                    $groupInvitation = new GroupInvitation();
                    $groupInvitation->setGroup($group)
                        ->setEmail($email)
                        ->setUser($this->getUser())
                        ->setState(GroupInvitation::INVITATION_SENT);

                    $manager->persist($groupInvitation);

                    $repository = $manager->getRepository('NaturaPassUserBundle:Invitation');
                    $invitation = $repository->findOneBy(
                        array(
                            'email' => $email,
                            'user' => $this->getUser()
                        )
                    );

                    if (!$invitation) {
                        $invitation = new Invitation();
                    }

                    $invitation->setEmail($email)
                        ->setUser($this->getUser());

                    $manager->persist($invitation);
                    $manager->flush();

                    $addresses[] = $email;
                }
            }
        }

        $renderVars = array(
            'group' => $group,
            'fullname' => $this->getUser()->getFullName()
        );

        if ($message = $request->get('email[body]', false, true)) {
            $render = 'NaturaPassEmailBundle:Group:invite-email.html.twig';
            $renderVars['message'] = $message;
        } else {
            $render = 'NaturaPassEmailBundle:Group:invite-email-without-message.html.twig';
        }

        if (count($receivers)) {

            $this->getNotificationService()->queue(
                new GroupJoinInvitedNotification($group), $receivers
            );

            $this->getEmailService()->generate(
                'group.invite', array('%groupname%' => $group->getName(), '%fullname%' => $this->getUser()->getFullName()), $receivers, $render, $renderVars
            );
        }

        if (!empty($addresses)) {

            $this->getEmailService()->generate(
                'group.invite', array('%groupname%' => $group->getName(), '%fullname%' => $this->getUser()->getFullName()), $addresses, $render, $renderVars
            );
        }

        return $this->view($this->success(), Codes::HTTP_OK);
    }

    /**
     * FR : Supprime le rattachement d'un utilisateur à un groupe
     * EN : Removes the connection of a user to a group
     *
     * DELETE /groups/{user_id}/joins/{group_id}
     *
     *
     * @param \NaturaPass\UserBundle\Entity\User $user
     * @param \NaturaPass\GroupBundle\Entity\Group $group
     *
     * @ParamConverter("user", class="NaturaPassUserBundle:User")
     * @ParamConverter("group", class="NaturaPassGroupBundle:Group")
     *
     * @return \FOS\RestBundle\View\View
     * @throws HttpException
     */
    public function deleteGroupJoinAction(User $user, Group $group)
    {
        $this->authorize(array_merge(array($user), $group->getAdmins()->toArray()));

        $manager = $this->getDoctrine()->getManager();
        $repository = $manager->getRepository('NaturaPassGroupBundle:GroupUser');
        $groupUser = $repository->findOneBy(
            array(
                'user' => $user,
                'group' => $group
            )
        );

        if ($groupUser) {
            if ($user->getId() !== $group->getOwner()->getId()) {

                $manager->remove($groupUser);
                $manager->flush();

                if ($user->getId() != $this->getUser()->getId()) {
                    if ($groupUser->getAccess() == GroupUser::ACCESS_RESTRICTED) {
                        $this->getNotificationService()->queue(
                            new GroupJoinRefusedNotification($group), $user
                        );
                    } else {
                        $this->getNotificationService()->queue(
                            new GroupSubscriberBannedNotification($group), $user
                        );
                    }
                    $this->getNotificationService()->queue(
                        new GroupSubscriberRemoveNotification($group, $groupUser), array()
                    );
                }

                return $this->view($this->success(), Codes::HTTP_OK);
            }

            throw new HttpException(Codes::HTTP_BAD_REQUEST, $this->message('errors.group.subscriber.unregister_owner'));
        }

        throw new HttpException(Codes::HTTP_BAD_REQUEST, $this->message("errors.group.subscriber.unregistered"));
    }

    /**
     * FR : Supprime le rattachement d'un utilisateur à un groupe
     * EN : Removes the connection of a user to a group
     *
     * DELETE /groups/{user_id}/joinnews/{group_id}
     *
     *
     * @param \NaturaPass\UserBundle\Entity\User $user
     * @param \NaturaPass\GroupBundle\Entity\Group $group
     *
     * @ParamConverter("user", class="NaturaPassUserBundle:User")
     * @ParamConverter("group", class="NaturaPassGroupBundle:Group")
     *
     * @return \FOS\RestBundle\View\View
     * @throws HttpException
     */
    public function deleteGroupJoinnewsAction(User $user, Group $group)
    {
        $this->authorize(array_merge(array($user), $group->getAdmins()->toArray()));

        $manager = $this->getDoctrine()->getManager();
        $repository = $manager->getRepository('NaturaPassGroupBundle:GroupUser');
        $groupUser = $repository->findOneBy(
            array(
                'user' => $user,
                'group' => $group
            )
        );

        
        if ($groupUser) {
            if ($user->getId() !== $group->getOwner()->getId()) {
                $manager->remove($groupUser);
                $manager->flush();

                // remove parameters when user leave group
                // author: vietlh            
                $repository2 = $manager->getRepository('NaturaPassUserBundle:ParametersNotification');
                $paras = $repository2->findBy(
                    array(
                        'parameters' => $user->getParameters(),
                        'objectID' => $group->getId(),
                    )
                );
                foreach ($paras as $p) {
                        $manager->remove($p);
                        $manager->flush();

                    }
                // end
                                   
                if ($user->getId() != $this->getUser()->getId()) {
                    if ($groupUser->getAccess() == GroupUser::ACCESS_RESTRICTED) {
                        $this->getNotificationService()->queue(
                            new GroupJoinRefusedNotification($group), $user
                        );
                    } else {
                        $this->getNotificationService()->queue(
                            new GroupSubscriberBannedNotification($group), $user
                        );
                    }
                    $this->getNotificationService()->queue(
                        new GroupSubscriberRemoveNotification($group, $groupUser), array()
                    );
                }

                return $this->view($this->success(), Codes::HTTP_OK);
            }

            throw new HttpException(Codes::HTTP_BAD_REQUEST, $this->message('errors.group.subscriber.unregister_owner'));
        }

        throw new HttpException(Codes::HTTP_BAD_REQUEST, $this->message("errors.group.subscriber.unregistered"));
    }

    /**
     * Supprime un groupe de la base de données
     * Remove a group from the database
     *
     * DELETE /groups/{group}
     *
     *
     * @param Group $group
     * @return \FOS\RestBundle\View\View
     *
     * @ParamConverter("group", class="NaturaPassGroupBundle:Group")
     */
    public function deleteGroupAction(Group $group)
    {
        $this->authorize($group->getAdmins()->toArray());
        $manager = $this->getDoctrine()->getManager();
        $id = $group->getId();

        $manager->remove($group);
        $manager->flush();

        return $this->view(array_merge($this->success(), array("sqlite" => array("groups" => array("DELETE FROM `tb_group` WHERE `c_id` IN (" . $id . ") AND `c_user_id` = '" . $this->getUser()->getId() . "';")))), Codes::HTTP_OK);
    }

    //viet lh
    /**
     * FR : Confirme le rattachement d'un utilisateur à un groupe, par le créateur du groupe ou un administrateur
     * EN : Confirms the connection of a user in a group
     *
     * PUT /groups/{group}/users/{user}/joinnew
     *
     *
     * @param \NaturaPass\GroupBundle\Entity\Group $group
     * @param \NaturaPass\UserBundle\Entity\User $user
     * @return \FOS\RestBundle\View\View
     * @throws HttpException
     *
     * @ParamConverter("group", class="NaturaPassGroupBundle:Group")
     * @ParamConverter("user", class="NaturaPassUserBundle:User")
     * @View(serializerGroups={"GroupUserLess", "GroupUserDetail", "UserDetail", "UserLess"})
     */
    public function putGroupUserJoinnewAction(Group $group, User $user)
    {        
        $this->authorize(array_merge(array($user, $this->getUser()), $group->getAdmins()->toArray()));

        $manager = $this->getDoctrine()->getManager();
        $repository = $manager->getRepository('NaturaPassGroupBundle:GroupUser');
        $groupUser = $repository->findOneBy(
            array(
                'user' => $user,
                'group' => $group
            )
        );
        if ($groupUser) {

            if ($groupUser->getAccess() === GroupUser::ACCESS_INVITED && $this->getUser() == $user) {
                $groupUser->setAccess(GroupUser::ACCESS_DEFAULT);

                $manager->persist($groupUser);
                $manager->flush();
                //vietlh add default notifications to user invited
                    $manager = $this->getDoctrine()->getManager();
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

                return $this->view(array_merge(
                    $this->success(),
                    array(
                        "sqlite" => SqliteSerialization::serializeSqliteInserOrReplace("tb_group", array(GroupSerialization::serializeGroupSqliteRefresh(array(), null, $group, $this->getUser(), true)))
                    )), Codes::HTTP_OK);
            } else {

                if ($groupUser->getAccess() === GroupUser::ACCESS_RESTRICTED && $group->isAdmin($this->getUser())) {
                    $groupUser->setAccess(GroupUser::ACCESS_DEFAULT);
                    $manager->persist($groupUser);
                    $manager->flush();

                    $this->getNotificationService()->queue(
                        new GroupJoinAcceptedNotification($group), $user
                    );

                    $this->getEmailService()->generate(
                        'group.access-validated', array('%groupname%' => $group->getName(), '%fullname%' => $this->getUser()->getFullName()), array($user), 'NaturaPassEmailBundle:Group:access-validated.html.twig', array('group' => $group, 'fullname' => $this->getUser()->getFullName())
                    );

                    return $this->view(
                        array('subscriber' => $this->getFormatGroupSubscriber($groupUser),
                            "sqlite" => SqliteSerialization::serializeSqliteInserOrReplace("tb_group", array(GroupSerialization::serializeGroupSqliteRefresh(array(), null, $group, $this->getUser(), true)))
                            ), Codes::HTTP_OK
                    );
                }
            }
            throw new HttpException(Codes::HTTP_BAD_REQUEST, $this->message('errors.group.subscriber.already'));
        }
        throw new HttpException(Codes::HTTP_BAD_REQUEST, $this->message('errors.group.subscriber.unregistered'));
    }

}
