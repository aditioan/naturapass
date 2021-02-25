<?php

/**
 * Created by PhpStorm.
 * User: vincentvalot
 * Date: 29/07/15
 * Time: 09:11
 */

namespace Api\ApiBundle\Controller\v2\Users;

use Admin\SentinelleBundle\Entity\Locality;
use Api\ApiBundle\Controller\v2\ApiRestController;
use Api\ApiBundle\Controller\v2\Serialization\DistributorSerialization;
use Api\ApiBundle\Controller\v2\Serialization\DogSerialization;
use Api\ApiBundle\Controller\v2\Serialization\MainSerialization;
use Api\ApiBundle\Controller\v2\Serialization\NewsSerialization;
use Api\ApiBundle\Controller\v2\Serialization\PublicationSerialization;
use Api\ApiBundle\Controller\v2\Serialization\ShapeSerialization;
use Api\ApiBundle\Controller\v2\Serialization\SqliteSerialization;
use Api\ApiBundle\Controller\v2\Serialization\UserSerialization;
use Api\ApiBundle\Controller\v2\Serialization\GroupSerialization;
use Api\ApiBundle\Controller\v2\Serialization\HuntSerialization;
use Api\ApiBundle\Controller\v2\Serialization\WeaponSerialization;
use Doctrine\ORM\QueryBuilder;
use Doctrine\ORM\Tools\Pagination\Paginator;
use FOS\RestBundle\Util\Codes;
use NaturaPass\NotificationBundle\Entity\NotificationReceiver;
use NaturaPass\UserBundle\Entity\User;
use NaturaPass\GroupBundle\Entity\GroupUser;
use NaturaPass\UserBundle\Entity\UserAddress;
use NaturaPass\UserBundle\Entity\UserDeviceSended;
use NaturaPass\UserBundle\Entity\LoadFlag;
use NaturaPass\PublicationBundle\Entity\PublicationDeleted;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\HttpException;
use NaturaPass\LoungeBundle\Entity\LoungeUser;
use NaturaPass\LoungeBundle\Entity\Lounge;

class UsersController extends ApiRestController
{

    /**
     * Get the users whom the name and firstname can match the q value
     *
     * GET /users/search?q=Chass
     *
     * @param Request $request
     * @return \FOS\RestBundle\View\View
     */
    public function getUsersSearchAction(Request $request)
    {
        $this->authorize();

        $search = urldecode($request->query->get('q', ''));
        $searchWithoutSpace = str_replace(' ', '', trim($search));

        /**
         * @var $qb QueryBuilder
         */
        $qb = $this->getDoctrine()->getManager()->createQueryBuilder();
        $qb->select('u')
            ->from('NaturaPassUserBundle:User', 'u')
            ->where(
                $qb->expr()->orX(
                    $qb->expr()->like('CONCAT(u.firstname, u.lastname)', ':name0'), $qb->expr()->like('CONCAT(u.lastname, u.firstname)', ':name0'), $qb->expr()->like('u.firstname', ':name1'), $qb->expr()->like('u.lastname', ':name1')
                )
            )
            ->andWhere('u.id != :connected')
            ->setParameter('name0', '%' . $searchWithoutSpace . '%')
            ->setParameter('name1', '%' . $search . '%')
            ->setParameter('connected', $this->getUser()->getId())
            ->setMaxResults($request->query->get('page_limit', 100000))
            ->setFirstResult($request->query->get('page_offset', 0));

        $users = UserSerialization::serializeUsers($qb->getQuery()->getResult(), $this->getUser());

        return $this->view(array(
            'users' => $users,
            'total' => count($users),
            'term' => $search
        ), Codes::HTTP_OK);
    }

    /**
     * Get the users whom the name and firstname can match the q value
     *
     * GET /users/search?q=Chass
     *
     * @param Request $request
     * @return \FOS\RestBundle\View\View
     */
    public function getUsersSearchlessAction(Request $request)
    {
        $this->authorize();

        $search = urldecode($request->query->get('q', ''));
        $searchWithoutSpace = str_replace(' ', '', trim($search));

        /**
         * @var $qb QueryBuilder
         */
        $qb = $this->getDoctrine()->getManager()->createQueryBuilder();
        $qb->select('u')
            ->from('NaturaPassUserBundle:User', 'u')
            ->where(
                $qb->expr()->orX(
                    $qb->expr()->like('CONCAT(u.firstname, u.lastname)', ':name0'), $qb->expr()->like('CONCAT(u.lastname, u.firstname)', ':name0'), $qb->expr()->like('u.firstname', ':name1'), $qb->expr()->like('u.lastname', ':name1')
                )
            )
            ->andWhere('u.id != :connected')
            ->setParameter('name0', '%' . $searchWithoutSpace . '%')
            ->setParameter('name1', '%' . $search . '%')
            ->setParameter('connected', $this->getUser()->getId())
            ->setMaxResults($request->query->get('page_limit', 100000))
            ->setFirstResult($request->query->get('page_offset', 0));

        $users = UserSerialization::serializeUserLesss($qb->getQuery()->getResult(), $this->getUser());

        return $this->view(array(
            'users' => $users,
            'total' => count($users),
            'term' => $search
        ), Codes::HTTP_OK);
    }

    /**
     * Get user data
     *
     * GET /v2/users/{user}
     *
     * @param \NaturaPass\UserBundle\Entity\User $user
     * @return array
     *
     * @ParamConverter("user", class="NaturaPassUserBundle:User")
     */
    public function getUserAction(User $user)
    {
        $this->authorize();

        $data = UserSerialization::serializeUser($user, $this->getUser());

        if ($user->getParameters()->getFriends()) {
            $data['friends'] = count($user->getFriends());
        }

        return $this->view(array('user' => $data), Codes::HTTP_OK);
    }

    /**
     * Get user's locked by current user
     *
     * GET /v2/user/lock
     *
     * @return array
     *
     */
    public function getUserLockAction()
    {
        $this->authorize();

        $datas = UserSerialization::serializeUserFullLesss($this->getUser()->getLocks());

        return $this->view(array('locked' => $datas), Codes::HTTP_OK);
    }

    /**
     * Get the data of the connected user
     *
     * GET /v2/user/connected
     *
     * @return array
     */
    public function getUserConnectedAction()
    {
        if ($this->isConnected()) {
            $data = UserSerialization::serializeUser($this->getUser());
            $data['birthday'] = ($this->getUser()->getBirthday() ? date("d/m/Y", $this->getUser()->getBirthday()->getTimestamp()) : "");
            $address = $this->getUser()->getFavoriteAddress();
            if ($address instanceof UserAddress) {
                $data['address'] = MainSerialization::serializeAddress($address);
            }

            return $this->view(array('user' => $data), Codes::HTTP_OK);
        }

        return $this->view(null, Codes::HTTP_NO_CONTENT);
    }

    /**
     * retourne le display d'amis des paramètres
     *
     * GET /v2/user/parameters/friends
     *
     * @return \FOS\RestBundle\View\View
     */
    public function getUserParametersFriendsAction(Request $request)
    {
        $this->authorize();
        $parameters = $this->getUser()->getParameters();
        return $this->view(array('friend' => (boolean)$parameters->getFriends()), Codes::HTTP_OK);
    }


    /**
     * return user email parameter settings
     *
     * GET /v2/user/profile/email/parameters
     *
     * @return \FOS\RestBundle\View\View
     */
    public function getUserProfileEmailParametersAction()
    {
        $this->authorize();
        $manager = $this->container->get('doctrine')->getManager();
        $emails = $manager->getRepository('NaturaPassEmailBundle:EmailModel')->findBy(array(), array('order' => 'ASC'));
        $email_parameter = array();
        foreach ($emails as $email) {
            $wanted = 0;
            $type = $this->getUser()->getParameters()->getEmailByType($email->getType());
            if (($type && $type->getWanted()) || !$type) {
                $wanted = 1;
            }
            $email_parameter[] = array(
                "id" => $email->getId(),
                "label" => $this->get('translator')->trans($email->getType() . ".description", array(), $this->container->getParameter("translation_name") . 'email'),
                "wanted" => $wanted
            );
        }
        return $this->view(array('emails' => $email_parameter), Codes::HTTP_OK);
    }

    /**
     * return user notification parameter settings
     *
     * GET /v2/user/profile/notification/parameters
     *
     * @return \FOS\RestBundle\View\View
     */
    public function getUserProfileNotificationParametersAction()
    {
        $this->authorize();
        $manager = $this->container->get('doctrine')->getManager();
        $metadata = $manager->getMetadataFactory()->getMetadataFor('NaturaPass\NotificationBundle\Entity\AbstractNotification');
        $notificationstypes = array_keys($metadata->discriminatorMap);
        foreach ($notificationstypes as $notification) {
            $wanted = 0;
            $type = $this->getUser()->getParameters()->getNotificationByType($notification);
            if (($type && $type->getWanted()) || !$type) {
                $wanted = 1;
            }
            $notification_parameter[] = array(
                "id" => $notification,
                "label" => $this->get('translator')->trans("description." . $notification, array(), $this->container->getParameter("translation_name") . 'notifications'),
                "wanted" => $wanted
            );
        }

        return $this->view(array('notifications' => $notification_parameter), Codes::HTTP_OK);
    }

    //vietlh

    /**
     * return user email parameter settings
     *
     * GET /v2/user/profile/email/parametersbygroup
     *
     * @return \FOS\RestBundle\View\View
     */
    public function getUserProfileEmailParametersbygroupAction()
    {
        $this->authorize();
        $manager = $this->container->get('doctrine')->getManager();
        $emails = $manager->getRepository('NaturaPassEmailBundle:EmailModel')->findBy(array(), array('order' => 'ASC'));
        $email_parameter = array();
        foreach ($emails as $email) {
            $wanted = 0;
            $type = $this->getUser()->getParameters()->getEmailByType($email->getType());
            if (($type && $type->getWanted()) || !$type) {
                $wanted = 1;
            }
            if (strpos($email->getType(), 'invitation') !== FALSE && strpos($notification, 'invitation') == '0') {
                $key = 'amis';
            }
            if (strpos($email->getType(), 'publication') !== FALSE && strpos($notification, 'publication') == '0') {
                $key = 'publication';
            }
            if (strpos($email->getType(), 'lounge') !== FALSE && strpos($notification, 'lounge') == '0') {
                $key = 'agenda';
            }
            if (strpos($email->getType(), 'group') !== FALSE && strpos($notification, 'group') == '0' && $email->getType() != 'group.publication_added') {
                $key = 'group';
            }
            
            $email_parameter[$key][] = array(
                "id" => $email->getId(),
                "label" => $this->get('translator')->trans($email->getType() . ".description", array(), $this->container->getParameter("translation_name") . 'email'),
                "wanted" => $wanted
            );
        }
        return $this->view(array('emails' => $email_parameter), Codes::HTTP_OK);
    }

    /**
     * return user notification parameter settings
     *
     * GET /v2/user/profile/notification/parametersbygroup
     *
     * @return \FOS\RestBundle\View\View
     */
    public function getUserProfileNotificationParametersbygroupAction()
    {
        $this->authorize();
        $manager = $this->container->get('doctrine')->getManager();
        $metadata = $manager->getMetadataFactory()->getMetadataFor('NaturaPass\NotificationBundle\Entity\AbstractNotification');
        $notificationstypes = array_keys($metadata->discriminatorMap);
        foreach ($notificationstypes as $notification) {
            if($notification != 'lounge.publication.new' && $notification != 'group.publication.new'){
                $wanted = 0;
                $type = $this->getUser()->getParameters()->getNotificationByType($notification);
                if (($type && $type->getWanted()) || !$type) {
                    $wanted = 1;
                }

                if (strpos($notification, 'user') !== FALSE && strpos($notification, 'user') == '0') {
                    $key = 'amis';
                }
                if (strpos($notification, 'publication') !== FALSE && strpos($notification, 'publication') == '0') {
                    $key = 'publication';
                }
                if (strpos($notification, 'lounge') !== FALSE && strpos($notification, 'lounge') == '0') {
                    $key = 'agenda';
                }
                if (strpos($notification, 'group') !== FALSE && strpos($notification, 'group') == '0') {
                    $key = 'group';
                }
                if (strpos($notification, 'chat') !== FALSE && strpos($notification, 'chat') == '0') {
                    $key = 'chat';
                }

                $notification_parameter[$key][] = array(
                    "id" => $notification,
                    "label" => $this->get('translator')->trans("description." . $notification, array(), $this->container->getParameter("translation_name") . 'notifications'),
                    "wanted" => $wanted
                );

            }

        }

        return $this->view(array('notifications' => $notification_parameter), Codes::HTTP_OK);
    }

    //end

    /**
     * change le display d'amis des paramètres
     *
     * PUT /v2/user/parameters/friends
     *
     * JSON LIE
     * {
     *      "friend": [0 => OFF, 1 => ON]
     * }
     *
     * @param Request $request
     * @return \FOS\RestBundle\View\View
     */
    public function putUserParametersFriendsAction(Request $request)
    {
        $this->authorize();
        if ($request->request->has('friend')) {
            $friend = $request->request->get('friend');
            $parameters = $this->getUser()->getParameters();
            $parameters->setFriends($friend);
            $manager = $this->getDoctrine()->getManager();
            $manager->persist($parameters);
            $manager->flush();
            return $this->view(array_merge($this->success(), array("friend" => (boolean)$friend)), Codes::HTTP_OK);
        }
        throw new HttpException(Codes::HTTP_BAD_REQUEST, $this->message('errors.lounge.subscriber.unregistered'));
    }

    /**
     * lock an user
     *
     * POST /v2/users/locks
     *
     *{
     *      "id": 1
     * }
     *
     * @param Request $request
     * @return \FOS\RestBundle\View\View
     */
    public function postUserLockAction(Request $request)
    {
        $this->authorize();

        if ($request->request->has('id')) {
            $id = $request->request->get('id', false);
            $manager = $this->getDoctrine()->getManager();
            $user = $manager->getRepository("NaturaPassUserBundle:User")->findOneBy(array("id" => $id));
            $this->getUser()->addLock($user);
            $manager->merge($this->getUser());
            $manager->flush();
            return $this->view($this->success(), Codes::HTTP_OK);
        } else {
            throw new HttpException(Codes::HTTP_BAD_REQUEST, $this->message('errors.parameters'));
        }
    }

    /**
     * change le mdp de l'utilisateur connecté
     *
     * POST /v2/users/changepasswords
     *
     * user[current_password]
     * user[new][first]
     * user[new][second]
     *
     * @param Request $request
     * @return \FOS\RestBundle\View\View
     */
    public function postUserChangepasswordAction(Request $request)
    {
        $user = $this->getUser();
        if ($user->getPassword() == $request->request->get("user")["current_password"]) {
            if ($request->request->get("user")["new"]["first"] == $request->request->get("user")["new"]["second"]) {
                $user->setPassword($request->request->get("user")["new"]["first"])
                    ->setConfirmationToken(null)
                    ->setPasswordRequestedAt(null);
                $this->container->get('fos_user.user_manager')->updateUser($user);
                return $this->view($this->success(), Codes::HTTP_OK);
            } else {
                throw new HttpException(Codes::HTTP_BAD_REQUEST, $this->message('errors.user.verification_password'));
            }
        } else {
            throw new HttpException(Codes::HTTP_BAD_REQUEST, $this->message('errors.user.current_password'));
        }
    }

    /**
     * Get news from admin
     *
     * GET /v2/user/news
     *
     * @return array
     */
    public function getUserNewsAction(Request $request)
    {
        $this->authorize();

        $search = urldecode($request->query->get('q', ''));
        $searchWithoutSpace = str_replace(' ', '', trim($search));

        /**
         * @var $qb QueryBuilder
         */
        $qb = $this->getDoctrine()->getManager()->createQueryBuilder();
        $qb->select('n')
            ->from('AdminNewsBundle:News', 'n')
            ->andWhere('n.active = 1')
            ->orderBy('n.date', 'DESC');

        $results = $qb->getQuery()->getResult();

        return $this->view(array(
            'news' => NewsSerialization::serializeNews($results),
            'total' => count($results)
        ), Codes::HTTP_OK);
    }

    /**
     * Return groups that current user is admin
     *
     * GET /v2/users/groups/admin
     *
     * @param Request $request
     * @return \FOS\RestBundle\View\View
     *
     */
    public function getUsersGroupsAdminAction(Request $request)
    {
        $this->authorize();
        $userGroups = $this->getUser()->getAllGroups(array(\NaturaPass\GroupBundle\Entity\GroupUser::ACCESS_ADMIN));
        $groups = GroupSerialization::serializeGroups($userGroups, $this->getUser());
        return $this->view(array(
            'groups' => $groups,
            'total' => count($groups)
        ), Codes::HTTP_OK);
    }

    /**
     * Return hunts that current user is admin
     *
     * GET /v2/users/hunts/admin
     *
     * @param Request $request
     * @return \FOS\RestBundle\View\View
     *
     */
    public function getUsersHuntsAdminAction(Request $request)
    {
        $this->authorize();
        $userHunts = $this->getUser()->getAllHunts(array(\NaturaPass\LoungeBundle\Entity\LoungeUser::ACCESS_ADMIN));
        $hunts = HuntSerialization::serializeHunts($userHunts, $this->getUser());
        return $this->view(array(
            'hunts' => $hunts,
            'total' => count($hunts)
        ), Codes::HTTP_OK);
    }

    /**
     * Delete a user's locked
     *
     * DELETE /v2/users/{id}/lock
     *
     * @param \NaturaPass\UserBundle\Entity\User $user
     *
     * @ParamConverter("user", class="NaturaPassUserBundle:User")
     *
     * @return View
     * @throws HttpException
     */
    public function deleteUserLockAction(User $user)
    {
        $this->authorize();
        $manager = $this->getDoctrine()->getManager();
        $this->getUser()->removeLock($user);
        $manager->persist($this->getUser());
        $manager->flush();
        return $this->view($this->success(), Codes::HTTP_OK);
    }


    /**
     * get Sqlite of all points in map of the current user
     *
     * PUT /v2/user/sqlite
     *
     * {
     *   "identifier": "e0366ffd02fdd942a960119e71fce654c2ccf5",
     *   "version":{
     *      "version":"20160222114711"
     *    },
     *   "publication":{
     *      "updated":"1450264747",
     *      "ids":"1,2,3,4"
     *    },
     *   "shape":{
     *      "updated":"1450264747"
     *    },
     *    "distributor":{
     *      "updated":""
     *    },
     *   "group":{
     *      "updated":"1450264747"
     *      "ids":"1,2,3,4"
     *    },
     *   "hunt":{
     *      "updated":"1450264747"
     *      "ids":"1,2,3,4"
     *    }
     *    "address":{
     *      "reload":[1: force reload, 0: don't force reload]
     *    },
     *   "favorite":{
     *      "updated":"1450264747"
     *      "ids":"1,2,3,4"
     *    },
     *    "color":{
     *      "reload":[1: force reload, 0: don't force reload]
     *    },
     *    "breed":{
     *      "reload":[1: force reload, 0: don't force reload]
     *    },
     *    "type":{
     *      "reload":[1: force reload, 0: don't force reload]
     *    },
     *    "brand":{
     *      "reload":[1: force reload, 0: don't force reload]
     *    },
     *    "calibre":{
     *      "reload":[1: force reload, 0: don't force reload]
     *    }
     * }
     *
     * INFO :   address doesn't need updated
     *          color doesn't need updated
     *          distributor doesn't need ids
     *          To get all elements, just send updated:"" (don't send ids) or just send ids and not updated just for "color" and "address"
     *
     * @param Request $request
     * @return \FOS\RestBundle\View\View
     */
    public function putUserSqliteAction(Request $request)
    {
        $this->authorize();
        session_write_close();
        $manager = $this->getDoctrine()->getManager();
        $identifier = $request->request->get('identifier', false);
        $limit = $request->request->get('limit', 10);
        $offset = $request->request->get('offset', 0);
        $return = array("sqlite" => array());
        if ($identifier) {
            $repoSended = $manager->getRepository("NaturaPassUserBundle:UserDeviceSended");
            $version = $request->request->get('version', false);
            $haveVersion = false;
            if ($version) {
                $requestVersion = new Request($_GET, $version, array(), $_COOKIE, $_FILES, $_SERVER);
                $lastVersion = $requestVersion->get('version', false);
                $qb = $manager->createQueryBuilder()->select('v')
                    ->from('NaturaPassUserBundle:DeviceDbVersion', 'v')
                    ->where('v.version > :version')
                    ->setParameter('version', $lastVersion)
                    ->getQuery();

                $paginators = new Paginator($qb, $fetchJoinCollection = true);
                $return["versions"] = array();
                foreach ($paginators as $version) {
                    $get = UserSerialization::serializeVersionSqlite($version);
                    if (!is_null($get)) {
                        $haveVersion = true;
                        $return["versions"][] = $get;
                    }
                }
            }

            $publication = $request->request->get('publication', false);
            if ($publication) {
                $requestPublication = new Request($_GET, $publication, array(), $_COOKIE, $_FILES, $_SERVER);
                $updated = $haveVersion ? $haveVersion : $requestPublication->get('updated', false);
                $reload = $requestPublication->get('reload', false);
                $ids = $requestPublication->get('ids', array());
                if ($ids == "") {
                    $ids = array();
                } elseif (!is_array($ids)) {
                    $ids = explode(",", $ids);
                }
                if (!$updated || $updated == "") {
                    $reload = true;
                }
                $sendedPub = $repoSended->findOneBy(array("user" => $this->getUser(), "guid" => $identifier, "type" => UserDeviceSended::TYPE_MAP));
                if (!$reload && !is_null($sendedPub)) {
                    $publicationIds = $sendedPub->getIdsArray();
                } else {
                    $publicationIds = array();
                }
                if (!is_array($publicationIds)) {
                    $publicationIds = explode(",", $publicationIds);
                }
                $publicationIds = array_merge($publicationIds, $ids);
                $filter = array(
                    'groups' => array(),
                    'hunts' => array(),
                    'sharing' => 3
                );
                $userGroups = $this->getUser()->getAllGroups();
                foreach ($userGroups as $group) {
                    $filter["groups"][] = $group->getId();
                }
                $userHunts = $this->getUser()->getAllHunts();
                foreach ($userHunts as $hunt) {
                    $filter["hunts"][] = $hunt->getId();
                }
                $qb = $this->getSharingQueryBuilder(
                    'NaturaPassPublicationBundle:Publication', 'p', $filter, 3
                );
                $qb->andWhere("p.geolocation IS NOT NULL");
                $qb->orderBy('p.created', 'DESC')
//                    ->setFirstResult($offset)
//                    ->setMaxResults($limit)
                    ->getQuery();
                $return["sqlite"]["publications"] = array();
                $paginators = new Paginator($qb, $fetchJoinCollection = true);
                $arrayGoodIds = array();
                $arrayValues = array();
                foreach ($paginators as $publication) {
                    $arrayGoodIds[] = $publication->getId();
                    $val = PublicationSerialization::serializePublicationSqliteInsertOrReplace($publicationIds, $updated, $publication, $this->getUser());
                    if (!is_null($val)) {
                        $arrayValues[] = $val;
                    }
                }
                if (!empty($arrayValues)) {
                    $return["sqlite"]["publications"] = array_merge($return["sqlite"]["publications"], SqliteSerialization::serializeSqliteInserOrReplace("tb_carte", $arrayValues));
                }
                $arrayDeleteId = array();
                foreach ($publicationIds as $publicationId) {
                    if (!in_array($publicationId, $arrayGoodIds)) {
                        $arrayDeleteId[] = $publicationId;
                    }
                }
                if (count($arrayDeleteId)) {
                    $return["sqlite"]["publications"][] = "DELETE FROM `tb_carte` WHERE `c_id` IN (" . join(',', $arrayDeleteId) . ") AND `c_user_id` = '" . $this->getUser()->getId() . "';";
                }
                if (is_null($sendedPub)) {
                    $sendedPub = new UserDeviceSended();
                    $sendedPub->setUser($this->getUser());
                    $sendedPub->setGuid($identifier);
                    $sendedPub->setType(UserDeviceSended::TYPE_MAP);
                }
                $sendedPub->setIds(implode(",", $arrayGoodIds));
                $manager->persist($sendedPub);
                $manager->flush();
            }

            $shape = $request->request->get('shape', false);
            if ($shape) {
                $requestShape = new Request($_GET, $shape, array(), $_COOKIE, $_FILES, $_SERVER);
                $updated = $haveVersion ? $haveVersion : $requestShape->get('updated', $haveVersion);
                $reload = $requestShape->get('reload', false);
                if (!$updated || $updated == "") {
                    $reload = true;
                }
                $sendedShape = $repoSended->findOneBy(array("user" => $this->getUser(), "guid" => $identifier, "type" => UserDeviceSended::TYPE_SHAPE));
                if (!$reload && !is_null($sendedShape)) {
                    $sahpeIds = $sendedShape->getIdsArray();
                } else {
                    $sahpeIds = array();
                }
                if (!is_array($sahpeIds)) {
                    $sahpeIds = explode(",", $sahpeIds);
                }
                $filter = array(
                    'groups' => array(),
                    'hunts' => array(),
                    'sharing' => 3
                );
                $userGroups = $this->getUser()->getAllGroups();
                foreach ($userGroups as $group) {
                    $filter["groups"][] = $group->getId();
                }
                $userHunts = $this->getUser()->getAllHunts();
                foreach ($userHunts as $hunt) {
                    $filter["hunts"][] = $hunt->getId();
                }
                $qb = $this->getSharingQueryBuilder(
                    'NaturaPassMainBundle:Shape',
                    'p',
                    $filter,
                    3
                );
                $qb->orderBy('p.created', 'DESC')
//                    ->setFirstResult($offset)
//                    ->setMaxResults($limit)
                    ->getQuery();
                $return["sqlite"]["shapes"] = array();
                $paginators = new Paginator($qb, $fetchJoinCollection = true);
                $allIds = array();
                $arrayValues = array();
                foreach ($paginators as $shape) {
                    $allIds[] = $shape->getId();
                    if (is_null($shape->getLatCenter()) || is_null($shape->getLonCenter())) {
                        $shape->getCentre();
                        $manager->persist($shape);
                        $manager->flush();
                    }
                    $val = ShapeSerialization::serializeShapeSqliteInsertOrReplace($sahpeIds, $updated, $shape, $this->getUser());
                    if (!is_null($val)) {
                        $arrayValues[] = $val;
                    }
                }
                if (!empty($arrayValues)) {
                    $return["sqlite"]["shapes"] = array_merge($return["sqlite"]["shapes"], SqliteSerialization::serializeSqliteInserOrReplace("tb_shape", $arrayValues));
                }
                $arrayDeleteId = array();
                foreach ($sahpeIds as $shapeId) {
                    if (!in_array($shapeId, $allIds)) {
                        $arrayDeleteId[] = $shapeId;
                    }
                }
                if (count($arrayDeleteId)) {
                    $return["sqlite"]["shapes"][] = "DELETE FROM `tb_shape` WHERE `c_id` IN (" . join(',', $arrayDeleteId) . ") AND `c_user_id` = '" . $this->getUser()->getId() . "';";
                }
                if (is_null($sendedShape)) {
                    $sendedShape = new UserDeviceSended();
                    $sendedShape->setUser($this->getUser());
                    $sendedShape->setGuid($identifier);
                    $sendedShape->setType(UserDeviceSended::TYPE_SHAPE);
                }
                $sendedShape->setIds(implode(",", $allIds));
                $manager->persist($sendedShape);
                $manager->flush();
            }

            $distributor = $request->request->get('distributor', false);
            if ($distributor) {
                $requestDistributor = new Request($_GET, $distributor, array(), $_COOKIE, $_FILES, $_SERVER);
                $updated = $requestDistributor->get('updated', false);

                $return["sqlite"]["distributors"] = array();
                if (!$updated || $updated == "") {
                    $return["sqlite"]["distributors"][] = "DELETE FROM `tb_distributor`;";
                }
                $qb = $manager->createQueryBuilder()->select('d')
                    ->from('AdminDistributorBundle:Distributor', 'd')
//                    ->setFirstResult($offset)
//                    ->setMaxResults($limit)
                    ->getQuery();

                $paginators = new Paginator($qb, $fetchJoinCollection = true);
                $arrayValues = array();
                foreach ($paginators as $distributor) {
                    $val = DistributorSerialization::serializeDistributorSqliteInsertOrReplace($updated, $distributor);
                    if (!is_null($val)) {
                        $arrayValues[] = $val;
                    }
                }
                if (!empty($arrayValues)) {
                    $return["sqlite"]["distributors"] = array_merge($return["sqlite"]["distributors"], SqliteSerialization::serializeSqliteInserOrReplace("tb_distributor", $arrayValues));
                }
            }

            $group = $request->request->get('group', false);
            if ($group) {
                $requestGroup = new Request($_GET, $group, array(), $_COOKIE, $_FILES, $_SERVER);
                $updated = $haveVersion ? $haveVersion : $requestGroup->get('updated', $haveVersion);
                $reload = $requestGroup->get('reload', false);
                $ids = $requestGroup->get('ids', array());
                if ($ids == "") {
                    $ids = array();
                } elseif (!is_array($ids)) {
                    $ids = explode(",", $ids);
                }
                if (!$updated || $updated == "") {
                    $reload = true;
                }
                $sendedGroup = $repoSended->findOneBy(array("user" => $this->getUser(), "guid" => $identifier, "type" => UserDeviceSended::TYPE_GROUP));
                if (!$reload && !is_null($sendedGroup)) {
                    $groupIds = $sendedGroup->getIdsArray();
                } else {
                    $groupIds = array();
                }
                $groupIds = array_merge($groupIds, $ids);
                if (is_null($sendedGroup)) {
                    $sendedGroup = new UserDeviceSended();
                    $sendedGroup->setUser($this->getUser());
                    $sendedGroup->setGuid($identifier);
                    $sendedGroup->setType(UserDeviceSended::TYPE_GROUP);
                }
                $groupReturn = GroupSerialization::serializeGroupSqlite($groupIds, $updated, $this->getUser(), $sendedGroup);
                $return["sqlite"]["groups"] = array();
                $return["sqlite"]["groups"] = array_merge($return["sqlite"]["groups"], $groupReturn);
                $manager->persist($sendedGroup);
                $manager->flush();
            }

            $hunt = $request->request->get('hunt', false);
            if ($hunt) {
                $requestHunt = new Request($_GET, $hunt, array(), $_COOKIE, $_FILES, $_SERVER);
                $updated = $haveVersion ? $haveVersion : $requestHunt->get('updated', $haveVersion);
                $reload = $requestHunt->get('reload', false);
                $ids = $requestHunt->get('ids', array());
                if ($ids == "") {
                    $ids = array();
                } elseif (!is_array($ids)) {
                    $ids = explode(",", $ids);
                }
                if (!$updated || $updated == "") {
                    $reload = true;
                }
                $sendedHunt = $repoSended->findOneBy(array("user" => $this->getUser(), "guid" => $identifier, "type" => UserDeviceSended::TYPE_HUNT));
                if (!$reload && !is_null($sendedHunt)) {
                    $huntIds = $sendedHunt->getIdsArray();
                } else {
                    $huntIds = array();
                }
                $huntIds = array_merge($huntIds, $ids);
                if (is_null($sendedHunt)) {
                    $sendedHunt = new UserDeviceSended();
                    $sendedHunt->setUser($this->getUser());
                    $sendedHunt->setGuid($identifier);
                    $sendedHunt->setType(UserDeviceSended::TYPE_HUNT);
                }
                $huntReturn = HuntSerialization::serializeHuntSqlite($huntIds, $updated, $this->getUser(), $sendedHunt);
                $return["sqlite"]["hunts"] = array();
                $return["sqlite"]["hunts"] = array_merge($return["sqlite"]["hunts"], $huntReturn);
                $manager->persist($sendedHunt);
                $manager->flush();
            }

            $favoriteAddress = $request->request->get('address', false);
            if ($favoriteAddress) {
                $requestAddress = new Request($_GET, $favoriteAddress, array(), $_COOKIE, $_FILES, $_SERVER);
                $sendedAddress = $repoSended->findOneBy(array("user" => $this->getUser(), "guid" => $identifier, "type" => UserDeviceSended::TYPE_ADDRESS));
                $reload = $requestAddress->get('reload', false);
                if (!$reload && !is_null($sendedAddress)) {
                    $addressIds = $sendedAddress->getIdsArray();
                } else {
                    $addressIds = array();
                }
                if (is_null($sendedAddress)) {
                    $sendedAddress = new UserDeviceSended();
                    $sendedAddress->setUser($this->getUser());
                    $sendedAddress->setGuid($identifier);
                    $sendedAddress->setType(UserDeviceSended::TYPE_ADDRESS);
                }
                $addressReturn = UserSerialization::serializeAddressSqlite($addressIds, $this->getUser(), $sendedAddress);
                $return["sqlite"]["addresss"] = array();
                $return["sqlite"]["addresss"] = array_merge($return["sqlite"]["addresss"], $addressReturn);
                $manager->persist($sendedAddress);
                $manager->flush();
            }

            $color = $request->request->get('color', false);
            if ($color) {
                $requestColor = new Request($_GET, $color, array(), $_COOKIE, $_FILES, $_SERVER);
                $sendedColor = $repoSended->findOneBy(array("user" => $this->getUser(), "guid" => $identifier, "type" => UserDeviceSended::TYPE_COLOR));
                $reload = $requestColor->get('reload', false);
                if (!$reload && !is_null($sendedColor)) {
                    $colorIds = $sendedColor->getIdsArray();
                } else {
                    $colorIds = array();
                }
                if (is_null($sendedColor)) {
                    $sendedColor = new UserDeviceSended();
                    $sendedColor->setUser($this->getUser());
                    $sendedColor->setGuid($identifier);
                    $sendedColor->setType(UserDeviceSended::TYPE_COLOR);
                }
                $colorReturn = PublicationSerialization::serializeColorSqlite($colorIds, $manager, $sendedColor);
                $return["sqlite"]["colors"] = array();
                $return["sqlite"]["colors"] = array_merge($return["sqlite"]["colors"], $colorReturn);
                $manager->persist($sendedColor);
                $manager->flush();
            }

            $favorite = $request->request->get('favorite', false);
            if ($favorite) {
                $requestFavorite = new Request($_GET, $favorite, array(), $_COOKIE, $_FILES, $_SERVER);
                $updated = $haveVersion ? $haveVersion : $requestFavorite->get('updated', $haveVersion);
                $reload = $requestFavorite->get('reload', false);
                $ids = $requestFavorite->get('ids', array());
                if ($ids == "") {
                    $ids = array();
                } elseif (!is_array($ids)) {
                    $ids = explode(",", $ids);
                }
                if (!$updated || $updated == "") {
                    $reload = true;
                }
                $sendedFavorite = $repoSended->findOneBy(array("user" => $this->getUser(), "guid" => $identifier, "type" => UserDeviceSended::TYPE_FAVORITE));
                if (!$reload && !is_null($sendedFavorite)) {
                    $favoriteIds = $sendedFavorite->getIdsArray();
                } else {
                    $favoriteIds = array();
                }
                $favoriteIds = array_merge($favoriteIds, $ids);
                if (is_null($sendedFavorite)) {
                    $sendedFavorite = new UserDeviceSended();
                    $sendedFavorite->setUser($this->getUser());
                    $sendedFavorite->setGuid($identifier);
                    $sendedFavorite->setType(UserDeviceSended::TYPE_FAVORITE);
                }
                $favReturn = UserSerialization::serializeFavoriteSqlite($favoriteIds, $updated, $this->getUser(), $sendedFavorite);
                $return["sqlite"]["favorites"] = array();
                $return["sqlite"]["favorites"] = array_merge($return["sqlite"]["favorites"], $favReturn);
                $manager->persist($sendedFavorite);
                $manager->flush();
            }

            $breed = $request->request->get('breed', false);
            if ($breed) {
                $requestBreed = new Request($_GET, $breed, array(), $_COOKIE, $_FILES, $_SERVER);
                $sendedBreed = $repoSended->findOneBy(array("user" => $this->getUser(), "guid" => $identifier, "type" => UserDeviceSended::TYPE_DOG_BREED));
                $reload = $requestBreed->get('reload', false);
                if (!$reload && !is_null($sendedBreed)) {
                    $breedIds = $sendedBreed->getIdsArray();
                } else {
                    $breedIds = array();
                }
                if (is_null($sendedBreed)) {
                    $sendedBreed = new UserDeviceSended();
                    $sendedBreed->setUser($this->getUser());
                    $sendedBreed->setGuid($identifier);
                    $sendedBreed->setType(UserDeviceSended::TYPE_DOG_BREED);
                }
                $breedReturn = DogSerialization::serializeBreedSqlite($breedIds, $manager, $sendedBreed);
                $return["sqlite"]["breeds"] = array();
                $return["sqlite"]["breeds"] = array_merge($return["sqlite"]["breeds"], $breedReturn);
                $manager->persist($sendedBreed);
                $manager->flush();
            }

            $type = $request->request->get('type', false);
            if ($type) {
                $requestType = new Request($_GET, $type, array(), $_COOKIE, $_FILES, $_SERVER);
                $sendedType = $repoSended->findOneBy(array("user" => $this->getUser(), "guid" => $identifier, "type" => UserDeviceSended::TYPE_DOG_TYPE));
                $reload = $requestType->get('reload', false);
                if (!$reload && !is_null($sendedType)) {
                    $typeIds = $sendedType->getIdsArray();
                } else {
                    $typeIds = array();
                }
                if (is_null($sendedType)) {
                    $sendedType = new UserDeviceSended();
                    $sendedType->setUser($this->getUser());
                    $sendedType->setGuid($identifier);
                    $sendedType->setType(UserDeviceSended::TYPE_DOG_TYPE);
                }
                $typeReturn = DogSerialization::serializeTypeSqlite($typeIds, $manager, $sendedType);
                $return["sqlite"]["types"] = array();
                $return["sqlite"]["types"] = array_merge($return["sqlite"]["types"], $typeReturn);
                $manager->persist($sendedType);
                $manager->flush();
            }

            $brand = $request->request->get('brand', false);
            if ($brand) {
                $requestBrand = new Request($_GET, $brand, array(), $_COOKIE, $_FILES, $_SERVER);
                $sendedBrand = $repoSended->findOneBy(array("user" => $this->getUser(), "guid" => $identifier, "type" => UserDeviceSended::TYPE_WEAPON_BRAND));
                $reload = $requestBrand->get('reload', false);
                if (!$reload && !is_null($sendedBrand)) {
                    $brandIds = $sendedBrand->getIdsArray();
                } else {
                    $brandIds = array();
                }
                if (is_null($sendedBrand)) {
                    $sendedBrand = new UserDeviceSended();
                    $sendedBrand->setUser($this->getUser());
                    $sendedBrand->setGuid($identifier);
                    $sendedBrand->setType(UserDeviceSended::TYPE_WEAPON_BRAND);
                }
                $brandReturn = WeaponSerialization::serializeBrandSqlite($brandIds, $manager, $sendedBrand);
                $return["sqlite"]["brands"] = array();
                $return["sqlite"]["brands"] = array_merge($return["sqlite"]["brands"], $brandReturn);
                $manager->persist($sendedBrand);
                $manager->flush();
            }

            $calibre = $request->request->get('calibre', false);
            if ($calibre) {
                $requestCalibre = new Request($_GET, $calibre, array(), $_COOKIE, $_FILES, $_SERVER);
                $sendedCalibre = $repoSended->findOneBy(array("user" => $this->getUser(), "guid" => $identifier, "type" => UserDeviceSended::TYPE_WEAPON_CALIBRE));
                $reload = $requestCalibre->get('reload', false);
                if (!$reload && !is_null($sendedCalibre)) {
                    $calibreIds = $sendedCalibre->getIdsArray();
                } else {
                    $calibreIds = array();
                }
                if (is_null($sendedCalibre)) {
                    $sendedCalibre = new UserDeviceSended();
                    $sendedCalibre->setUser($this->getUser());
                    $sendedCalibre->setGuid($identifier);
                    $sendedCalibre->setType(UserDeviceSended::TYPE_WEAPON_CALIBRE);
                }
                $calibreReturn = WeaponSerialization::serializeCalibreSqlite($calibreIds, $manager, $sendedCalibre);
                $return["sqlite"]["calibres"] = array();
                $return["sqlite"]["calibres"] = array_merge($return["sqlite"]["calibres"], $calibreReturn);
                $manager->persist($sendedCalibre);
                $manager->flush();
            }
        }


        return $this->view($return, Codes::HTTP_OK);
    }

    /**
     * get Sqlite of all points in map of the current user
     *
     * PUT /v2/user/sqliteex
     *
     * {
     *   "identifier": "e0366ffd02fdd942a960119e71fce654c2ccf5",
     *   "version":{
     *      "version":"20160222114711"
     *    },
     *   "publication":{
     *      "updated":"1450264747",
     *      "ids":"1,2,3,4"
     *    },
     *   "shape":{
     *      "updated":"1450264747"
     *    },
     *    "distributor":{
     *      "updated":""
     *    },
     *   "group":{
     *      "updated":"1450264747"
     *      "ids":"1,2,3,4"
     *    },
     *   "hunt":{
     *      "updated":"1450264747"
     *      "ids":"1,2,3,4"
     *    }
     *    "address":{
     *      "reload":[1: force reload, 0: don't force reload]
     *    },
     *   "favorite":{
     *      "updated":"1450264747"
     *      "ids":"1,2,3,4"
     *    },
     *    "color":{
     *      "reload":[1: force reload, 0: don't force reload]
     *    },
     *    "breed":{
     *      "reload":[1: force reload, 0: don't force reload]
     *    },
     *    "type":{
     *      "reload":[1: force reload, 0: don't force reload]
     *    },
     *    "brand":{
     *      "reload":[1: force reload, 0: don't force reload]
     *    },
     *    "calibre":{
     *      "reload":[1: force reload, 0: don't force reload]
     *    }
     * }
     *
     * INFO :   address doesn't need updated
     *          color doesn't need updated
     *          distributor doesn't need ids
     *          To get all elements, just send updated:"" (don't send ids) or just send ids and not updated just for "color" and "address"
     *
     * @param Request $request
     * @return \FOS\RestBundle\View\View
     */
    public function putUserSqliteexAction(Request $request)
    {
        $this->authorize();
        session_write_close();
        $manager = $this->getDoctrine()->getManager();
        $identifier = $request->request->get('identifier', false);
        // $limit = $request->request->get('limit', 500);
        // $offset = $request->request->get('offset', 0);
        $offset = 0;
        $return = array("sqlite" => array());
        if ($identifier) {
            $repoSended = $manager->getRepository("NaturaPassUserBundle:UserDeviceSended");
            $version = $request->request->get('version', false);
            $haveVersion = false;
            if ($version) {
                $requestVersion = new Request($_GET, $version, array(), $_COOKIE, $_FILES, $_SERVER);
                $lastVersion = $requestVersion->get('version', false);
                $qb = $manager->createQueryBuilder()->select('v')
                    ->from('NaturaPassUserBundle:DeviceDbVersion', 'v')
                    ->where('v.version > :version')
                    ->setParameter('version', $lastVersion)
                    ->getQuery();

                $paginators = new Paginator($qb, $fetchJoinCollection = true);
                $return["versions"] = array();
                foreach ($paginators as $version) {
                    $get = UserSerialization::serializeVersionSqlite($version);
                    if (!is_null($get)) {
                        $haveVersion = true;
                        $return["versions"][] = $get;
                    }
                }
            }

            $publication = $request->request->get('publication', false);
            if ($publication) {
                $requestPublication = new Request($_GET, $publication, array(), $_COOKIE, $_FILES, $_SERVER);
                $updated = $haveVersion ? $haveVersion : $requestPublication->get('updated', false);
                $updatedTime = $requestPublication->get('updated', 0);
                $dateTime = new \DateTime();
                $dateTime->setTimestamp($updatedTime);
                $updatedTime = $dateTime;
                $updatedTime = get_object_vars($updatedTime);
                $limit = $requestPublication->get('limit', 500);
                $reload = $requestPublication->get('reload', false);
                $ids = $requestPublication->get('ids', array());
                if ($ids == "") {
                    $ids = array();
                } elseif (!is_array($ids)) {
                    $ids = explode(",", $ids);
                }

                if (!$updated || $updated == "") {
                    $reload = true;
                }

                $sendedPub = $repoSended->findOneBy(array("user" => $this->getUser(), "guid" => $identifier, "type" => UserDeviceSended::TYPE_MAP));
                if (!$reload && !is_null($sendedPub)) {
                    $publicationIds = $sendedPub->getIdsArray();
                } else {
                    $publicationIds = array();
                }
                if (!is_array($publicationIds)) {
                    $publicationIds = explode(",", $publicationIds);
                }
                $publicationIds = array_merge($publicationIds, $ids);
                $filter = array(
                    'groups' => array(),
                    'hunts' => array(),
                    'sharing' => 3
                );
                $userGroups = $this->getUser()->getAllGroups();
                foreach ($userGroups as $group) {
                    $filter["groups"][] = $group->getId();
                }

                $userHunts = $this->getUser()->getAllHunts();
                foreach ($userHunts as $hunt) {
                    $filter["hunts"][] = $hunt->getId();
                }
                
                $qb = $this->getSharingQueryBuilder(
                    'NaturaPassPublicationBundle:Publication', 'p', $filter, 3
                );
                if($requestPublication->get('updated', 0) != 0){
                $qb->andWhere("p.updated >= '".$updatedTime["date"]."'");
                }
                $qb->andWhere("p.geolocation IS NOT NULL");
                $qb->orderBy('p.updated', 'ASC')
                   ->setFirstResult(0)
                   ->setMaxResults($limit)
                    ->getQuery();

                $return["sqlite"]["publications"] = array();
                $paginators = new Paginator($qb, $fetchJoinCollection = true);
                $arrayGoodIds = array();
                $arrayValues = array();
                $k = 0;

                foreach ($paginators as $publication) {
                    $k++;
                        $arrayGoodIds[] = $publication->getId();
                        $val = PublicationSerialization::serializePublicationSqliteInsertOrReplace($publicationIds, $updated, $publication, $this->getUser());
                        if (!is_null($val)) {
                            $arrayValues[] = $val;
                        }
                }
                //
                $manager = $this->getDoctrine()->getManager();
                $qb = $manager->createQueryBuilder()->select('gr')
                    ->from('NaturaPassGroupBundle:GroupUser', 'gr')
                    ->where('gr.refresh = 1')
                    ->andWhere('gr.user = '.$this->getUser()->getId());
                $results = $qb->getQuery()->getResult();
                foreach ($results as $groupUser){
                        $temp[] = $groupUser->getGroup()->getId();
//                        $setGr = $groupUser->getGroup();
                        $groupUser->setRefresh(0);
                        $manager->persist($groupUser);
                        $manager->flush();
                }
                if(count($temp)>0){
                    unset($filter["groups"]);
                    unset($filter["hunts"]);
                    $filter["groups"] = $temp;

                    $qb2 = $this->getSharingQueryBuilder(
                        'NaturaPassPublicationBundle:Publication', 'p', $filter, 3
                    );
                    $qb2->andWhere("p.geolocation IS NOT NULL");
                    $qb2->orderBy('p.updated', 'ASC')->getQuery();

                    $return["sqlite"]["temp"] = array();
                    $paginators2 = new Paginator($qb2, $fetchJoinCollection = true);
                    $arrayGoodIds2 = array();
                    $arrayValues2 = array();
                    $k2 = 0;

                    foreach ($paginators2 as $publication) {
                        $k2++;
                        $arrayGoodIds2[] = $publication->getId();
                        $val = PublicationSerialization::serializePublicationSqliteInsertOrReplace($publicationIds, $updated, $publication, $this->getUser());
                        if (!is_null($val)) {
                            $arrayValues2[] = $val;
                        }
                    }

                    if (!empty($arrayValues2)) {
                        $return["sqlite"]["temp"] = array_merge($return["sqlite"]["temp"], SqliteSerialization::serializeSqliteInserOrReplace("tb_carte", $arrayValues2));
                    }
                }
                //
                if (!empty($arrayValues)) {
                    $return["sqlite"]["publications"] = array_merge($return["sqlite"]["publications"], SqliteSerialization::serializeSqliteInserOrReplace("tb_carte", $arrayValues));
                    $return["sqlite"]["count_publications"] = $k;
                    if(count($return["sqlite"]["temp"])>0){
                        $return["sqlite"]["publications"] = array_merge($return["sqlite"]["publications"], $return["sqlite"]["temp"]);
                        $return["sqlite"]["count_publications"] = $k+$k2;
                    }
                }
                // get delete
                $pubDeleted = $manager->getRepository("NaturaPassPublicationBundle:PublicationDeleted");
                $qb = $pubDeleted->createQueryBuilder('d')
                    ->Where("d.geolocation IS NOT NULL")
                    ->andwhere("d.deleted >= '".$updatedTime["date"]."'")
                    ->getQuery();

                $dataDel = $qb->getArrayResult();

                $arrayDeleteId = array();

                foreach ($dataDel as $val) {
                    $arrayDeleteId[] = $val["id"];
                }
                // end delete
                if (count($arrayDeleteId)) {
                    $return["sqlite"]["publications"][] = "DELETE FROM `tb_carte` WHERE `c_id` IN (" . join(',', $arrayDeleteId) . ") AND `c_user_id` = '" . $this->getUser()->getId() . "';";
                    $return["sqlite"]["delete_publications"] = count($arrayDeleteId);
                }
                if (is_null($sendedPub)) {
                    $sendedPub = new UserDeviceSended();
                    $sendedPub->setUser($this->getUser());
                    $sendedPub->setGuid($identifier);
                    $sendedPub->setType(UserDeviceSended::TYPE_MAP);
                }
                $sendedPub->setIds(implode(",", $arrayGoodIds));
                $manager->persist($sendedPub);
                $manager->flush();
            }

            $shape = $request->request->get('shape', false);
            if ($shape) {
                $requestShape = new Request($_GET, $shape, array(), $_COOKIE, $_FILES, $_SERVER);
                $updated = $haveVersion ? $haveVersion : $requestShape->get('updated', $haveVersion);
                $reload = $requestShape->get('reload', false);
                if (!$updated || $updated == "") {
                    $reload = true;
                }
                $sendedShape = $repoSended->findOneBy(array("user" => $this->getUser(), "guid" => $identifier, "type" => UserDeviceSended::TYPE_SHAPE));
                if (!$reload && !is_null($sendedShape)) {
                    $sahpeIds = $sendedShape->getIdsArray();
                } else {
                    $sahpeIds = array();
                }
                if (!is_array($sahpeIds)) {
                    $sahpeIds = explode(",", $sahpeIds);
                }
                $filter = array(
                    'groups' => array(),
                    'hunts' => array(),
                    'sharing' => 3
                );
                $userGroups = $this->getUser()->getAllGroups();
                foreach ($userGroups as $group) {
                    $filter["groups"][] = $group->getId();
                }
                $userHunts = $this->getUser()->getAllHunts();
                foreach ($userHunts as $hunt) {
                    $filter["hunts"][] = $hunt->getId();
                }
                $qb = $this->getSharingQueryBuilder(
                    'NaturaPassMainBundle:Shape',
                    'p',
                    $filter,
                    3
                );
                $qb->orderBy('p.created', 'DESC')
                    ->getQuery();
                $return["sqlite"]["shapes"] = array();
                $paginators = new Paginator($qb, $fetchJoinCollection = true);
                $allIds = array();
                $arrayValues = array();
                foreach ($paginators as $shape) {
                    $allIds[] = $shape->getId();
                    if (is_null($shape->getLatCenter()) || is_null($shape->getLonCenter())) {
                        $shape->getCentre();
                        $manager->persist($shape);
                        $manager->flush();
                    }
                    $val = ShapeSerialization::serializeShapeSqliteInsertOrReplace($sahpeIds, $updated, $shape, $this->getUser());
                    if (!is_null($val)) {
                        $arrayValues[] = $val;
                    }
                }
                if (!empty($arrayValues)) {
                    $return["sqlite"]["shapes"] = array_merge($return["sqlite"]["shapes"], SqliteSerialization::serializeSqliteInserOrReplace("tb_shape", $arrayValues));
                }
                $arrayDeleteId = array();
                foreach ($sahpeIds as $shapeId) {
                    if (!in_array($shapeId, $allIds)) {
                        $arrayDeleteId[] = $shapeId;
                    }
                }
                if (count($arrayDeleteId)) {
                    $return["sqlite"]["shapes"][] = "DELETE FROM `tb_shape` WHERE `c_id` IN (" . join(',', $arrayDeleteId) . ") AND `c_user_id` = '" . $this->getUser()->getId() . "';";
                }
                if (is_null($sendedShape)) {
                    $sendedShape = new UserDeviceSended();
                    $sendedShape->setUser($this->getUser());
                    $sendedShape->setGuid($identifier);
                    $sendedShape->setType(UserDeviceSended::TYPE_SHAPE);
                }
                $sendedShape->setIds(implode(",", $allIds));
                $manager->persist($sendedShape);
                $manager->flush();
            }

            

            $distributor = $request->request->get('distributor', false);
            if ($distributor) {
                $requestDistributor = new Request($_GET, $distributor, array(), $_COOKIE, $_FILES, $_SERVER);
                $updated = $requestDistributor->get('updated', false);
                $limit = $requestDistributor->get('limit', 500);
                $updatedTime = $requestDistributor->get('updated', 0);
                $dateTime = new \DateTime();
                $dateTime->setTimestamp($updatedTime);
                $updatedTime = $dateTime;
                $updatedTime = get_object_vars($updatedTime);
                $return["sqlite"]["distributors"] = array();
                if (!$updated || $updated == "") {
                    $return["sqlite"]["distributors"][] = "DELETE FROM `tb_distributor`;";
                }
                $qb = $manager->createQueryBuilder()->select('d')
                    ->from('AdminDistributorBundle:Distributor', 'd');
                if($requestDistributor->get('updated', 0) != 0){
                $qb->Where("d.updated >= '".$updatedTime["date"]."'");
                }
                $qb->orderBy('d.updated', 'ASC')
                   ->setFirstResult($offset)
                   ->setMaxResults($limit)
                   ->getQuery();

                $paginators = new Paginator($qb, $fetchJoinCollection = true);
                $arrayValues = array();
                $k = 0;
                foreach ($paginators as $distributor) {
                    $k++;
                    $val = DistributorSerialization::serializeDistributorSqliteInsertOrReplace($updated, $distributor);
                    if (!is_null($val)) {
                        $arrayValues[] = $val;
                    }
                }
                if (!empty($arrayValues)) {
                    $return["sqlite"]["distributors"] = array_merge($return["sqlite"]["distributors"], SqliteSerialization::serializeSqliteInserOrReplace("tb_distributor", $arrayValues));
                    $return["sqlite"]["count_distributors"] = $k;
                }
            }

            $group = $request->request->get('group', false);
            if ($group) {
                $requestGroup = new Request($_GET, $group, array(), $_COOKIE, $_FILES, $_SERVER);
                $updated = $haveVersion ? $haveVersion : $requestGroup->get('updated', $haveVersion);
                $reload = $requestGroup->get('reload', false);
                $ids = $requestGroup->get('ids', array());
                if ($ids == "") {
                    $ids = array();
                } elseif (!is_array($ids)) {
                    $ids = explode(",", $ids);
                }
                if (!$updated || $updated == "") {
                    $reload = true;
                }
                $sendedGroup = $repoSended->findOneBy(array("user" => $this->getUser(), "guid" => $identifier, "type" => UserDeviceSended::TYPE_GROUP));
                if (!$reload && !is_null($sendedGroup)) {
                    $groupIds = $sendedGroup->getIdsArray();
                } else {
                    $groupIds = array();
                }
                $groupIds = array_merge($groupIds, $ids);
                if (is_null($sendedGroup)) {
                    $sendedGroup = new UserDeviceSended();
                    $sendedGroup->setUser($this->getUser());
                    $sendedGroup->setGuid($identifier);
                    $sendedGroup->setType(UserDeviceSended::TYPE_GROUP);
                }
                $groupReturn = GroupSerialization::serializeGroupSqlite($groupIds, $updated, $this->getUser(), $sendedGroup);
                $return["sqlite"]["groups"] = array();
                $return["sqlite"]["groups"] = array_merge($return["sqlite"]["groups"], $groupReturn);
                $manager->persist($sendedGroup);
                $manager->flush();
            }

            $hunt = $request->request->get('hunt', false);
            if ($hunt) {
                $requestHunt = new Request($_GET, $hunt, array(), $_COOKIE, $_FILES, $_SERVER);
                $updated = $haveVersion ? $haveVersion : $requestHunt->get('updated', $haveVersion);
                $reload = $requestHunt->get('reload', false);
                $ids = $requestHunt->get('ids', array());
                if ($ids == "") {
                    $ids = array();
                } elseif (!is_array($ids)) {
                    $ids = explode(",", $ids);
                }
                if (!$updated || $updated == "") {
                    $reload = true;
                }
                $sendedHunt = $repoSended->findOneBy(array("user" => $this->getUser(), "guid" => $identifier, "type" => UserDeviceSended::TYPE_HUNT));
                if (!$reload && !is_null($sendedHunt)) {
                    $huntIds = $sendedHunt->getIdsArray();
                } else {
                    $huntIds = array();
                }
                $huntIds = array_merge($huntIds, $ids);
                if (is_null($sendedHunt)) {
                    $sendedHunt = new UserDeviceSended();
                    $sendedHunt->setUser($this->getUser());
                    $sendedHunt->setGuid($identifier);
                    $sendedHunt->setType(UserDeviceSended::TYPE_HUNT);
                }
                $huntReturn = HuntSerialization::serializeHuntSqlite($huntIds, $updated, $this->getUser(), $sendedHunt);
                $return["sqlite"]["hunts"] = array();
                $return["sqlite"]["hunts"] = array_merge($return["sqlite"]["hunts"], $huntReturn);
                $manager->persist($sendedHunt);
                $manager->flush();
            }

            // start get all agenda
            $agenda = $request->request->get('agenda', false);
            if ($agenda == "true") {
                $manager = $this->getDoctrine()->getManager();
                $qb = $manager->createQueryBuilder()->select('l')
                    ->from('NaturaPassLoungeBundle:Lounge', 'l')
                    ->andWhere('l.endDate > :endDate')
                    ->setParameter('endDate', new \DateTime())
                    ->orderBy('l.meetingDate', 'ASC');
                $results = $qb->getQuery()->getResult();

                $lounges = array();
                foreach ($results as $result) {
                    $subscriber = $result->isSubscriber(
                        $this->getUser(), array(
                            LoungeUser::ACCESS_DEFAULT,
                            LoungeUser::ACCESS_ADMIN,
                            LoungeUser::ACCESS_INVITED,
                            LoungeUser::ACCESS_RESTRICTED
                        )
                    );
                    $add = false;
                    if (($result->getAccess() == Lounge::ACCESS_PROTECTED && $subscriber) || in_array(
                            $result->getAccess(), array(Lounge::ACCESS_PUBLIC, Lounge::ACCESS_SEMIPROTECTED)
                        )
                    ) {
                        $add = true;
                    }
                    
                    if ($add) {
                        $lounges[] = $result;
                    }
                }
                    $huntReturn = HuntSerialization::serializeAllHuntSqlite($lounges, $this->getUser());                    
                    $return["sqlite"]["agendas"] = array();
                    $return["sqlite"]["agendas"] = array_merge($return["sqlite"]["agendas"], $huntReturn);
            }

            // end get all agenda

            $favoriteAddress = $request->request->get('address', false);
            if ($favoriteAddress) {
                $requestAddress = new Request($_GET, $favoriteAddress, array(), $_COOKIE, $_FILES, $_SERVER);
                $sendedAddress = $repoSended->findOneBy(array("user" => $this->getUser(), "guid" => $identifier, "type" => UserDeviceSended::TYPE_ADDRESS));
                $reload = $requestAddress->get('reload', false);
                if (!$reload && !is_null($sendedAddress)) {
                    $addressIds = $sendedAddress->getIdsArray();
                } else {
                    $addressIds = array();
                }
                if (is_null($sendedAddress)) {
                    $sendedAddress = new UserDeviceSended();
                    $sendedAddress->setUser($this->getUser());
                    $sendedAddress->setGuid($identifier);
                    $sendedAddress->setType(UserDeviceSended::TYPE_ADDRESS);
                }
                $addressReturn = UserSerialization::serializeAddressSqlite($addressIds, $this->getUser(), $sendedAddress);
                $return["sqlite"]["addresss"] = array();
                $return["sqlite"]["addresss"] = array_merge($return["sqlite"]["addresss"], $addressReturn);
                $manager->persist($sendedAddress);
                $manager->flush();
            }

            $color = $request->request->get('color', false);
            if ($color) {
                $requestColor = new Request($_GET, $color, array(), $_COOKIE, $_FILES, $_SERVER);
                $sendedColor = $repoSended->findOneBy(array("user" => $this->getUser(), "guid" => $identifier, "type" => UserDeviceSended::TYPE_COLOR));
                $reload = $requestColor->get('reload', false);
                if (!$reload && !is_null($sendedColor)) {
                    $colorIds = $sendedColor->getIdsArray();
                } else {
                    $colorIds = array();
                }
                if (is_null($sendedColor)) {
                    $sendedColor = new UserDeviceSended();
                    $sendedColor->setUser($this->getUser());
                    $sendedColor->setGuid($identifier);
                    $sendedColor->setType(UserDeviceSended::TYPE_COLOR);
                }
                $colorReturn = PublicationSerialization::serializeColorSqlite($colorIds, $manager, $sendedColor);
                $return["sqlite"]["colors"] = array();
                $return["sqlite"]["colors"] = array_merge($return["sqlite"]["colors"], $colorReturn);
                $manager->persist($sendedColor);
                $manager->flush();
            }

            $favorite = $request->request->get('favorite', false);
            if ($favorite) {
                $requestFavorite = new Request($_GET, $favorite, array(), $_COOKIE, $_FILES, $_SERVER);
                $updated = $haveVersion ? $haveVersion : $requestFavorite->get('updated', $haveVersion);
                $reload = $requestFavorite->get('reload', false);
                $ids = $requestFavorite->get('ids', array());
                if ($ids == "") {
                    $ids = array();
                } elseif (!is_array($ids)) {
                    $ids = explode(",", $ids);
                }
                if (!$updated || $updated == "") {
                    $reload = true;
                }
                $sendedFavorite = $repoSended->findOneBy(array("user" => $this->getUser(), "guid" => $identifier, "type" => UserDeviceSended::TYPE_FAVORITE));
                if (!$reload && !is_null($sendedFavorite)) {
                    $favoriteIds = $sendedFavorite->getIdsArray();
                } else {
                    $favoriteIds = array();
                }
                $favoriteIds = array_merge($favoriteIds, $ids);
                if (is_null($sendedFavorite)) {
                    $sendedFavorite = new UserDeviceSended();
                    $sendedFavorite->setUser($this->getUser());
                    $sendedFavorite->setGuid($identifier);
                    $sendedFavorite->setType(UserDeviceSended::TYPE_FAVORITE);
                }
                $favReturn = UserSerialization::serializeFavoriteSqlite($favoriteIds, $updated, $this->getUser(), $sendedFavorite);
                $return["sqlite"]["favorites"] = array();
                $return["sqlite"]["favorites"] = array_merge($return["sqlite"]["favorites"], $favReturn);
                $manager->persist($sendedFavorite);
                $manager->flush();
            }

            $breed = $request->request->get('breed', false);
            if ($breed) {
                $requestBreed = new Request($_GET, $breed, array(), $_COOKIE, $_FILES, $_SERVER);
                $sendedBreed = $repoSended->findOneBy(array("user" => $this->getUser(), "guid" => $identifier, "type" => UserDeviceSended::TYPE_DOG_BREED));
                $reload = $requestBreed->get('reload', false);
                if (!$reload && !is_null($sendedBreed)) {
                    $breedIds = $sendedBreed->getIdsArray();
                } else {
                    $breedIds = array();
                }
                if (is_null($sendedBreed)) {
                    $sendedBreed = new UserDeviceSended();
                    $sendedBreed->setUser($this->getUser());
                    $sendedBreed->setGuid($identifier);
                    $sendedBreed->setType(UserDeviceSended::TYPE_DOG_BREED);
                }
                $breedReturn = DogSerialization::serializeBreedSqlite($breedIds, $manager, $sendedBreed);
                $return["sqlite"]["breeds"] = array();
                $return["sqlite"]["breeds"] = array_merge($return["sqlite"]["breeds"], $breedReturn);
                $manager->persist($sendedBreed);
                $manager->flush();
            }

            $type = $request->request->get('type', false);
            if ($type) {
                $requestType = new Request($_GET, $type, array(), $_COOKIE, $_FILES, $_SERVER);
                $sendedType = $repoSended->findOneBy(array("user" => $this->getUser(), "guid" => $identifier, "type" => UserDeviceSended::TYPE_DOG_TYPE));
                $reload = $requestType->get('reload', false);
                if (!$reload && !is_null($sendedType)) {
                    $typeIds = $sendedType->getIdsArray();
                } else {
                    $typeIds = array();
                }
                if (is_null($sendedType)) {
                    $sendedType = new UserDeviceSended();
                    $sendedType->setUser($this->getUser());
                    $sendedType->setGuid($identifier);
                    $sendedType->setType(UserDeviceSended::TYPE_DOG_TYPE);
                }
                $typeReturn = DogSerialization::serializeTypeSqlite($typeIds, $manager, $sendedType);
                $return["sqlite"]["types"] = array();
                $return["sqlite"]["types"] = array_merge($return["sqlite"]["types"], $typeReturn);
                $manager->persist($sendedType);
                $manager->flush();
            }

            $brand = $request->request->get('brand', false);
            if ($brand) {
                $requestBrand = new Request($_GET, $brand, array(), $_COOKIE, $_FILES, $_SERVER);
                $sendedBrand = $repoSended->findOneBy(array("user" => $this->getUser(), "guid" => $identifier, "type" => UserDeviceSended::TYPE_WEAPON_BRAND));
                $reload = $requestBrand->get('reload', false);
                if (!$reload && !is_null($sendedBrand)) {
                    $brandIds = $sendedBrand->getIdsArray();
                } else {
                    $brandIds = array();
                }
                if (is_null($sendedBrand)) {
                    $sendedBrand = new UserDeviceSended();
                    $sendedBrand->setUser($this->getUser());
                    $sendedBrand->setGuid($identifier);
                    $sendedBrand->setType(UserDeviceSended::TYPE_WEAPON_BRAND);
                }
                $brandReturn = WeaponSerialization::serializeBrandSqlite($brandIds, $manager, $sendedBrand);
                $return["sqlite"]["brands"] = array();
                $return["sqlite"]["brands"] = array_merge($return["sqlite"]["brands"], $brandReturn);
                $manager->persist($sendedBrand);
                $manager->flush();
            }

            $calibre = $request->request->get('calibre', false);
            if ($calibre) {
                $requestCalibre = new Request($_GET, $calibre, array(), $_COOKIE, $_FILES, $_SERVER);
                $sendedCalibre = $repoSended->findOneBy(array("user" => $this->getUser(), "guid" => $identifier, "type" => UserDeviceSended::TYPE_WEAPON_CALIBRE));
                $reload = $requestCalibre->get('reload', false);
                if (!$reload && !is_null($sendedCalibre)) {
                    $calibreIds = $sendedCalibre->getIdsArray();
                } else {
                    $calibreIds = array();
                }
                if (is_null($sendedCalibre)) {
                    $sendedCalibre = new UserDeviceSended();
                    $sendedCalibre->setUser($this->getUser());
                    $sendedCalibre->setGuid($identifier);
                    $sendedCalibre->setType(UserDeviceSended::TYPE_WEAPON_CALIBRE);
                }
                $calibreReturn = WeaponSerialization::serializeCalibreSqlite($calibreIds, $manager, $sendedCalibre);
                $return["sqlite"]["calibres"] = array();
                $return["sqlite"]["calibres"] = array_merge($return["sqlite"]["calibres"], $calibreReturn);
                $manager->persist($sendedCalibre);
                $manager->flush();
            }
        }

        return $this->view($return, Codes::HTTP_OK);
    }

    /**
     * get Sqlite of all points in map of the current user
     *
     * PUT /v2/dump/db
     *
     * {
     * }
     *
     * @param Request $request
     * @return \FOS\RestBundle\View\View
     */
    public function putDumpDbAction(Request $request)
    {
        

        $this->authorize();
        session_write_close();
        $manager = $this->getDoctrine()->getManager();

        // $limit = $request->request->get('limit', 500);
        // $offset = $request->request->get('offset', 0);
        $offset = 0;
        $return = array("sqlite" => array());

            $publication = $request->request->get('publication', false);
                $requestPublication = new Request($_GET, $publication, array(), $_COOKIE, $_FILES, $_SERVER);
                $limit = $requestPublication->get('limit', 10000);
                $publicationIds = array();
                $filter = array(
                    // 'groups' => array(),
                    // 'hunts' => array(),
                    'sharing' => 999
                );
                /*$userGroups = $this->getUser()->getAllGroups();
                foreach ($userGroups as $group) {
                    $filter["groups"][] = $group->getId();
                }
                $userHunts = $this->getUser()->getAllHunts();
                foreach ($userHunts as $hunt) {
                    $filter["hunts"][] = $hunt->getId();
                }*/
                $qb = $this->getSharingQueryBuilder(
                    'NaturaPassPublicationBundle:Publication', 'p', $filter, 3
                );
                /*if($requestPublication->get('updated', 0) != 0){
                $qb->andWhere("p.updated >= '".$updatedTime["date"]."'");
                }*/
                $qb->andWhere("p.geolocation IS NOT NULL");
                
                $qb->orderBy('p.updated', 'ASC')
                   ->setFirstResult(0)
                   ->setMaxResults($limit)
                    ->getQuery();

                $return["sqlite"]["publications"] = array();
                $paginators = new Paginator($qb, $fetchJoinCollection = true);
                $arrayGoodIds = array();
                $arrayValues = array();
                $k = 0;

                foreach ($paginators as $publication) {
                    $k++;
                        $arrayGoodIds[] = $publication->getId();
                        $val = PublicationSerialization::serializePublicationSqliteInsertOrReplace($publicationIds, $updated, $publication, $this->getUser());
                        if (!is_null($val)) {
                            $arrayValues[] = $val;
                        }
                }
                if (!empty($arrayValues)) {
                    $return["sqlite"]["publications"] = array_merge($return["sqlite"]["publications"], SqliteSerialization::serializeSqliteInserOrReplace("tb_carte", $arrayValues));
                    $return["sqlite"]["count_publications"] = $k;
                }
                // get delete
                $pubDeleted = $manager->getRepository("NaturaPassPublicationBundle:PublicationDeleted");
                $qb = $pubDeleted->createQueryBuilder('d')
                    ->Where("d.geolocation IS NOT NULL")
                    ->andwhere("d.deleted >= '".$updatedTime["date"]."'")
                    ->getQuery();

                $dataDel = $qb->getArrayResult();

                $arrayDeleteId = array();

                foreach ($dataDel as $val) {
                    $arrayDeleteId[] = $val["id"];
                }
                // end delete
                if (count($arrayDeleteId)) {
                    $return["sqlite"]["publications"][] = "DELETE FROM `tb_carte` WHERE `c_id` IN (" . join(',', $arrayDeleteId) . ") AND `c_user_id` = '" . $this->getUser()->getId() . "';";
                    $return["sqlite"]["delete_publications"] = count($arrayDeleteId);
                }

                $sendedPub = new LoadFlag();
                $sendedPub->setPub(implode(",", $arrayGoodIds));
                $manager->persist($sendedPub);
                $manager->flush();

                $fs = new \Symfony\Component\Filesystem\Filesystem();

                try {
                    $fs->dumpFile('db.json', $return['sqlite']['publications']);
                }
                catch(IOException $e) {
                }

        return $this->view($return, Codes::HTTP_OK);
    }

    
    public function putUserSqliteex2Action(Request $request)
    {
        $this->authorize();
        session_write_close();
        $manager = $this->getDoctrine()->getManager();
        $identifier = $request->request->get('identifier', false);
        $offset = 0;
        $return = array("sqlite" => array());
        if ($identifier) {
            $repoSended = $manager->getRepository("NaturaPassUserBundle:UserDeviceSended");
            $version = $request->request->get('version', false);
            $haveVersion = false;
            if ($version) {
                $requestVersion = new Request($_GET, $version, array(), $_COOKIE, $_FILES, $_SERVER);
                $lastVersion = $requestVersion->get('version', false);
                $qb = $manager->createQueryBuilder()->select('v')
                    ->from('NaturaPassUserBundle:DeviceDbVersion', 'v')
                    ->where('v.version > :version')
                    ->setParameter('version', $lastVersion)
                    ->getQuery();

                $paginators = new Paginator($qb, $fetchJoinCollection = true);
                $return["versions"] = array();
                foreach ($paginators as $version) {
                    $get = UserSerialization::serializeVersionSqlite($version);
                    if (!is_null($get)) {
                        $haveVersion = true;
                        $return["versions"][] = $get;
                    }
                }
            }

            $publication = $request->request->get('publication', false);
            if ($publication) {
                $requestPublication = new Request($_GET, $publication, array(), $_COOKIE, $_FILES, $_SERVER);
                $updated = $haveVersion ? $haveVersion : $requestPublication->get('updated', false);
                $limit = $requestPublication->get('limit', 500);
                $reload = $requestPublication->get('reload', false);
                $updatedTime = $requestPublication->get('updated', 0);
                $dateTime = new \DateTime();
                $dateTime->setTimestamp($updatedTime);
                $updatedTime = $dateTime;
                $updatedTime = get_object_vars($updatedTime);
                $ids = $requestPublication->get('ids', array());
                if ($ids == "") {
                    $ids = array();
                } elseif (!is_array($ids)) {
                    $ids = explode(",", $ids);
                }

                if (!$updated || $updated == "") {
                    $reload = true;
                }

                $sendedPub = $repoSended->findOneBy(array("user" => $this->getUser(), "guid" => $identifier, "type" => UserDeviceSended::TYPE_MAP));
                if (!$reload && !is_null($sendedPub)) {
                    $publicationIds = $sendedPub->getIdsArray();
                } else {
                    $publicationIds = array();
                }
                if (!is_array($publicationIds)) {
                    $publicationIds = explode(",", $publicationIds);
                }
                $publicationIds = array_merge($publicationIds, $ids);
                $filter = array(
                    'groups' => array(),
                    'hunts' => array(),
                    'sharing' => 3
                );
                $userGroups = $this->getUser()->getAllGroups();
                foreach ($userGroups as $group) {
                    $filter["groups"][] = $group->getId();
                }
                $userHunts = $this->getUser()->getAllHunts();
                foreach ($userHunts as $hunt) {
                    $filter["hunts"][] = $hunt->getId();
                }
                 
                // get load flag
                $manager = $this->getDoctrine()->getManager();
                $Flag = $manager->getRepository("NaturaPassUserBundle:LoadFlag");
                $qb = $Flag->createQueryBuilder('f')
                    ->Where("f.pub IS NOT NULL")
                    ->getQuery();

                $dataFlag = $qb->getArrayResult();
                $lastFlag = count($dataFlag) - 1;
                $loadedFlag = implode(",", $dataFlag[$lastFlag]);
                // end
                $qb = $this->getSharingQueryBuilder(
                    'NaturaPassPublicationBundle:Publication', 'p', $filter, 3
                );
                if($requestPublication->get('updated', 0) != 0){
                $qb->andWhere("p.updated >= '".$updatedTime["date"]."'");
                }
                $qb->andWhere("p.geolocation IS NOT NULL");

                if(count($dataFlag) > 0)
                {
                    $qb->andWhere($qb->expr()->notIn('p.id',  $loadedFlag));
                }

                $qb->orderBy('p.updated', 'ASC')
                   ->setFirstResult(0)
                   ->setMaxResults($limit)
                    ->getQuery();

                $return["sqlite"]["publications"] = array();
                $paginators = new Paginator($qb, $fetchJoinCollection = true);
                $arrayGoodIds = array();
                $arrayValues = array();
                $k = 0;

                foreach ($paginators as $publication) {
                    $k++;
                        $arrayGoodIds[] = $publication->getId();
                        $val = PublicationSerialization::serializePublicationSqliteInsertOrReplace($publicationIds, $updated, $publication, $this->getUser());
                        if (!is_null($val)) {
                            $arrayValues[] = $val;
                        }
                }
                if (!empty($arrayValues)) {
                    $return["sqlite"]["publications"] = array_merge($return["sqlite"]["publications"], SqliteSerialization::serializeSqliteInserOrReplace("tb_carte", $arrayValues));
                    $return["sqlite"]["count_publications"] = $k;
                }
                // get delete
                $pubDeleted = $manager->getRepository("NaturaPassPublicationBundle:PublicationDeleted");
                $qb = $pubDeleted->createQueryBuilder('d')
                    ->Where("d.geolocation IS NOT NULL")
                    ->andwhere("d.deleted >= '".$updatedTime["date"]."'")
                    ->getQuery();

                $dataDel = $qb->getArrayResult();

                $arrayDeleteId = array();

                foreach ($dataDel as $val) {
                    $arrayDeleteId[] = $val["id"];
                }
                // end delete
                if (count($arrayDeleteId)) {
                    $return["sqlite"]["publications"][] = "DELETE FROM `tb_carte` WHERE `c_id` IN (" . join(',', $arrayDeleteId) . ") AND `c_user_id` = '" . $this->getUser()->getId() . "';";
                    $return["sqlite"]["delete_publications"] = count($arrayDeleteId);
                }
                if (is_null($sendedPub)) {
                    $sendedPub = new UserDeviceSended();
                    $sendedPub->setUser($this->getUser());
                    $sendedPub->setGuid($identifier);
                    $sendedPub->setType(UserDeviceSended::TYPE_MAP);
                }
                $sendedPub->setIds(implode(",", $arrayGoodIds));
                $manager->persist($sendedPub);
                $manager->flush();
            }

            $shape = $request->request->get('shape', false);
            if ($shape) {
                $requestShape = new Request($_GET, $shape, array(), $_COOKIE, $_FILES, $_SERVER);
                $updated = $haveVersion ? $haveVersion : $requestShape->get('updated', $haveVersion);
                $reload = $requestShape->get('reload', false);
                if (!$updated || $updated == "") {
                    $reload = true;
                }
                $sendedShape = $repoSended->findOneBy(array("user" => $this->getUser(), "guid" => $identifier, "type" => UserDeviceSended::TYPE_SHAPE));
                if (!$reload && !is_null($sendedShape)) {
                    $sahpeIds = $sendedShape->getIdsArray();
                } else {
                    $sahpeIds = array();
                }
                if (!is_array($sahpeIds)) {
                    $sahpeIds = explode(",", $sahpeIds);
                }
                $filter = array(
                    'groups' => array(),
                    'hunts' => array(),
                    'sharing' => 3
                );
                $userGroups = $this->getUser()->getAllGroups();
                foreach ($userGroups as $group) {
                    $filter["groups"][] = $group->getId();
                }
                $userHunts = $this->getUser()->getAllHunts();
                foreach ($userHunts as $hunt) {
                    $filter["hunts"][] = $hunt->getId();
                }
                $qb = $this->getSharingQueryBuilder(
                    'NaturaPassMainBundle:Shape',
                    'p',
                    $filter,
                    3
                );
                $qb->orderBy('p.created', 'DESC')
                    ->getQuery();
                $return["sqlite"]["shapes"] = array();
                $paginators = new Paginator($qb, $fetchJoinCollection = true);
                $allIds = array();
                $arrayValues = array();
                foreach ($paginators as $shape) {
                    $allIds[] = $shape->getId();
                    if (is_null($shape->getLatCenter()) || is_null($shape->getLonCenter())) {
                        $shape->getCentre();
                        $manager->persist($shape);
                        $manager->flush();
                    }
                    $val = ShapeSerialization::serializeShapeSqliteInsertOrReplace($sahpeIds, $updated, $shape, $this->getUser());
                    if (!is_null($val)) {
                        $arrayValues[] = $val;
                    }
                }
                if (!empty($arrayValues)) {
                    $return["sqlite"]["shapes"] = array_merge($return["sqlite"]["shapes"], SqliteSerialization::serializeSqliteInserOrReplace("tb_shape", $arrayValues));
                }
                $arrayDeleteId = array();
                foreach ($sahpeIds as $shapeId) {
                    if (!in_array($shapeId, $allIds)) {
                        $arrayDeleteId[] = $shapeId;
                    }
                }
                if (count($arrayDeleteId)) {
                    $return["sqlite"]["shapes"][] = "DELETE FROM `tb_shape` WHERE `c_id` IN (" . join(',', $arrayDeleteId) . ") AND `c_user_id` = '" . $this->getUser()->getId() . "';";
                }
                if (is_null($sendedShape)) {
                    $sendedShape = new UserDeviceSended();
                    $sendedShape->setUser($this->getUser());
                    $sendedShape->setGuid($identifier);
                    $sendedShape->setType(UserDeviceSended::TYPE_SHAPE);
                }
                $sendedShape->setIds(implode(",", $allIds));
                $manager->persist($sendedShape);
                $manager->flush();
            }

            

            $distributor = $request->request->get('distributor', false);
            if ($distributor) {
                $requestDistributor = new Request($_GET, $distributor, array(), $_COOKIE, $_FILES, $_SERVER);
                $updated = $requestDistributor->get('updated', false);
                $limit = $requestDistributor->get('limit', 500);
                $updatedTime = $requestDistributor->get('updated', 0);
                $dateTime = new \DateTime();
                $dateTime->setTimestamp($updatedTime);
                $updatedTime = $dateTime;
                $updatedTime = get_object_vars($updatedTime);
                $return["sqlite"]["distributors"] = array();
                if (!$updated || $updated == "") {
                    $return["sqlite"]["distributors"][] = "DELETE FROM `tb_distributor`;";
                }
                $qb = $manager->createQueryBuilder()->select('d')
                    ->from('AdminDistributorBundle:Distributor', 'd');
                if($requestDistributor->get('updated', 0) != 0){
                $qb->Where("d.updated >= '".$updatedTime["date"]."'");
                }
                $qb->orderBy('d.updated', 'ASC')
                   ->setFirstResult($offset)
                   ->setMaxResults($limit)
                   ->getQuery();

                $paginators = new Paginator($qb, $fetchJoinCollection = true);
                $arrayValues = array();
                $k = 0;
                foreach ($paginators as $distributor) {
                    $k++;
                    $val = DistributorSerialization::serializeDistributorSqliteInsertOrReplace($updated, $distributor);
                    if (!is_null($val)) {
                        $arrayValues[] = $val;
                    }
                }
                if (!empty($arrayValues)) {
                    $return["sqlite"]["distributors"] = array_merge($return["sqlite"]["distributors"], SqliteSerialization::serializeSqliteInserOrReplace("tb_distributor", $arrayValues));
                    $return["sqlite"]["count_distributors"] = $k;
                }
            }

            $group = $request->request->get('group', false);
            if ($group) {
                $requestGroup = new Request($_GET, $group, array(), $_COOKIE, $_FILES, $_SERVER);
                $updated = $haveVersion ? $haveVersion : $requestGroup->get('updated', $haveVersion);
                $reload = $requestGroup->get('reload', false);
                $ids = $requestGroup->get('ids', array());
                if ($ids == "") {
                    $ids = array();
                } elseif (!is_array($ids)) {
                    $ids = explode(",", $ids);
                }
                if (!$updated || $updated == "") {
                    $reload = true;
                }
                $sendedGroup = $repoSended->findOneBy(array("user" => $this->getUser(), "guid" => $identifier, "type" => UserDeviceSended::TYPE_GROUP));
                if (!$reload && !is_null($sendedGroup)) {
                    $groupIds = $sendedGroup->getIdsArray();
                } else {
                    $groupIds = array();
                }
                $groupIds = array_merge($groupIds, $ids);
                if (is_null($sendedGroup)) {
                    $sendedGroup = new UserDeviceSended();
                    $sendedGroup->setUser($this->getUser());
                    $sendedGroup->setGuid($identifier);
                    $sendedGroup->setType(UserDeviceSended::TYPE_GROUP);
                }
                $groupReturn = GroupSerialization::serializeGroupSqlite($groupIds, $updated, $this->getUser(), $sendedGroup);
                $return["sqlite"]["groups"] = array();
                $return["sqlite"]["groups"] = array_merge($return["sqlite"]["groups"], $groupReturn);
                $manager->persist($sendedGroup);
                $manager->flush();
            }

            $hunt = $request->request->get('hunt', false);
            if ($hunt) {
                $requestHunt = new Request($_GET, $hunt, array(), $_COOKIE, $_FILES, $_SERVER);
                $updated = $haveVersion ? $haveVersion : $requestHunt->get('updated', $haveVersion);
                $reload = $requestHunt->get('reload', false);
                $ids = $requestHunt->get('ids', array());
                if ($ids == "") {
                    $ids = array();
                } elseif (!is_array($ids)) {
                    $ids = explode(",", $ids);
                }
                if (!$updated || $updated == "") {
                    $reload = true;
                }
                $sendedHunt = $repoSended->findOneBy(array("user" => $this->getUser(), "guid" => $identifier, "type" => UserDeviceSended::TYPE_HUNT));
                if (!$reload && !is_null($sendedHunt)) {
                    $huntIds = $sendedHunt->getIdsArray();
                } else {
                    $huntIds = array();
                }
                $huntIds = array_merge($huntIds, $ids);
                if (is_null($sendedHunt)) {
                    $sendedHunt = new UserDeviceSended();
                    $sendedHunt->setUser($this->getUser());
                    $sendedHunt->setGuid($identifier);
                    $sendedHunt->setType(UserDeviceSended::TYPE_HUNT);
                }
                $huntReturn = HuntSerialization::serializeHuntSqlite($huntIds, $updated, $this->getUser(), $sendedHunt);
                $return["sqlite"]["hunts"] = array();
                $return["sqlite"]["hunts"] = array_merge($return["sqlite"]["hunts"], $huntReturn);
                $manager->persist($sendedHunt);
                $manager->flush();
            }

            // start get all agenda
            $agenda = $request->request->get('agenda', false);
            if ($agenda == "true") {
                $manager = $this->getDoctrine()->getManager();
                $qb = $manager->createQueryBuilder()->select('l')
                    ->from('NaturaPassLoungeBundle:Lounge', 'l')
                    ->andWhere('l.endDate > :endDate')
                    ->setParameter('endDate', new \DateTime())
                    ->orderBy('l.meetingDate', 'ASC');
                $results = $qb->getQuery()->getResult();

                $lounges = array();
                foreach ($results as $result) {
                    $subscriber = $result->isSubscriber(
                        $this->getUser(), array(
                            LoungeUser::ACCESS_DEFAULT,
                            LoungeUser::ACCESS_ADMIN,
                            LoungeUser::ACCESS_INVITED,
                            LoungeUser::ACCESS_RESTRICTED
                        )
                    );
                    $add = false;
                    if (($result->getAccess() == Lounge::ACCESS_PROTECTED && $subscriber) || in_array(
                            $result->getAccess(), array(Lounge::ACCESS_PUBLIC, Lounge::ACCESS_SEMIPROTECTED)
                        )
                    ) {
                        $add = true;
                    }
                    
                    if ($add) {
                        $lounges[] = $result;
                    }
                }
                    $huntReturn = HuntSerialization::serializeAllHuntSqlite($lounges, $this->getUser());                    
                    $return["sqlite"]["agendas"] = array();
                    $return["sqlite"]["agendas"] = array_merge($return["sqlite"]["agendas"], $huntReturn);
            }

            // end get all agenda

            $favoriteAddress = $request->request->get('address', false);
            if ($favoriteAddress) {
                $requestAddress = new Request($_GET, $favoriteAddress, array(), $_COOKIE, $_FILES, $_SERVER);
                $sendedAddress = $repoSended->findOneBy(array("user" => $this->getUser(), "guid" => $identifier, "type" => UserDeviceSended::TYPE_ADDRESS));
                $reload = $requestAddress->get('reload', false);
                if (!$reload && !is_null($sendedAddress)) {
                    $addressIds = $sendedAddress->getIdsArray();
                } else {
                    $addressIds = array();
                }
                if (is_null($sendedAddress)) {
                    $sendedAddress = new UserDeviceSended();
                    $sendedAddress->setUser($this->getUser());
                    $sendedAddress->setGuid($identifier);
                    $sendedAddress->setType(UserDeviceSended::TYPE_ADDRESS);
                }
                $addressReturn = UserSerialization::serializeAddressSqlite($addressIds, $this->getUser(), $sendedAddress);
                $return["sqlite"]["addresss"] = array();
                $return["sqlite"]["addresss"] = array_merge($return["sqlite"]["addresss"], $addressReturn);
                $manager->persist($sendedAddress);
                $manager->flush();
            }

            $color = $request->request->get('color', false);
            if ($color) {
                $requestColor = new Request($_GET, $color, array(), $_COOKIE, $_FILES, $_SERVER);
                $sendedColor = $repoSended->findOneBy(array("user" => $this->getUser(), "guid" => $identifier, "type" => UserDeviceSended::TYPE_COLOR));
                $reload = $requestColor->get('reload', false);
                if (!$reload && !is_null($sendedColor)) {
                    $colorIds = $sendedColor->getIdsArray();
                } else {
                    $colorIds = array();
                }
                if (is_null($sendedColor)) {
                    $sendedColor = new UserDeviceSended();
                    $sendedColor->setUser($this->getUser());
                    $sendedColor->setGuid($identifier);
                    $sendedColor->setType(UserDeviceSended::TYPE_COLOR);
                }
                $colorReturn = PublicationSerialization::serializeColorSqlite($colorIds, $manager, $sendedColor);
                $return["sqlite"]["colors"] = array();
                $return["sqlite"]["colors"] = array_merge($return["sqlite"]["colors"], $colorReturn);
                $manager->persist($sendedColor);
                $manager->flush();
            }

            $favorite = $request->request->get('favorite', false);
            if ($favorite) {
                $requestFavorite = new Request($_GET, $favorite, array(), $_COOKIE, $_FILES, $_SERVER);
                $updated = $haveVersion ? $haveVersion : $requestFavorite->get('updated', $haveVersion);
                $reload = $requestFavorite->get('reload', false);
                $ids = $requestFavorite->get('ids', array());
                if ($ids == "") {
                    $ids = array();
                } elseif (!is_array($ids)) {
                    $ids = explode(",", $ids);
                }
                if (!$updated || $updated == "") {
                    $reload = true;
                }
                $sendedFavorite = $repoSended->findOneBy(array("user" => $this->getUser(), "guid" => $identifier, "type" => UserDeviceSended::TYPE_FAVORITE));
                if (!$reload && !is_null($sendedFavorite)) {
                    $favoriteIds = $sendedFavorite->getIdsArray();
                } else {
                    $favoriteIds = array();
                }
                $favoriteIds = array_merge($favoriteIds, $ids);
                if (is_null($sendedFavorite)) {
                    $sendedFavorite = new UserDeviceSended();
                    $sendedFavorite->setUser($this->getUser());
                    $sendedFavorite->setGuid($identifier);
                    $sendedFavorite->setType(UserDeviceSended::TYPE_FAVORITE);
                }
                $favReturn = UserSerialization::serializeFavoriteSqlite($favoriteIds, $updated, $this->getUser(), $sendedFavorite);
                $return["sqlite"]["favorites"] = array();
                $return["sqlite"]["favorites"] = array_merge($return["sqlite"]["favorites"], $favReturn);
                $manager->persist($sendedFavorite);
                $manager->flush();
            }

            $breed = $request->request->get('breed', false);
            if ($breed) {
                $requestBreed = new Request($_GET, $breed, array(), $_COOKIE, $_FILES, $_SERVER);
                $sendedBreed = $repoSended->findOneBy(array("user" => $this->getUser(), "guid" => $identifier, "type" => UserDeviceSended::TYPE_DOG_BREED));
                $reload = $requestBreed->get('reload', false);
                if (!$reload && !is_null($sendedBreed)) {
                    $breedIds = $sendedBreed->getIdsArray();
                } else {
                    $breedIds = array();
                }
                if (is_null($sendedBreed)) {
                    $sendedBreed = new UserDeviceSended();
                    $sendedBreed->setUser($this->getUser());
                    $sendedBreed->setGuid($identifier);
                    $sendedBreed->setType(UserDeviceSended::TYPE_DOG_BREED);
                }
                $breedReturn = DogSerialization::serializeBreedSqlite($breedIds, $manager, $sendedBreed);
                $return["sqlite"]["breeds"] = array();
                $return["sqlite"]["breeds"] = array_merge($return["sqlite"]["breeds"], $breedReturn);
                $manager->persist($sendedBreed);
                $manager->flush();
            }

            $type = $request->request->get('type', false);
            if ($type) {
                $requestType = new Request($_GET, $type, array(), $_COOKIE, $_FILES, $_SERVER);
                $sendedType = $repoSended->findOneBy(array("user" => $this->getUser(), "guid" => $identifier, "type" => UserDeviceSended::TYPE_DOG_TYPE));
                $reload = $requestType->get('reload', false);
                if (!$reload && !is_null($sendedType)) {
                    $typeIds = $sendedType->getIdsArray();
                } else {
                    $typeIds = array();
                }
                if (is_null($sendedType)) {
                    $sendedType = new UserDeviceSended();
                    $sendedType->setUser($this->getUser());
                    $sendedType->setGuid($identifier);
                    $sendedType->setType(UserDeviceSended::TYPE_DOG_TYPE);
                }
                $typeReturn = DogSerialization::serializeTypeSqlite($typeIds, $manager, $sendedType);
                $return["sqlite"]["types"] = array();
                $return["sqlite"]["types"] = array_merge($return["sqlite"]["types"], $typeReturn);
                $manager->persist($sendedType);
                $manager->flush();
            }

            $brand = $request->request->get('brand', false);
            if ($brand) {
                $requestBrand = new Request($_GET, $brand, array(), $_COOKIE, $_FILES, $_SERVER);
                $sendedBrand = $repoSended->findOneBy(array("user" => $this->getUser(), "guid" => $identifier, "type" => UserDeviceSended::TYPE_WEAPON_BRAND));
                $reload = $requestBrand->get('reload', false);
                if (!$reload && !is_null($sendedBrand)) {
                    $brandIds = $sendedBrand->getIdsArray();
                } else {
                    $brandIds = array();
                }
                if (is_null($sendedBrand)) {
                    $sendedBrand = new UserDeviceSended();
                    $sendedBrand->setUser($this->getUser());
                    $sendedBrand->setGuid($identifier);
                    $sendedBrand->setType(UserDeviceSended::TYPE_WEAPON_BRAND);
                }
                $brandReturn = WeaponSerialization::serializeBrandSqlite($brandIds, $manager, $sendedBrand);
                $return["sqlite"]["brands"] = array();
                $return["sqlite"]["brands"] = array_merge($return["sqlite"]["brands"], $brandReturn);
                $manager->persist($sendedBrand);
                $manager->flush();
            }

            $calibre = $request->request->get('calibre', false);
            if ($calibre) {
                $requestCalibre = new Request($_GET, $calibre, array(), $_COOKIE, $_FILES, $_SERVER);
                $sendedCalibre = $repoSended->findOneBy(array("user" => $this->getUser(), "guid" => $identifier, "type" => UserDeviceSended::TYPE_WEAPON_CALIBRE));
                $reload = $requestCalibre->get('reload', false);
                if (!$reload && !is_null($sendedCalibre)) {
                    $calibreIds = $sendedCalibre->getIdsArray();
                } else {
                    $calibreIds = array();
                }
                if (is_null($sendedCalibre)) {
                    $sendedCalibre = new UserDeviceSended();
                    $sendedCalibre->setUser($this->getUser());
                    $sendedCalibre->setGuid($identifier);
                    $sendedCalibre->setType(UserDeviceSended::TYPE_WEAPON_CALIBRE);
                }
                $calibreReturn = WeaponSerialization::serializeCalibreSqlite($calibreIds, $manager, $sendedCalibre);
                $return["sqlite"]["calibres"] = array();
                $return["sqlite"]["calibres"] = array_merge($return["sqlite"]["calibres"], $calibreReturn);
                $manager->persist($sendedCalibre);
                $manager->flush();
            }
        }

        return $this->view($return, Codes::HTTP_OK);
    }


    /**
     * Check available user for website
     *
     * @param Request $request
     * @return \FOS\RestBundle\View\View
     *
     */
    public function postCheckUserLoginAction(Request $request)
    {
        $manager = $this->getDoctrine()->getManager();
        $encoder = $this->get('naturapass_sha1salted.encoder');
        $params = array(
                'email' => urldecode($request->request->get('_username')),
                'password' => $encoder->encodePassword($request->request->get('_password'), $encoder::SALT)
            );
        $params2 = array(
                'email' => urldecode($request->request->get('_username'))
            );
        $user = $manager->getRepository('NaturaPassUserBundle:User')->findOneBy($params);
        if (isset($params2)) {
            $user2 = $manager->getRepository('NaturaPassUserBundle:User')->findOneBy($params2);
        }
        if (!$user instanceof User) {
            if ((isset($params2) && !$user2 instanceof User) || !isset($params2)) {
                $request->getSession()
                    ->getFlashBag()
                    ->add('error', $this->message('errors.user.nonexistent'));
                throw new HttpException(Codes::HTTP_NOT_FOUND, $this->message('errors.user.nonexistent'));
            } else {
                $request->getSession()
                    ->getFlashBag()
                    ->add('error', $this->message('errors.user.passwordnonexistent'));
                throw new HttpException(Codes::HTTP_NOT_FOUND, $this->message('errors.user.passwordnonexistent'));
            }
        }
        $request->getSession()->getFlashBag()->clear();
        return $this->view(array('success' => true), Codes::HTTP_OK);
    }
}