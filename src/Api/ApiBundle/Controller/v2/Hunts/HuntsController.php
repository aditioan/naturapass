<?php

/**
 * Created by PhpStorm.
 * User: vincentvalot
 * Date: 07/08/15
 * Time: 11:16
 */

namespace Api\ApiBundle\Controller\v2\Hunts;

use Api\ApiBundle\Controller\v2\ApiRestController;
use Api\ApiBundle\Controller\v2\Serialization\HuntSerialization;
use Api\ApiBundle\Controller\v2\Serialization\PublicationSerialization;
use Api\ApiBundle\Controller\v2\Serialization\SqliteSerialization;
use Doctrine\Common\Collections\ArrayCollection;
use FOS\RestBundle\Util\Codes;
use NaturaPass\LoungeBundle\Entity\Lounge;
use NaturaPass\LoungeBundle\Entity\LoungeNotMember;
use NaturaPass\LoungeBundle\Entity\LoungeUser;
use NaturaPass\LoungeBundle\Form\Handler\LoungeHandler;
use NaturaPass\LoungeBundle\Form\Type\LoungeType;
use NaturaPass\NotificationBundle\Entity\Lounge\SocketOnly\LoungeChangeAllowNotification;
use NaturaPass\UserBundle\Entity\UserDeviceSended;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Symfony\Component\HttpFoundation\Request;
use NaturaPass\NotificationBundle\Entity\Lounge\SocketOnly\LoungeNotUserParticipationNotification;
use NaturaPass\NotificationBundle\Entity\Lounge\LoungeJoinInvitedNotification;
use Symfony\Component\Validator\Constraints\DateTime;
use NaturaPass\UserBundle\Entity\ParametersNotification;

class HuntsController extends ApiRestController
{

    /**
     * Return the hunt information
     *
     * GET /v2/hunts/{lounge}
     *
     * @param Lounge $hunt
     * @return array
     *
     * @ParamConverter("hunt", class="NaturaPassLoungeBundle:Lounge")
     */
    public function getHuntAction(Lounge $hunt)
    {
        return $this->view(
            array('hunt' => HuntSerialization::serializeHunt($hunt, $this->getUser())), Codes::HTTP_OK
        );
    }

    /**
     * Return the hunt information
     *
     * GET /v2/hunt/live
     *
     * @return array
     *
     */
    public function getHuntLiveAction()
    {
        $this->authorize();
        $manager = $this->getDoctrine()->getManager();
        $array = array('live' => HuntSerialization::serializeHunts($this->getUser()->getAllHuntsLives(), $this->getUser()));
        
        return $this->view(
            $array, Codes::HTTP_OK
        );
    }

    /**
     * Return the hunt publication + discussion
     *
     * GET /v2/hunts/{lounge}/publication/discussion?limit=20&offset=0
     *
     * @param Request $request
     * @param Lounge $hunt
     * @return array
     *
     * @ParamConverter("hunt", class="NaturaPassLoungeBundle:Lounge")
     *
     */
    public function getHuntPublicationDiscussionAction(Request $request, Lounge $hunt)
    {
        $this->authorizeHunt($hunt);
        $this->authorizeLive($hunt);
        $list = array();
        foreach ($hunt->getPublications() as $publication) {
            if ($publication->getCreated() >= $hunt->getCreated() && ($hunt->getAllowShow() == Lounge::ALLOW_ALL_MEMBERS || $hunt->isAdmin($this->getUser()))) {
                $list[$publication->getCreated()->getTimestamp()] = array_merge(array("type" => "publication"), PublicationSerialization::serializePublication($publication, $this->getUser()));
            }
        }
        foreach ($hunt->getMessages() as $message) {
            if ($hunt->getAllowShowChat() == Lounge::ALLOW_ALL_MEMBERS || $hunt->isAdmin($this->getUser())) {
                $list[$message->getCreated()->getTimestamp()] = array_merge(array("type" => "message"), HuntSerialization::serializeHuntMessage($message));
            }
        }
        krsort($list);
        $listCollection = new ArrayCollection(array_values($list));
        return $this->view(
            array_values($listCollection->slice($request->query->get('offset', 0), $request->query->get('limit', 10))), Codes::HTTP_OK
        );
    }

    /**
     * add a hunt to DB
     *
     * POST /v2/hunts
     *
     * Content-Type: form-data
     *      lounge[name] = "Battue dimanche"
     *      lounge[description] = "Une bonne battue entre amis"
     *      lounge[geolocation] = [0 => Pas activé, 1 => Activé]
     *      lounge[access] = [0 => Privé, 1 => Semi-privé, 2 => Public]
     *      lounge[allow_add] = [0 => ADMIN, 1 => ALL_MEMBERS]
     *      lounge[allow_show] = [0 => ADMIN, 1 => ALL_MEMBERS]
     *      lounge[allow_add_chat] = [0 => ADMIN, 1 => ALL_MEMBERS]
     *      lounge[allow_show_chat] = [0 => ADMIN, 1 => ALL_MEMBERS]
     *      lounge[meetingAddress][address] = "Impasse du Jura, 01800 CHARNOZ SUR AIN, France"
     *      lounge[meetingAddress][latitude] = "45.5587"
     *      lounge[meetingAddress][longitude] = "7.566"
     *      lounge[meetingDate] = "20/03/2014 18:30"
     *      lounge[endDate] = "20/03/2014 18:30"
     *      lounge[photo][file] = Données de photo
     *
     * @param Request $request
     *
     * @return array
     *
     */
    public function postHuntAction(Request $request)
    {
        $this->authorize();
        $form = $this->createForm(new LoungeType($this->getUser(), $this->container), new Lounge(), array('csrf_protection' => false));
        $handler = new LoungeHandler($form, $request, $this->getDoctrine()->getManager());
        if ($lounge = $handler->process()) {
            return $this->view(
                array(
                    'hunt' => HuntSerialization::serializeHunt($lounge, $this->getUser(), true),
                    "sqlite" => SqliteSerialization::serializeSqliteInserOrReplace("tb_hunt", array(HuntSerialization::serializeHuntSqliteRefresh(array(), null, $lounge, $this->getUser(), true))),
                    "sqlite_agenda" => SqliteSerialization::serializeSqliteInserOrReplace("tb_agenda", array(HuntSerialization::serializeAllHuntArraySqlite($lounge, $this->getUser())))
                ), Codes::HTTP_CREATED
            );
        }

        return $this->view(array($form->getErrors(true)), Codes::HTTP_BAD_REQUEST);
    }

    /**
     * add a hunt to DB
     *
     * POST /v2/huntnews
     *
     * Content-Type: form-data
     *      lounge[name] = "Battue dimanche"
     *      lounge[description] = "Une bonne battue entre amis"
     *      lounge[geolocation] = [0 => Pas activé, 1 => Activé]
     *      lounge[access] = [0 => Privé, 1 => Semi-privé, 2 => Public]
     *      lounge[allow_add] = [0 => ADMIN, 1 => ALL_MEMBERS]
     *      lounge[allow_show] = [0 => ADMIN, 1 => ALL_MEMBERS]
     *      lounge[allow_add_chat] = [0 => ADMIN, 1 => ALL_MEMBERS]
     *      lounge[allow_show_chat] = [0 => ADMIN, 1 => ALL_MEMBERS]
     *      lounge[meetingAddress][address] = "Impasse du Jura, 01800 CHARNOZ SUR AIN, France"
     *      lounge[meetingAddress][latitude] = "45.5587"
     *      lounge[meetingAddress][longitude] = "7.566"
     *      lounge[meetingDate] = "20/03/2014 18:30"
     *      lounge[endDate] = "20/03/2014 18:30"
     *      lounge[invitePersons] = "20/03/2014 18:30"
     *      lounge[photo][file] = Données de photo
     *
     * @param Request $request
     *
     * @return array
     *
     */
    public function postHuntnewsAction(Request $request)
    {
        $this->authorize();
        $manager = $this->container->get('doctrine')->getManager();
        $form = $this->createForm(new LoungeType($this->getUser(), $this->container), new Lounge(), array('csrf_protection' => false));
        $handler = new LoungeHandler($form, $request, $this->getDoctrine()->getManager());
        if ($lounge = $handler->process()) {
            $invitePersons = $lounge->getInvitepersons();
            if(!is_null($invitePersons)){
                $arrayPersons = explode(',', $invitePersons);
                foreach ($arrayPersons as $person){
                    $manager = $this->getDoctrine()->getManager();
                    $repoUser = $manager->getRepository("NaturaPassUserBundle:User");

                    $receiver = $repoUser->findOneBy(array("id" => $person));
                    if(!is_null($receiver)){
                        $this->getNotificationService()->queue(
                            new LoungeJoinInvitedNotification($lounge), $receiver
                        );

                        $this->getEmailService()->generate(
                            'lounge.invite', array('%loungename%' => $lounge->getName(), '%fullname%' => $this->getUser()->getFullName()), array($receiver), 'NaturaPassEmailBundle:Lounge:invite-email-without-message.html.twig', array('lounge' => $lounge, 'fullname' => $this->getUser()->getFullName())
                        );
                        $loungeUser = new LoungeUser();
                        $loungeUser->setUser($receiver)
                            ->setLounge($lounge)
                            ->setAccess(LoungeUser::ACCESS_INVITED);
                        $manager->persist($loungeUser);
                        $manager->flush();
                    }
                }
            }


            //vietlh create parameters default with user when create group
                    $parameters[0]['type']='lounge.publication.new';
                    $parameters[0]['wanted']='1';
                    foreach ($parameters as $p) {

                        $parameters_id = $this->getUser()->getParameters();
                        $pref = new ParametersNotification();
                        $pref->setParameters($parameters_id)
                            ->setType($p['type']);

                        $pref->setWanted($p['wanted']);
                        $pref->setObjectID($lounge->getId());
                        $manager->persist($pref);

                        $manager->flush();

                    }
            //
            //import publications
            $hunt = $lounge;
            $groups = $request->request->get('groups', array());
            $categories = $request->request->get('categories', array());
            if (is_array($categories) && count($categories) && strpos($categories[0], ",")) {
                $categories = explode(',', $categories[0]);
            }

            $categories = array_unique($categories);
            $arrayCategories = array();
            if(count($categories) >= 2){
                foreach ($categories as $id_category) {
                    $em = $this->getDoctrine()->getManager();
                    $category = $em->getRepository('AdminSentinelleBundle:Category');
                    $arrayCategories = array_merge($arrayCategories, $category->recursiveTree(array($category->find($id_category))));
                }
                $arrayCategories = array_unique($arrayCategories);
                if (is_array($groups) && strpos($groups[0], ",")) {
                    $groups = explode(',', $groups[0]);
                }
            }


            $groups = array_unique($groups);
            $allgroup = array();
            $userGroups = $this->getUser()->getAllGroups();

            foreach ($groups as $group) {
                foreach ($userGroups as $userGroup) {
                    if ($group == $userGroup->getId() && $userGroup->isAdmin($this->getUser())) {
                        $allgroup[] = $group;
                        continue;
                    }
                }
            }

            $manager = $this->getDoctrine()->getManager();
            foreach ($allgroup as $id_group) {
                $groupObject = $manager->getRepository('NaturaPassGroupBundle:Group')->find($id_group);
                if (is_object($groupObject)) {
                    foreach ($groupObject->getPublications() as $publication) {
                        if (!$publication->hasHunt($hunt) && (count($arrayCategories) == 0 || $publication->hasCategories($arrayCategories))) {
                            $publication->addHunt($hunt);
                            $manager->persist($publication);

                            $val = PublicationSerialization::serializePublicationSqliteInsertOrReplace($publicationIds, $updated, $publication, $this->getUser());
                            if (!is_null($val)) {
                                $arrayValues[] = $val;
                            }
                        }
                    }
                }
            }
            $manager->flush();
            if (!empty($arrayValues)) {
                $return["sqlite"] = SqliteSerialization::serializeSqliteInserOrReplace("tb_carte", $arrayValues);
            }

            //end import
            return $this->view(
                array(
                    'hunt' => HuntSerialization::serializeHunt($lounge, $this->getUser(), true),
                    "sqlite" => SqliteSerialization::serializeSqliteInserOrReplace("tb_hunt", array(HuntSerialization::serializeHuntSqliteRefresh(array(), null, $lounge, $this->getUser(), true))),
                    "sqlite_agenda" => SqliteSerialization::serializeSqliteInserOrReplace("tb_agenda", array(HuntSerialization::serializeAllHuntArraySqlite($lounge, $this->getUser()))),
                    "sqlite_carte" => $return["sqlite"]
                ), Codes::HTTP_CREATED
            );
        }

        return $this->view(array($form->getErrors(true)), Codes::HTTP_BAD_REQUEST);

    }

    /**
     * Ajoute l'ensemble la chasse aux agendas
     *
     * POST /v2/hunts/{hunt_id}/groups
     *
     * Content-Type:
     *      groups[] = 1, 2, 3
     *
     * @param Request $request
     * @param Lounge $hunt
     * @return \FOS\RestBundle\View\View
     *
     * @throws HttpException
     * @ParamConverter("hunt", class="NaturaPassLoungeBundle:Lounge")
     */
    public function postHuntGroupAction(Request $request, Lounge $hunt)
    {
        if (!$hunt->isAdmin($this->getUser())) {
            throw new HttpException(Codes::HTTP_BAD_REQUEST, $this->message('codes.403'));
        }
        $groups = $request->request->get('groups', array());
        if (is_array($groups) && strpos($groups[0], ",")) {
            $groups = explode(',', $groups[0]);
        }

        $groups = array_unique($groups);
        $allgroup = array();
        $userGroups = $this->getUser()->getAllGroups();

        foreach ($groups as $group) {
            foreach ($userGroups as $userGroup) {
                if ($group == $userGroup->getId() && $userGroup->isAdmin($this->getUser())) {
                    $allgroup[] = $group;
                    continue;
                }
            }
        }

        if (!$request->request->has('groups')) {
            throw new HttpException(Codes::HTTP_BAD_REQUEST, $this->message('errors.parameters'));
        } else if (empty($allgroup)) {
            throw new HttpException(Codes::HTTP_BAD_REQUEST, $this->message('errors.group_admin'));
        }
        $manager = $this->getDoctrine()->getManager();
        foreach ($allgroup as $id_group) {
            $groupObject = $manager->getRepository('NaturaPassGroupBundle:Group')->find($id_group);
            if (is_object($groupObject) && !$hunt->hasGroup($groupObject)) {
                $hunt->addGroup($groupObject);
                $manager->persist($hunt);
            }
        }
        $manager->flush();
        return $this->view($this->success(), Codes::HTTP_OK);
    }

    /**
     * FR :
     * EN : Update hunt allow_add
     *
     * PUT /v2/hunts/{hunt}/allowaddshow
     *
     * Content-Type: form-data
     *      allow_add = [0 => ADMIN, 1 => ALL_MEMBERS]
     *      allow_show = [0 => ADMIN, 1 => ALL_MEMBERS]
     *
     *
     * @param Lounge $hunt
     * @param Request $request
     * @return \FOS\RestBundle\View\View
     *
     *
     * @ParamConverter("hunt", class="NaturaPassLoungeBundle:Lounge")
     */
    public function putHuntAllowaddshowAction(Lounge $hunt, Request $request)
    {
        $this->authorize($hunt->getAdmins());
        $em = $this->getDoctrine()->getManager();
        $allow_add = $request->request->get("allow_add", Lounge::ALLOW_ALL_MEMBERS);
        $allow_show = $request->request->get("allow_show", Lounge::ALLOW_ALL_MEMBERS);
        $hunt->setAllowAdd($allow_add);
        $hunt->setAllowShow($allow_show);
        $em->persist($hunt);
        $em->flush();
        $em->refresh($hunt);
        $subscribers = $hunt->getSubscribers(array(LoungeUser::ACCESS_DEFAULT, LoungeUser::ACCESS_ADMIN), true);
        $subscribers->removeElement($this->getUser());

        $this->getNotificationService()->queue(
            new LoungeChangeAllowNotification($hunt), $subscribers->toArray()
        );

        return $this->view(
            array(
                'success' => true,
                'hunt' => HuntSerialization::serializeHunt($hunt, $this->getUser(), true),
                "sqlite" => SqliteSerialization::serializeSqliteInserOrReplace("tb_hunt", array(HuntSerialization::serializeHuntSqliteRefresh(array(), null, $hunt, $this->getUser(), true)))
            ), Codes::HTTP_CREATED
        );
    }

    /**
     * FR :
     * EN : Update hunt allow_add
     *
     * PUT /v2/hunts/{hunt}/allowadd
     *
     * Content-Type: form-data
     *      allow_add = [0 => ADMIN, 1 => ALL_MEMBERS]
     *
     *
     * @param Lounge $hunt
     * @param Request $request
     * @return \FOS\RestBundle\View\View
     *
     *
     * @ParamConverter("hunt", class="NaturaPassLoungeBundle:Lounge")
     */
    public function putHuntAllowaddAction(Lounge $hunt, Request $request)
    {
        $this->authorize($hunt->getAdmins());
        $em = $this->getDoctrine()->getManager();
        $allow_add = $request->request->get("allow_add");
        $hunt->setAllowAdd($allow_add);
        $em->persist($hunt);
        $em->flush();
        $em->refresh($hunt);
        $subscribers = $hunt->getSubscribers(array(LoungeUser::ACCESS_DEFAULT, LoungeUser::ACCESS_ADMIN), true);
        $subscribers->removeElement($this->getUser());

        $this->getNotificationService()->queue(
            new LoungeChangeAllowNotification($hunt), $subscribers->toArray()
        );

        return $this->view(
            array(
                'success' => true,
                'hunt' => HuntSerialization::serializeHunt($hunt, $this->getUser(), true),
                "sqlite" => SqliteSerialization::serializeSqliteInserOrReplace("tb_hunt", array(HuntSerialization::serializeHuntSqliteRefresh(array(), null, $hunt, $this->getUser(), true)))
            ), Codes::HTTP_CREATED
        );
    }

    /**
     * FR :
     * EN : Update hunt allow_add
     *
     * PUT /v2/hunts/{hunt}/allowaddshowchat
     *
     * Content-Type: form-data
     *      allow_add_chat = [0 => ADMIN, 1 => ALL_MEMBERS]
     *      allow_show_chat = [0 => ADMIN, 1 => ALL_MEMBERS]
     *
     *
     * @param Lounge $hunt
     * @param Request $request
     * @return \FOS\RestBundle\View\View
     *
     *
     * @ParamConverter("hunt", class="NaturaPassLoungeBundle:Lounge")
     */
    public function putHuntAllowaddshowchatAction(Lounge $hunt, Request $request)
    {
        $this->authorize($hunt->getAdmins());
        $em = $this->getDoctrine()->getManager();
        $allow_add = $request->request->get("allow_add_chat", Lounge::ALLOW_ALL_MEMBERS);
        $allow_show = $request->request->get("allow_show_chat", Lounge::ALLOW_ALL_MEMBERS);
        $hunt->setAllowAddChat($allow_add);
        $hunt->setAllowShowChat($allow_show);
        $em->persist($hunt);
        $em->flush();
        $em->refresh($hunt);
        $subscribers = $hunt->getSubscribers(array(LoungeUser::ACCESS_DEFAULT, LoungeUser::ACCESS_ADMIN), true);
        $subscribers->removeElement($this->getUser());

        $this->getNotificationService()->queue(
            new LoungeChangeAllowNotification($hunt), $subscribers->toArray()
        );

        return $this->view(
            array(
                'success' => true,
                'hunt' => HuntSerialization::serializeHunt($hunt, $this->getUser(), true),
                "sqlite" => SqliteSerialization::serializeSqliteInserOrReplace("tb_hunt", array(HuntSerialization::serializeHuntSqliteRefresh(array(), null, $hunt, $this->getUser(), true)))
            ), Codes::HTTP_CREATED
        );
    }

    /**
     * FR :
     * EN : Update hunt allow_add
     *
     * PUT /v2/hunts/{hunt}/allowaddchat
     *
     * Content-Type: form-data
     *      allow_add_chat = [0 => ADMIN, 1 => ALL_MEMBERS]
     *
     *
     * @param Lounge $hunt
     * @param Request $request
     * @return \FOS\RestBundle\View\View
     *
     *
     * @ParamConverter("hunt", class="NaturaPassLoungeBundle:Lounge")
     */
    public function putHuntAllowaddchatAction(Lounge $hunt, Request $request)
    {
        $this->authorize($hunt->getAdmins());
        $em = $this->getDoctrine()->getManager();
        $allow_add = $request->request->get("allow_add_chat");
        $hunt->setAllowAddChat($allow_add);
        $em->persist($hunt);
        $em->flush();
        $em->refresh($hunt);
        $subscribers = $hunt->getSubscribers(array(LoungeUser::ACCESS_DEFAULT, LoungeUser::ACCESS_ADMIN), true);
        $subscribers->removeElement($this->getUser());

        $this->getNotificationService()->queue(
            new LoungeChangeAllowNotification($hunt), $subscribers->toArray()
        );

        return $this->view(
            array(
                'success' => true,
                'hunt' => HuntSerialization::serializeHunt($hunt, $this->getUser(), true),
                "sqlite" => SqliteSerialization::serializeSqliteInserOrReplace("tb_hunt", array(HuntSerialization::serializeHuntSqliteRefresh(array(), null, $hunt, $this->getUser(), true)))
            ), Codes::HTTP_CREATED
        );
    }

    /**
     * FR :
     * EN : Update hunt allow_show
     *
     * PUT /v2/hunts/{hunt}/allowshow
     *
     * Content-Type: form-data
     *      allow_show = [0 => ADMIN, 1 => ALL_MEMBERS]
     *
     *
     * @param Lounge $hunt
     * @param Request $request
     * @return \FOS\RestBundle\View\View
     *
     *
     * @ParamConverter("hunt", class="NaturaPassLoungeBundle:Lounge")
     */
    public function putHuntAllowshowAction(Lounge $hunt, Request $request)
    {
        $this->authorize($hunt->getAdmins());
        $em = $this->getDoctrine()->getManager();
        $allow_show = $request->request->get("allow_show");
        $hunt->setAllowShow($allow_show);
        $em->persist($hunt);
        $em->flush();
        $em->refresh($hunt);
        $subscribers = $hunt->getSubscribers(array(LoungeUser::ACCESS_DEFAULT, LoungeUser::ACCESS_ADMIN), true);
        $subscribers->removeElement($this->getUser());

        $this->getNotificationService()->queue(
            new LoungeChangeAllowNotification($hunt), $subscribers->toArray()
        );

        return $this->view(
            array(
                'success' => true,
                'hunt' => HuntSerialization::serializeHunt($hunt, $this->getUser(), true),
                "sqlite" => SqliteSerialization::serializeSqliteInserOrReplace("tb_hunt", array(HuntSerialization::serializeHuntSqliteRefresh(array(), null, $hunt, $this->getUser(), true)))
            ), Codes::HTTP_CREATED
        );
    }

    /**
     * FR :
     * EN : Update hunt allow_show
     *
     * PUT /v2/hunts/{hunt}/allowshowchat
     *
     * Content-Type: form-data
     *      allow_show_chat = [0 => ADMIN, 1 => ALL_MEMBERS]
     *
     *
     * @param Lounge $hunt
     * @param Request $request
     * @return \FOS\RestBundle\View\View
     *
     *
     * @ParamConverter("hunt", class="NaturaPassLoungeBundle:Lounge")
     */
    public function putHuntAllowshowchatAction(Lounge $hunt, Request $request)
    {
        $this->authorize($hunt->getAdmins());
        $em = $this->getDoctrine()->getManager();
        $allow_show = $request->request->get("allow_show_chat");
        $hunt->setAllowShowChat($allow_show);
        $em->persist($hunt);
        $em->flush();
        $em->refresh($hunt);
        $subscribers = $hunt->getSubscribers(array(LoungeUser::ACCESS_DEFAULT, LoungeUser::ACCESS_ADMIN), true);
        $subscribers->removeElement($this->getUser());

        $this->getNotificationService()->queue(
            new LoungeChangeAllowNotification($hunt), $subscribers->toArray()
        );

        return $this->view(
            array(
                'success' => true,
                'hunt' => HuntSerialization::serializeHunt($hunt, $this->getUser(), true),
                "sqlite" => SqliteSerialization::serializeSqliteInserOrReplace("tb_hunt", array(HuntSerialization::serializeHuntSqliteRefresh(array(), null, $hunt, $this->getUser(), true)))
            ), Codes::HTTP_CREATED
        );
    }

    /**
     * Mets � jour la participation + le commentaire publique d'un non membre � un salon
     *
     * PUT /v2/hunts/{HUNT_ID}/notmembers/{NOT_MEMBER_ID}/participationcomment
     *
     * JSON LIE
     * {
     *      "participation": [0 => Ne participe pas, 1 => Participe, 2 => Peut-�tre],
     *      "content": "Chef de la ligne 1"
     * }
     *
     * @param \NaturaPass\LoungeBundle\Entity\Lounge $lounge
     * @param integer $id
     * @param Request $request
     *
     * @ParamConverter("lounge", class="NaturaPassLoungeBundle:Lounge")
     *
     * @return \FOS\RestBundle\View\View
     * @throws HttpException
     */
    public function putHuntNotmemberParticipationcommentAction(Lounge $lounge, $idnotmember, Request $request)
    {
        $this->authorize($lounge->getAdmins()->toArray());
        if ($request->request->has('participation') && $request->request->has('content')) {
            if ($loungeUser = $lounge->isNotMember($idnotmember)) {
                $manager = $this->getDoctrine()->getManager();
                $loungeUser->setPublicComment($request->request->get('content'));
                $participation = $request->request->get('participation');
                $loungeUser->setParticipation($participation);
                $manager->persist($loungeUser);
                $manager->flush();

                $this->getNotificationService()->queue(
                    new LoungeNotUserParticipationNotification($lounge, $loungeUser), array()
                );

                return $this->view($this->success(), Codes::HTTP_OK);
            }
            throw new HttpException(Codes::HTTP_BAD_REQUEST, $this->message('errors.lounge.subscriber.unregistered'));
        }
        throw new HttpException(Codes::HTTP_BAD_REQUEST, $this->message('errors.parameters'));
    }

    /**
     * Update Hunt informations
     *
     * PUT /v2/hunts/{lounge}/information
     * Content-Type: Form-Data
     *      name = "Name"
     *      description = "Description"
     *      meetingDate = "dd-mm-yyyy hh:mm "
     *      endDate = "dd-mm-yyyy hh:mm "
     *
     * @param Lounge $hunt
     * @return array
     *
     * @ParamConverter("hunt", class="NaturaPassLoungeBundle:Lounge")
     */
    public function putHuntInformationAction(Lounge $hunt, Request $request)
    {
        if (!$hunt->isAdmin($this->getUser())) {
            throw new HttpException(Codes::HTTP_BAD_REQUEST, $this->message('codes.403'));
        }
        $em = $this->getDoctrine()->getManager();
        $name = $request->request->get("name");
        $description = $request->request->get("description");
        $meetingDate = $request->request->get("meetingDate");
        $endDate = $request->request->get("endDate");
        $hunt->setName($name);
        $hunt->setDescription($description);
        $hunt->setMeetingDate(new \DateTime(date('Y-m-d H:i:s', strtotime($meetingDate))));
        $hunt->setEndDate(new \DateTime(date('Y-m-d H:i:s', strtotime($endDate))));
        $em->persist($hunt);
        $em->flush();
        return $this->view(
            array(
                'success' => true,
                'hunt' => HuntSerialization::serializeHunt($hunt, $this->getUser(), true),
                "sqlite" => SqliteSerialization::serializeSqliteInserOrReplace("tb_hunt", array(HuntSerialization::serializeHuntSqliteRefresh(array(), null, $hunt, $this->getUser(), true)))
            ), Codes::HTTP_OK
        );
    }

    /**
     * Update Hunt access
     *
     * PUT /v2/hunts/{lounge}/access
     * Content-Type: Form-Data
     *      access = "Name"
     *
     * @param Lounge $hunt
     * @return array
     *
     * @ParamConverter("hunt", class="NaturaPassLoungeBundle:Lounge")
     */
    public function putHuntAccessAction(Lounge $hunt, Request $request)
    {
        if (!$hunt->isAdmin($this->getUser())) {
            throw new HttpException(Codes::HTTP_BAD_REQUEST, $this->message('codes.403'));
        }
        $em = $this->getDoctrine()->getManager();
        $access = $request->request->get("access");
        $hunt->setAccess($access);
        $em->persist($hunt);
        $em->flush();
        return $this->view(
            array(
                'success' => true,
                'hunt' => HuntSerialization::serializeHunt($hunt, $this->getUser(), true),
                "sqlite" => SqliteSerialization::serializeSqliteInserOrReplace("tb_hunt", array(HuntSerialization::serializeHuntSqliteRefresh(array(), null, $hunt, $this->getUser(), true)))
            ), Codes::HTTP_OK
        );
    }

    /**
     * Update Hunt access
     *
     * PUT /v2/hunts/{lounge}/geolocation
     * Content-Type: Form-Data
     *      address = "Address of meeting address"
     *      latitude = 20.996312
     *      longitude = 105.862114
     *      altitude = 10
     *
     * @param Lounge $hunt
     * @return array
     *
     * @ParamConverter("hunt", class="NaturaPassLoungeBundle:Lounge")
     */
    public function putHuntGeolocationAction(Lounge $hunt, Request $request)
    {
        if (!$hunt->isAdmin($this->getUser())) {
            throw new HttpException(Codes::HTTP_BAD_REQUEST, $this->message('codes.403'));
        }
        $latitude = $request->request->get('latitude', false);
        $longitude = $request->request->get('longitude', false);
        $altitude = $request->request->get('latitude', false);
        $address = $request->request->get('address', false);

        $em = $this->getDoctrine()->getManager();
        $access = $request->request->get("access");
        $hunt->setGeolocation(1);

        $meetingAddress = $hunt->getMeetingAddress();
        if (!$meetingAddress) {
            $meetingAddress = new \NaturaPass\MainBundle\Entity\Geolocation();
        }
        $meetingAddress->setAddress($address);
        $meetingAddress->setLatitude($latitude);
        $meetingAddress->setLongitude($longitude);
        $meetingAddress->setAltitude($altitude);
        $em->persist($meetingAddress);
        $em->flush();
        $hunt->setMeetingAddress($meetingAddress);
        $em->persist($hunt);
        $em->flush();
        return $this->view(
            array(
                'success' => true,
                'hunt' => HuntSerialization::serializeHunt($hunt, $this->getUser(), true),
                "sqlite" => SqliteSerialization::serializeSqliteInserOrReplace("tb_hunt", array(HuntSerialization::serializeHuntSqliteRefresh(array(), null, $hunt, $this->getUser(), true)))
            ), Codes::HTTP_OK
        );
    }

    /**
     * FR :
     * EN : get sqlite
     *
     * PUT /v2/hunt/sqlite
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
    public function putHuntSqliteAction(Request $request)
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
            $sendedHunt = $repoSended->findOneBy(array("user" => $this->getUser(), "guid" => $identifier, "type" => UserDeviceSended::TYPE_HUNT));
            if (!$reload && !is_null($sendedHunt)) {
                $huntIds = $sendedHunt->getIdsArray();
            } else {
                $huntIds = array();
            }
            if (is_null($sendedHunt)) {
                $sendedHunt = new UserDeviceSended();
                $sendedHunt->setUser($this->getUser());
                $sendedHunt->setGuid($identifier);
                $sendedHunt->setType(UserDeviceSended::TYPE_HUNT);
            }
            $return = array("sqlite" => array());
            $huntReturn = HuntSerialization::serializeHuntSqlite($huntIds, $updated, $this->getUser(), $sendedHunt);
            $return["sqlite"] = array_merge($return["sqlite"], $huntReturn);
            $manager->persist($sendedHunt);
            $manager->flush();
            return $this->view($return, Codes::HTTP_OK);
        }

    }

    //vietlh
    /**
     * Return the group parameters for users
     *
     * GET /v2/lounges/{lounge_id}/parameters/user
     *
     * @param \NaturaPass\LoungeBundle\Entity\Lounge $lounge
     * @return array
     *
     */
    public function getLoungeParametersUserAction($loungeID)
    {
        $this->authorize();
        $manager = $this->getDoctrine()->getManager();
        $parameters_id = $this->getUser()->getParameters();
        $paras = $manager->getRepository("NaturaPassUserBundle:ParametersNotification");
        $qb = $paras->createQueryBuilder('p')
            ->where('p.objectID = :grID')
            ->andWhere('p.parameters = :prID')
            ->setParameter('grID', $loungeID)
            ->setParameter('prID', $parameters_id)
            ->getQuery();

        $parameters = $qb->getArrayResult();

        if(count($parameters) == 0){
            $parameters[0]['type'] = 'lounge.publication.new';
                    $parameters[0]['wanted'] = '1';

                    foreach ($parameters as $p) {
                        $pref = new ParametersNotification();
                        $pref->setParameters($parameters_id)
                            ->setType($p['type']);

                        $pref->setWanted($p['wanted']);
                        $pref->setObjectID($loungeID);

                        $manager->persist($pref);

                        $manager->flush();

                    }
        }
        
        foreach ($parameters as $notification) {
            if($notification["type"] == 'lounge.publication.new')
            {
                $notification_parameter[] = array(
                "id" => $notification["type"],
                "label" => $this->get('translator')->trans("description." . $notification["type"], array(), $this->container->getParameter("translation_name") . 'notifications'),
                "wanted" => $notification["wanted"],
                );
            }
            
        }

            $paras = $manager->getRepository("NaturaPassLoungeBundle:LoungeUser");
                    $qb = $paras->createQueryBuilder('g')
                        ->where('g.lounge = :grID')
                        ->andWhere('g.user = :grUID')
                        ->setParameter('grID', $loungeID)
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

