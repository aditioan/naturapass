<?php

namespace Api\ApiBundle\Controller\v1;

use Api\ApiBundle\Controller\v2\Serialization\HuntSerialization;
use Api\ApiBundle\Controller\v2\Serialization\SqliteSerialization;
use Doctrine\Common\Collections\ArrayCollection;
use FOS\RestBundle\Controller\Annotations\View;
use FOS\RestBundle\Util\Codes;
use NaturaPass\GraphBundle\Entity\Edge;
use NaturaPass\LoungeBundle\Entity\LoungeMedia;
use NaturaPass\MainBundle\Component\Security\SecurityUtilities;
use NaturaPass\NotificationBundle\Entity\Lounge\LoungeGeolocationNotification;
use NaturaPass\NotificationBundle\Entity\Lounge\LoungeJoinAcceptedNotification;
use NaturaPass\NotificationBundle\Entity\Lounge\LoungeJoinInvitedNotification;
use NaturaPass\NotificationBundle\Entity\Lounge\LoungeJoinValidationAskedNotification;
use NaturaPass\NotificationBundle\Entity\Lounge\LoungeMessageNotification;
use NaturaPass\NotificationBundle\Entity\Lounge\LoungeStatusAdminNotification;
use NaturaPass\NotificationBundle\Entity\Lounge\LoungeStatusChangedNotification;
use NaturaPass\NotificationBundle\Entity\Lounge\SocketOnly\LoungeNotUserAddNotification;
use NaturaPass\NotificationBundle\Entity\Lounge\SocketOnly\LoungeNotUserParticipationNotification;
use NaturaPass\NotificationBundle\Entity\Lounge\SocketOnly\LoungeNotUserRemoveNotification;
use NaturaPass\NotificationBundle\Entity\Lounge\SocketOnly\LoungeSubscriberQuietNotification;
use NaturaPass\NotificationBundle\Entity\Lounge\SocketOnly\LoungeSubscriberAdminNotification;
use NaturaPass\NotificationBundle\Entity\Lounge\SocketOnly\LoungeSubscriberParticipationNotification;
use NaturaPass\NotificationBundle\Entity\Lounge\SocketOnly\LoungeSubscriberRemoveNotification;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use NaturaPass\LoungeBundle\Entity\Lounge;
use NaturaPass\LoungeBundle\Entity\LoungeUser;
use NaturaPass\LoungeBundle\Entity\LoungeNotMember;
use NaturaPass\LoungeBundle\Entity\LoungeInvitation;
use NaturaPass\UserBundle\Entity\Invitation;
use NaturaPass\LoungeBundle\Entity\LoungeMessage;
use NaturaPass\LoungeBundle\Form\Type\LoungeType;
use NaturaPass\LoungeBundle\Form\Handler\LoungeHandler;
use NaturaPass\UserBundle\Entity\User;
use NaturaPass\UserBundle\Entity\UserMap;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use NaturaPass\GroupBundle\Entity\Group;
use NaturaPass\UserBundle\Entity\ParametersNotification;

/**
 * Description of LoungesController
 *
 * @author vincentvalot
 */
class LoungesController extends ApiRestController
{

    /**z
     * Retourne tous les salons de la base de données
     *
     * GET /lounges?limit=10&offset=0&filter=test
     *
     * @param Request $request
     *
     * @return \FOS\RestBundle\View\View
     *
     * @View(serializerGroups={"LoungeLess", "UserLess"})
     *
     */
    public function getLoungesAction(Request $request)
    {
        $this->authorize();
        $limit = $request->query->get('limit', 10);
        $offset = $request->query->get('offset', 0);
        $filter = $request->query->get('filter', false);
        if ($filter != false) {
            $filter = urldecode($filter);
        }
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
            if ($filter != false && $filter != '' && $add) {
                if (preg_match('/' . strtolower($filter) . '/', strtolower($result->getName()))) {
                    $add = true;
                } else {
                    $add = false;
                }
            }
            if ($add) {
                $lounge = $this->getFormatLounge($result);
                $lounges[] = $lounge;
            }
        }

        return $this->view(array('lounges' => array_slice($lounges, $offset, $limit)), Codes::HTTP_OK);
    }

    /**
     * Retourne tous les salons auquel l'utilisateur est connecté (membre, admin)
     *
     * GET /lounges/owning?limit=10&offset=0&filter=test
     *
     * @param Request $request
     * @return \FOS\RestBundle\View\View
     *
     * @View(serializerGroups={"GroupUserLess", "UserLess"})
     */
    public function getLoungesOwningAction(Request $request)
    {
        $this->authorize();
        $limit = $request->query->get('limit', 10);
        $offset = $request->query->get('offset', 0);
        $filter = $request->query->get('filter', false);
        if ($filter != false) {
            $filter = urldecode($filter);
        }
        $results = $this->getUser()->getLounges(array(LoungeUser::ACCESS_DEFAULT, LoungeUser::ACCESS_ADMIN));
        $lounges = array();
        foreach ($results as $lounge) {
            $add = true;
            if ($filter != false && $filter != '') {
                if (preg_match('/' . strtolower($filter) . '/', strtolower($lounge->getName()))) {
                    $add = true;
                } else {
                    $add = false;
                }
            }
            if ($add) {
                $lounges[] = $this->getFormatLounge($lounge);
            }
        }

        return $this->view(array('lounges' => array_slice($lounges, $offset, $limit)), Codes::HTTP_OK);
    }

    /**
     * Retourne tous les salons auquel l'utilisateur est connecté (membre, admin)
     *
     * GET /lounges/old/owning?limit=10&offset=0&filter=test
     *
     * @param Request $request
     * @return \FOS\RestBundle\View\View
     *
     * @View(serializerGroups={"GroupUserLess", "UserLess"})
     */
    public function getLoungesOldOwningAction(Request $request)
    {
        $this->authorize();
        $limit = $request->query->get('limit', 10);
        $offset = $request->query->get('offset', 0);
        $filter = $request->query->get('filter', false);
        if ($filter != false) {
            $filter = urldecode($filter);
        }
        $results = $this->getUser()->getOldLounges(array(LoungeUser::ACCESS_DEFAULT, LoungeUser::ACCESS_ADMIN));
        $lounges = array();
        foreach ($results as $lounge) {
            $add = true;
            if ($filter != false && $filter != '') {
                if (preg_match('/' . strtolower($filter) . '/', strtolower($lounge->getName()))) {
                    $add = true;
                } else {
                    $add = false;
                }
            }
            if ($add) {
                $lounges[] = $this->getFormatLounge($lounge);
            }
        }

        return $this->view(array('lounges' => array_slice($lounges, $offset, $limit)), Codes::HTTP_OK);
    }

    /**
     * Retourne tous les salons qui sont en attente de validation
     *
     * GET /lounges/pending?limit=10&offset=0&filter=test
     *
     * @param Request $request
     * @return \FOS\RestBundle\View\View
     *
     * @View(serializerGroups={"GroupUserLess"})
     */
    public function getLoungesPendingAction(Request $request)
    {
        $this->authorize();
        $limit = $request->query->get('limit', 10);
        $offset = $request->query->get('offset', 0);
        $filter = $request->query->get('filter', false);
        if ($filter != false) {
            $filter = urldecode($filter);
        }
        $results = $this->getUser()->getLounges(array(LoungeUser::ACCESS_RESTRICTED, LoungeUser::ACCESS_INVITED));
        $lounges = array();
        foreach ($results as $lounge) {
            $add = true;
            if ($filter != false && $filter != '') {
                if (preg_match('/' . strtolower($filter) . '/', strtolower($lounge->getName()))) {
                    $add = true;
                } else {
                    $add = false;
                }
            }
            if ($add) {
                $lounges[] = $this->getFormatLounge($lounge);
            }
        }

        return $this->view(array('lounges' => array_slice($lounges, $offset, $limit)), Codes::HTTP_OK);
    }

    /**
     * FR : Retourne tous les salons qui sont en attente de validation
     * EN : Returns all the lounges that are awaiting approval
     *
     * GET /lounges/{LOUNGE_ID}/pending/users
     *
     * @return \FOS\RestBundle\View\View
     *
     * @param \NaturaPass\LoungeBundle\Entity\Lounge $lounge
     * @return array
     *
     * @View(serializerGroups={"GroupUserLess"})
     *
     * @ParamConverter("lounge", class="NaturaPassLoungeBundle:Lounge")
     *
     */
    public function getLoungesPendingUsersAction(Lounge $lounge)
    {
        $this->authorize();
        $suscibers = $lounge->getSubscribers(array(loungeUser::ACCESS_RESTRICTED, loungeUser::ACCESS_INVITED));
        $users = array();
        foreach ($suscibers as $susciber) {
            $users[] = $this->getFormatLoungeSubscriber($susciber);
        }

        return $this->view(array('users' => $users), Codes::HTTP_OK);
    }

    /**
     * Retourne tous les salons de la base de données ou l'utilisateur a été invité
     *
     * GET /lounges/invited
     *
     * @return array
     *
     * @View(serializerGroups={"LoungeLess", "UserLess"})
     *
     */
    public function getLoungesInvitedAction()
    {
        $this->authorize();
        $lounges = $this->getUser()->getLounges(array(LoungeUser::ACCESS_INVITED));
        $views = array();
        $array = array();
        foreach ($lounges as $lounge) {
            $view = array(
                'lounge' => $lounge,
                'photo' => $this->getBaseUrl() . ($lounge->getPhoto() ? $lounge->getPhoto()->getResize() : $this->getAssetHelper()->getUrl('/img/interface/default-media.jpg'))
            );
            array_push($array, $view);
        }
        $views['lounges'] = $array;

        return $this->view($views, Codes::HTTP_OK);
    }

    /**
     * Retourne les données d'un salon
     *
     * GET /lounges/{lounge}
     *
     * @param \NaturaPass\LoungeBundle\Entity\Lounge $lounge
     * @return array
     *
     * @View(serializerGroups={"LoungeDetail", "LoungePhoto", "MediaLess", "UserLess"})
     *
     * @ParamConverter("lounge", class="NaturaPassLoungeBundle:Lounge")
     */
    public function getLoungeAction(Lounge $lounge)
    {
        return $this->view(array('lounge' => $this->getFormatLounge($lounge)), Codes::HTTP_OK);
    }

    /**
     * Retourne les utilisateurs d'un salon
     *
     * GET /lounges/{lounge}/subscribers
     *
     * @param \NaturaPass\LoungeBundle\Entity\Lounge $lounge
     * @param Request $request
     *
     * @View(serializerGroups={"LoungeSubscribers", "LoungeUserLess", "UserLess"})
     * @ParamConverter("lounge", class="NaturaPassLoungeBundle:Lounge")
     *
     * @return \FOS\RestBundle\View\View
     *
     * @throws HttpException
     */
    public function getLoungeSubscribersAction(Lounge $lounge, Request $request)
    {
        $this->authorize();
        if ($lounge->getAccess() == Group::ACCESS_PROTECTED && !$lounge->isSubscriber(
                $this->getUser(), array(
                    LoungeUser::ACCESS_INVITED,
                    LoungeUser::ACCESS_DEFAULT,
                    LoungeUser::ACCESS_ADMIN,
                    LoungeUser::ACCESS_RESTRICTED
                )
            )
        ) {
            throw new HttpException(Codes::HTTP_FORBIDDEN, $this->message('codes.403'));
        }
        $accesses = array(LoungeUser::ACCESS_DEFAULT, LoungeUser::ACCESS_ADMIN);
        if ($request->query->get('all', false)) {
            $accesses[] = LoungeUser::ACCESS_INVITED;
            $accesses[] = LoungeUser::ACCESS_RESTRICTED;
        }
        $array = array();
        foreach ($lounge->getSubscribers($accesses) as $subscriber) {
            $array[] = $this->getFormatLoungeSubscriber($subscriber);
        }

        return $this->view(array('subscribers' => $array), Codes::HTTP_OK);
    }

    /**
     * Retourne les membre d'un salon ainsi que mes amis
     *
     * GET /lounges/{lounge}/subscribers/friend?all=1
     *
     * @param \NaturaPass\LoungeBundle\Entity\Lounge $lounge
     * @param Request $request
     *
     * @View(serializerGroups={"LoungeSubscribers", "LoungeUserLess", "UserLess"})
     * @ParamConverter("lounge", class="NaturaPassLoungeBundle:Lounge")
     *
     * @return \FOS\RestBundle\View\View
     *
     * @throws HttpException
     */
    public function getLoungeSubscribersFriendAction(Lounge $lounge, Request $request)
    {
        $this->authorize();
        if ($lounge->getAccess() == Group::ACCESS_PROTECTED && !$lounge->isSubscriber(
                $this->getUser(), array(
                    LoungeUser::ACCESS_INVITED,
                    LoungeUser::ACCESS_DEFAULT,
                    LoungeUser::ACCESS_ADMIN,
                    LoungeUser::ACCESS_RESTRICTED
                )
            )
        ) {
            throw new HttpException(Codes::HTTP_FORBIDDEN, $this->message('codes.403'));
        }
        $accesses = array(LoungeUser::ACCESS_DEFAULT, LoungeUser::ACCESS_ADMIN);
        if ($request->query->get('all', false)) {
            $accesses[] = LoungeUser::ACCESS_INVITED;
            $accesses[] = LoungeUser::ACCESS_RESTRICTED;
        }
        $array = array();
        foreach ($lounge->getSubscribers($accesses) as $subscriber) {
            $array[] = $this->getFormatLoungeSubscriberFriend($subscriber);
        }

        $friends = $this->getUser()->getFriends();
        $arrayFriend = array();
        foreach ($friends as $friend) {
            $arrayFriend[] = $this->getFormatUser($friend, true);
        }

        return $this->view(array('subscribers' => $array, 'myfriends' => $arrayFriend), Codes::HTTP_OK);
    }

    /**
     * Retourne les non-membres d'un salon
     *
     * GET /lounges/{lounge}/subscribersnotmember
     *
     * @param \NaturaPass\LoungeBundle\Entity\Lounge $lounge
     *
     * @View(serializerGroups={"LoungeSubscribers", "LoungeUserLess", "UserLess"})
     * @ParamConverter("lounge", class="NaturaPassLoungeBundle:Lounge")
     *
     * @return \FOS\RestBundle\View\View
     *
     * @throws HttpException
     */
    public function getLoungeSubscribersnotmemberAction(Lounge $lounge)
    {
        $this->authorize();
        if ($lounge->getAccess() == Group::ACCESS_PROTECTED && !$lounge->isSubscriber(
                $this->getUser(), array(
                    LoungeUser::ACCESS_INVITED,
                    LoungeUser::ACCESS_DEFAULT,
                    LoungeUser::ACCESS_ADMIN,
                    LoungeUser::ACCESS_RESTRICTED
                )
            )
        ) {
            throw new HttpException(Codes::HTTP_FORBIDDEN, $this->message('codes.403'));
        }

        $array = array();
        foreach ($lounge->getSubscribersNotMember() as $subscriberNotMember) {
            $array[] = $this->getFormatLoungeSubscriberNotMember($subscriberNotMember);
        }

        return $this->view(array('subscribersNotMember' => $array), Codes::HTTP_OK);
    }

    /**
     * Retourne les demandes d'accès pour un salon
     *
     * GET /lounges/{lounge}/asks
     *
     * @param \NaturaPass\LoungeBundle\Entity\Lounge $lounge
     *
     * @View(serializerGroups={"LoungeSubscribers", "LoungeUserLess", "UserLess"})
     *
     * @ParamConverter("lounge", class="NaturaPassLoungeBundle:Lounge")
     *
     * @return \FOS\RestBundle\View\View
     */
    public function getLoungeAsksAction(Lounge $lounge)
    {
        $array = array();
        foreach ($lounge->getSubscribers(array(LoungeUser::ACCESS_RESTRICTED)) as $loungeSuscribers) {
            $user = $loungeSuscribers->getUser();
            $array[] = array(
                'user' => array(
                    'id' => $user->getId(),
                    'fullname' => $user->getFullName(),
                    'firstname' => $user->getFirstName(),
                    'lastname' => $user->getLastName(),
                    'usertag' => $user->getUsertag(),
                    'profilepicture' => $user->getProfilePicture() ? $user->getProfilePicture()->getThumb() : $this->getAssetHelper()->getUrl('img/interface/default-avatar.jpg')
                ),
                'access' => $loungeSuscribers->getAccess(),
                'geolocation' => $loungeSuscribers->getGeolocation(),
                'participation' => $loungeSuscribers->getParticipation(),
                'created' => $loungeSuscribers->getCreated()->format(\DateTime::ATOM),
                'updated' => $loungeSuscribers->getUpdated()->format(\DateTime::ATOM)
            );
        }

        return $this->view(array('subscribers' => $array), Codes::HTTP_OK);
    }

    /**
     * Retourne les dernières coordonnées de géolocalisation des utilisateurs d'un salon
     *
     * GET /lounges/{lounge}/subscribers/geolocations?identifier=e0366ffd02fdd942a960119e71fce654c2ccf5
     *
     * @param \NaturaPass\LoungeBundle\Entity\Lounge $lounge
     * @param Request $request
     *
     * @View(serializerGroups={"UserMap", "MapLess", "UserLess", "GeolocationLess"})
     * @ParamConverter("lounge", class="NaturaPassLoungeBundle:Lounge")
     *
     * @return \FOS\RestBundle\View\View
     */
    public function getLoungeSubscribersGeolocationsAction(Lounge $lounge, Request $request)
    {
        $this->authorize();
        $manager = $this->getDoctrine()->getManager();
        $identifier = $request->query->get('identifier', false);
        if ($identifier) {
            $device = $this->getDoctrine()->getRepository('NaturaPassUserBundle:Device')->findOneBy(
                array('identifier' => $identifier)
            );
            $devices = array(
                $this->getDoctrine()->getRepository('NaturaPassUserBundle:UserDevice')->findOneBy(
                    array('owner' => $this->getUser(), 'device' => $device)
                )
            );
        } else {
            $devices = $this->getUser()->getDevices();
        }
        foreach ($devices as $device) {
            $issetMap = false;
            $qb = $manager->createQueryBuilder();
            $qb->select(array('m'))
                ->from('NaturaPassUserBundle:UserMap', 'm')
                ->join('m.device', 'd', 'WITH', 'd.id = :device')
                ->setParameter(':device', $device->getDevice()->getId())
                ->where('m.owner = :owner')
                ->andWhere('m.type = :type')
                ->setParameter(':type', UserMap::LOUNGE)
                ->setParameter(':owner', $this->getUser());
            $results = $qb->getQuery()->getResult();
            if (count($results)) {
                $issetMap = true;
            }
            if ($device->isAuthorized() && !$issetMap) {
                $usermap = new UserMap();
                $usermap->setDevice($device->getDevice());
                $usermap->setOwner($this->getUser());
                $usermap->setType(UserMap::LOUNGE);
                $usermap->setObjectID($lounge->getId());
                $this->getUser()->addMap($usermap);
                $manager->persist($this->getUser());
                $manager->flush();
            }
        }
        $geolocations = array();
        $qb = $manager->createQueryBuilder();
        if ($lounge->getGeolocation()) {
            $subscribers = $qb->select(array('lu', 'u'))
                ->from('NaturaPassLoungeBundle:LoungeUser', 'lu')
                ->innerJoin('lu.user', 'u')
                ->where('lu.lounge = :lounge')
                ->andWhere('lu.participation = :participation')
                ->andWhere('lu.geolocation = :activeGeo')
                ->andWhere('lu.user <> :user')
                ->setParameter('user', $this->getUser())
                ->setParameter('lounge', $lounge)
                ->setParameter('activeGeo', true)
                ->setParameter('participation', LoungeUser::PARTICIPATION_YES)
                ->getQuery()
                ->getResult();

            foreach ($subscribers as $subscriber) {
                $last = $subscriber->getUser()->getLastGeolocation();
                if ($last) {
                    $geolocations[] = array(
                        'user' => array(
                            'id_user' => $subscriber->getUser()->getId(),
                            'fullname' => $subscriber->getUser()->getFullname(),
                            'usertag' => $subscriber->getUser()->getUsertag(),
                            'profilepicture' => $subscriber->getUser()->getProfilePicture() ? $subscriber->getUser()->getProfilePicture()->getThumb() : $this->getAssetHelper()->getUrl(
                                'img/default-avatar.jpg'
                            ),
                        ),
                        'geolocation' => array(
                            'latitude' => $last->getLatitude(),
                            'longitude' => $last->getLongitude(),
                            'created' => $last->getCreated()->format(\DateTime::ATOM)
                        )
                    );
                }
            }
        }

        return $this->view(array('geolocations' => $geolocations), Codes::HTTP_OK);
    }

    /**
     * Retourne les messages du fil de discussion
     *
     * GET /lounges/{lounge}/messages?limit=10&loaded=5&previous=1
     *
     * previous est un paramètre permettant de spécifier si l'on doit récupérer les messages avant la date spécifiée
     * limit peut être spécifié pour une récupération de commentaire ultérieurs OU pour une première récupération des commentaires
     * offset est utilisé pour la récupération des messages antérieurs
     *
     * @param Request $request
     * @param Lounge $lounge
     *
     * @ParamConverter("lounge", class="NaturaPassLoungeBundle:Lounge")
     *
     * @View()
     *
     * @return \FOS\RestBundle\View\View
     */
    public function getLoungeMessagesAction(Request $request, Lounge $lounge)
    {
        $this->authorize();
        if ($lounge->getAllowShowChat() == Lounge::ALLOW_ADMIN && !$lounge->isSubscriber($this->getUser(), array(LoungeUser::ACCESS_ADMIN))) {
            return $this->view(array('messages' => array(), 'total' => 0), Codes::HTTP_OK);
        } else {
            $limit = $request->query->get('limit', 10);
            $loaded = $request->query->get('loaded', 0);
            $previous = $request->query->get('previous', false);
            $total = count($lounge->getMessages());
            if ($total - $loaded <= 0) {
                return $this->view(array('messages' => array(), 'total' => $total), Codes::HTTP_OK);
            }
            $qb = $this->getDoctrine()->getManager()->getRepository(
                'NaturaPassLoungeBundle:LoungeMessage'
            )->createQueryBuilder('m');
            $qb->where('m.lounge = :lounge')
                ->setParameter('lounge', $lounge)
                ->orderBy('m.created', 'DESC');
            if ($previous) {
                $qb->setFirstResult($total - $loaded);
            }
            if ($limit) {
                $qb->setMaxResults($limit);
            }
            $return = $qb->getQuery()->getResult();
            $messages = new ArrayCollection();
            foreach ($return as $message) {
                $messages[] = $this->getFormatLoungeMessage($message);
            }

            return $this->view(array('messages' => $messages, 'total' => $total), Codes::HTTP_OK);
        }
    }

    /**
     * Retourne les messages du fil de discussion
     *
     * GET /lounges/{lounge}/message/offset?limit=10&offset=10
     *
     * limit peut être spécifié pour une récupération de commentaire ultérieurs OU pour une première récupération des commentaires
     * offset est utilisé pour la récupération des messages antérieurs
     *
     * @param Request $request
     * @param Lounge $lounge
     *
     * @ParamConverter("lounge", class="NaturaPassLoungeBundle:Lounge")
     *
     * @View()
     *
     * @return \FOS\RestBundle\View\View
     */
    public function getLoungeMessageOffsetAction(Request $request, Lounge $lounge)
    {
        $this->authorize();
        if ($lounge->getAllowShowChat() == Lounge::ALLOW_ADMIN && !$lounge->isSubscriber($this->getUser(), array(LoungeUser::ACCESS_ADMIN))) {
            return $this->view(array('messages' => array(), 'total' => 0), Codes::HTTP_OK);
        } else {
            $limit = $request->query->get('limit', 10);
            $offset = $request->query->get('offset', 0);
            $total = count($lounge->getMessages());

            $messages = array();

            $qb = $this->getDoctrine()->getManager()->getRepository(
                'NaturaPassLoungeBundle:LoungeMessage'
            )->createQueryBuilder('m');
            $results = $qb->where('m.lounge = :lounge')
                ->setParameter('lounge', $lounge)
                ->orderBy('m.created', 'DESC')
                ->setFirstResult($offset)
                ->setMaxResults($limit)
                ->getQuery()
                ->getResult();

            foreach ($results as $message) {
                $messages[] = $this->getFormatLoungeMessage($message);
            }

            return $this->view(array('messages' => $messages, 'total' => $total), Codes::HTTP_OK);
        }
    }

    /**
     * Retourne tous les salons matchant le paramètre
     *
     * GET /lounges/{search}/search?limit=10&offset=0
     *
     * search contient le nom recherché encodé en format URL
     *
     * @param string $search
     * @param Request $request
     * @return \FOS\RestBundle\View\View
     *
     * @View(serializerGroups={"LoungeLess", "UserLess"})
     */
    public function getLoungesSearchAction($search, Request $request)
    {
        $this->authorize();
        $search = urldecode($search);
        $limit = $request->query->get('limit', 10);
        $offset = $request->query->get('offset', 0);
        $manager = $this->getDoctrine()->getManager();
        $qb = $manager->createQueryBuilder()->select('l')
            ->from('NaturaPassLoungeBundle:Lounge', 'l')
            ->andWhere('l.endDate > :endDate')
            ->setParameter('endDate', new \DateTime());
        $results = $qb->getQuery()->getResult();
        $lounges = array();
        foreach ($results as $result) {
            if ($result->checkAllowAdd($this->getUser())) {
                $subscriber = $result->isSubscriber(
                    $this->getUser(), array(
                        LoungeUser::ACCESS_DEFAULT,
                        LoungeUser::ACCESS_ADMIN,
                        LoungeUser::ACCESS_INVITED,
                        LoungeUser::ACCESS_RESTRICTED
                    )
                );
                if (preg_match('/' . strtolower($search) . '/', strtolower($result->getName())) && (($result->getAccess() == Lounge::ACCESS_PROTECTED && $subscriber) || in_array(
                            $result->getAccess(), array(Lounge::ACCESS_PUBLIC, Lounge::ACCESS_SEMIPROTECTED)
                        ))
                ) {
                    $lounge = $this->getFormatLounge($result);
                    $lounges[] = $lounge;
                }
            }
        }

        return $this->view(array('lounges' => array_slice($lounges, $offset, $limit)), Codes::HTTP_OK);
    }

    /**
     * Permet de toggle l'accès administrateur d'un utilisateur du salon (si admin, on l'enlève, si normal, on le mets admin)
     * Renvoie le paramètre isAdmin
     *
     * PUT /lounges/{lounge}/subscribers/{subscriber}/admin
     *
     * @param \NaturaPass\LoungeBundle\Entity\Lounge $lounge
     * @param \NaturaPass\UserBundle\Entity\User $subscriber
     *
     * @ParamConverter("lounge", class="NaturaPassLoungeBundle:Lounge")
     * @ParamConverter("subscriber", class="NaturaPassUserBundle:User")
     *
     * @return \FOS\RestBundle\View\View
     */
    public function putLoungeSubscriberAdminAction(Lounge $lounge, User $subscriber)
    {
        $this->authorize($lounge->getAdmins());
        $manager = $this->getDoctrine()->getManager();

        $loungeUser = $manager->getRepository('NaturaPassLoungeBundle:LoungeUser')->findOneBy(
            array(
                'user' => $subscriber,
                'lounge' => $lounge
            )
        );

        if ($loungeUser instanceof LoungeUser) {
            $loungeUser->setAccess(
                $loungeUser->getAccess() === LoungeUser::ACCESS_ADMIN ? LoungeUser::ACCESS_DEFAULT : LoungeUser::ACCESS_ADMIN
            );

            $manager->persist($loungeUser);
            $manager->flush();

            $this->getNotificationService()->queue(
                new LoungeSubscriberAdminNotification($lounge, $loungeUser), array()
            );
            $this->getNotificationService()->queue(
                new LoungeStatusAdminNotification($lounge), $subscriber
            );

            return $this->view(
                array('isAdmin' => $loungeUser->getAccess() === LoungeUser::ACCESS_ADMIN ? true : false), Codes::HTTP_OK
            );
        }

        throw new HttpException(Codes::HTTP_BAD_REQUEST, $this->message('errors.lounge.subscriber.unregistered'));
    }

    /**
     * Change le statut de la géolocalisation d'un salon
     *
     * PUT /lounges/{lounge}/geolocations/{geolocation}
     *
     * @param \NaturaPass\LoungeBundle\Entity\Lounge $lounge
     * @param integer $geolocation
     *
     * @ParamConverter("lounge", class="NaturaPassLoungeBundle:Lounge")
     *
     * @return \FOS\RestBundle\View\View
     */
    public function putLoungeGeolocationAction(Lounge $lounge, $geolocation)
    {
        $this->authorize($lounge->getAdmins());
        $lounge->setGeolocation($geolocation == 1 ? true : false);
        $manager = $this->getDoctrine()->getManager();
        $receivers = array();
        foreach ($lounge->getSubscribers(array(LoungeUser::ACCESS_ADMIN, LoungeUser::ACCESS_DEFAULT)) as $subscriber) {
            if ($lounge->getGeolocation() === true && $subscriber->getParticipation() === LoungeUser::PARTICIPATION_YES && $subscriber->getUser() != $this->getUser()
            ) {
                $receivers[] = $subscriber->getUser();
            }
        }
        $manager->persist($lounge);
        $manager->flush();
        $this->getEmailService()->generate(
            'lounge.geolocate_active', array('%loungename%' => $lounge->getName(), '%fullname%' => $this->getUser()->getFullName()), $receivers, 'NaturaPassEmailBundle:Lounge:geolocate-email.html.twig', array('lounge' => $lounge, 'fullname' => $this->getUser()->getFullName())
        );
        $loungeSubscribers = $lounge->getSubscribers(array(LoungeUser::ACCESS_DEFAULT, LoungeUser::ACCESS_ADMIN), true);
        $loungeSubscribers->removeElement($this->getUser());
        $formatted = $this->getFormatLounge($lounge);

        /* $this->sendSocketEvent(
          'api-lounge:geolocation',
          array('lounge' => $lounge->getLoungetag(), 'data' => $formatted)
          ); */

        $this->getNotificationService()->queue(
            new LoungeGeolocationNotification($this->getUser(), $lounge), $loungeSubscribers->toArray()
        );

        return $this->view($this->success(), Codes::HTTP_OK);
    }

    /**
     * Mets à jour la géolocalisation d'un utilisateur pour un salon
     *
     * PUT /lounges/{lounge}/subscribers/{subscriber}/geolocation
     *
     * JSON LIE
     * {
     *     "geolocation": [false => Ne marche pas, true => Marche]
     * }
     *
     * @param \NaturaPass\LoungeBundle\Entity\Lounge $lounge
     * @param \NaturaPass\UserBundle\Entity\User $subscriber
     * @param Request $request
     *
     * @ParamConverter("lounge", class="NaturaPassLoungeBundle:Lounge")
     * @ParamConverter("subscriber", class="NaturaPassUserBundle:User")
     *
     * @return \FOS\RestBundle\View\View
     * @throws HttpException
     */
    public function putLoungeSubscriberGeolocationAction(Lounge $lounge, User $subscriber, Request $request)
    {
        $this->authorize($subscriber);
        if ($request->request->has('geolocation')) {
            $loungeUser = $lounge->isSubscriber($subscriber);
            if ($loungeUser) {
                $geolocation = $request->request->get('geolocation');
                $manager = $this->getDoctrine()->getManager();
                $loungeUser->setGeolocation(
                    $loungeUser->getParticipation() === LoungeUser::PARTICIPATION_YES ? $geolocation : false
                );
                $manager->persist($loungeUser);
                $manager->flush();
                $loungeSubscribers = $lounge->getSubscribers(
                    array(LoungeUser::ACCESS_DEFAULT, LoungeUser::ACCESS_ADMIN), true
                );
                $loungeSubscribers->removeElement($this->getUser());


                /*                $this->getNotificationService()->generate(
                  'lounge.geolocation.user',
                  $this->getUser(),
                  $loungeSubscribers->toArray(),
                  array('lounge' => $lounge->getName()),
                  array(),
                  $lounge->getId()
                  ); */

                return $this->view(
                    array(
                        'geolocation' => $loungeUser->getParticipation() === LoungeUser::PARTICIPATION_YES ? $geolocation : false
                    ), Codes::HTTP_OK
                );
            }
            throw new HttpException(Codes::HTTP_BAD_REQUEST, $this->message('errors.lounge.subscriber.unregistered'));
        }
        throw new HttpException(Codes::HTTP_BAD_REQUEST, $this->message('errors.parameters'));
    }

    /**
     * Mets à jour la participation d'un utilisateur à un salon
     *
     * PUT /lounges/{lounge}/subscribers/{subscriber}/participation
     *
     * JSON LIE
     * {
     *      "participation": [0 => Ne participe pas, 1 => Participe, 2 => Peut-être]
     * }
     *
     * @param \NaturaPass\LoungeBundle\Entity\Lounge $lounge
     * @param \NaturaPass\UserBundle\Entity\User $subscriber
     * @param Request $request
     *
     * @ParamConverter("lounge", class="NaturaPassLoungeBundle:Lounge")
     * @ParamConverter("subscriber", class="NaturaPassUserBundle:User")
     *
     * @return \FOS\RestBundle\View\View
     * @throws HttpException
     */
    public function putLoungeSubscriberParticipationAction(Lounge $lounge, User $subscriber, Request $request)
    {
        $this->authorize(array_merge(array($subscriber), $lounge->getAdmins()->toArray()));
        if ($request->request->has('participation')) {
            if ($loungeUser = $lounge->isSubscriber($subscriber)) {
                $participation = $request->request->get('participation');
                $manager = $this->getDoctrine()->getManager();
                $loungeUser->setParticipation($participation);
                if ($loungeUser->getParticipation() !== LoungeUser::PARTICIPATION_YES) {
                    $loungeUser->setGeolocation(false);
                }
                $manager->persist($loungeUser);
                $manager->flush();
                $receivers = array();
                foreach ($lounge->getAdmins() as $admin) {
                    if ($admin != $subscriber) {
//                    if ($admin != $subscriber && $admin != $this->getUser()) {
                        $receivers[] = $subscriber;
                    }
                }
                $statusName = $this->getTranslator()->transChoice(
                    'lounge.state.participate.long', $participation, array(), 'lounge'
                );
                if (!empty($receivers)) {
                    $this->getEmailService()->generate(
                        'lounge.participate', array('%loungename%' => $lounge->getName(), '%fullname%' => $subscriber->getFullName()), array($receivers), 'NaturaPassEmailBundle:Lounge:participate-email.html.twig', array(
                            'lounge' => $lounge,
                            'fullname' => $subscriber->getFullName(),
                            'statut' => $participation,
                            'statutname' => $statusName
                        )
                    );
                }

                $this->getNotificationService()->queue(
                    new LoungeSubscriberParticipationNotification($lounge, $loungeUser), array()
                );
                $this->getNotificationService()->queue(
                    new LoungeStatusChangedNotification($loungeUser, $statusName), $lounge->getAdmins()->toArray()
                );

                return $this->view($this->success(), Codes::HTTP_OK);
            }
            throw new HttpException(Codes::HTTP_BAD_REQUEST, $this->message('errors.lounge.subscriber.unregistered'));
        }
        throw new HttpException(Codes::HTTP_BAD_REQUEST, $this->message('errors.parameters'));
    }

    /**
     * Mets à jour la participation d'un non-membre à un salon
     *
     * PUT /lounges/{lounge}/notmember/{subscriber}/participation
     *
     * JSON LIE
     * {
     *      "participation": [0 => Ne participe pas, 1 => Participe, 2 => Peut-être]
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
    public function putLoungeNotmemberParticipationAction(Lounge $lounge, $id, Request $request)
    {
        $this->authorize($lounge->getAdmins()->toArray());

        if ($request->request->has('participation')) {
            if ($loungeNotMember = $lounge->isNotMember($id)) {
                $participation = $request->request->get('participation');

                $loungeNotMember->setParticipation($participation);

                $manager = $this->getDoctrine()->getManager();
                $manager->persist($loungeNotMember);
                $manager->flush();

                $this->getNotificationService()->queue(
                    new LoungeNotUserParticipationNotification($lounge, $loungeNotMember), array()
                );

                return $this->view($this->success(), Codes::HTTP_OK);
            }
            throw new HttpException(
                Codes::HTTP_BAD_REQUEST, $this->message('errors.lounge.subscribernotmember.unregistered')
            );
        }
        throw new HttpException(Codes::HTTP_BAD_REQUEST, $this->message('errors.parameters'));
    }

    /**
     * Mets à jour le commentaire public sur un inscrit au salon
     *
     * PUT /lounges/{lounge}/subscribers/{subscriber}/publiccomment
     *
     * JSON LIE
     * {
     *      "content": "Traqueur / Ligne 1 / Poste 2"
     * }
     *
     * @param \NaturaPass\LoungeBundle\Entity\Lounge $lounge
     * @param \NaturaPass\UserBundle\Entity\User $subscriber
     * @param Request $request
     *
     * @ParamConverter("lounge", class="NaturaPassLoungeBundle:Lounge")
     * @ParamConverter("subscriber", class="NaturaPassUserBundle:User")
     *
     * @return \FOS\RestBundle\View\View
     * @throws HttpException
     */
    public function putLoungeSubscriberPubliccommentAction(Lounge $lounge, User $subscriber, Request $request)
    {
        $this->authorize($lounge->getAdmins()->toArray());
        if ($request->request->has('content')) {
            if ($loungeUser = $lounge->isSubscriber($subscriber)) {
                $manager = $this->getDoctrine()->getManager();
                $loungeUser->setPublicComment($request->request->get('content'));
                $manager->persist($loungeUser);
                $manager->flush();

                return $this->view($this->success(), Codes::HTTP_OK);
            }
            throw new HttpException(Codes::HTTP_BAD_REQUEST, $this->message('errors.lounge.subscriber.unregistered'));
        }
        throw new HttpException(Codes::HTTP_BAD_REQUEST, $this->message('errors.lounge.comment.empty'));
    }

    /**
     * Mets à jour le commentaire public sur un inscrit au salon
     *
     * PUT /lounges/{lounge}/notmember/{id}/publiccomment
     *
     * JSON LIE
     * {
     *      "content": "Traqueur / Ligne 1 / Poste 2"
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
    public function putLoungeNotmemberPubliccommentAction(Lounge $lounge, $id, Request $request)
    {
        $this->authorize($lounge->getAdmins()->toArray());
        if ($request->request->has('content')) {
            if ($loungeNotMember = $lounge->isNotMember($id)) {
                $manager = $this->getDoctrine()->getManager();
                $loungeNotMember->setPublicComment($request->request->get('content'));
                $manager->persist($loungeNotMember);
                $manager->flush();

                return $this->view($this->success(), Codes::HTTP_OK);
            }
            throw new HttpException(
                Codes::HTTP_BAD_REQUEST, $this->message('errors.lounge.subscribernotmember.unregistered')
            );
        }
        throw new HttpException(Codes::HTTP_BAD_REQUEST, $this->message('errors.lounge.comment.empty'));
    }

    /**
     * Mets à jour le commentaire privé sur un inscrit au salon par le propriétaire
     *
     * PUT /lounges/{lounge}/subscribers/{subscriber}/privatecomment
     *
     * JSON LIE
     * {
     *      "content": "Chef de la ligne 1"
     * }
     *
     * @param \NaturaPass\LoungeBundle\Entity\Lounge $lounge
     * @param \NaturaPass\UserBundle\Entity\User $subscriber
     * @param Request $request
     *
     * @ParamConverter("lounge", class="NaturaPassLoungeBundle:Lounge")
     * @ParamConverter("subscriber", class="NaturaPassUserBundle:User")
     *
     * @return \FOS\RestBundle\View\View
     * @throws HttpException
     */
    public function putLoungeSubscriberPrivatecommentAction(Lounge $lounge, User $subscriber, Request $request)
    {
        $this->authorize($lounge->getAdmins()->toArray());
        if ($request->request->has('content')) {
            if ($loungeUser = $lounge->isSubscriber($subscriber)) {
                $manager = $this->getDoctrine()->getManager();
                $loungeUser->setPrivateComment($request->request->get('content'));
                $manager->persist($loungeUser);
                $manager->flush();

                return $this->view($this->success(), Codes::HTTP_OK);
            }
            throw new HttpException(Codes::HTTP_BAD_REQUEST, $this->message('errors.lounge.subscriber.unregistered'));
        }
        throw new HttpException(Codes::HTTP_BAD_REQUEST, $this->message('errors.lounge.comment.empty'));
    }

    /**
     * Mets à jour le commentaire privé sur un inscrit au salon par le propriétaire
     *
     * PUT /lounges/{lounge}/notmember/{id}/privatecomment
     *
     * JSON LIE
     * {
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
    public function putLoungeNotmemberPrivatecommentAction(Lounge $lounge, $id, Request $request)
    {
        $this->authorize($lounge->getAdmins()->toArray());
        if ($request->request->has('content')) {
            if ($loungeNotMember = $lounge->isNotMember($id)) {
                $manager = $this->getDoctrine()->getManager();
                $loungeNotMember->setPrivateComment($request->request->get('content'));
                $manager->persist($loungeNotMember);
                $manager->flush();

                return $this->view($this->success(), Codes::HTTP_OK);
            }
            throw new HttpException(
                Codes::HTTP_BAD_REQUEST, $this->message('errors.lounge.subscribernotmember.unregistered')
            );
        }
        throw new HttpException(Codes::HTTP_BAD_REQUEST, $this->message('errors.lounge.comment.empty'));
    }

    /**
     * Mets à jour les accès d'un utilisateur à un salon
     *
     * PUT /lounges/{lounge}/subscribers/{subscriber}/quiet
     *
     * JSON LIE
     * {
     *      "quiet": [false => Défaut, true => Quiet]
     * }
     *
     * @param \NaturaPass\LoungeBundle\Entity\Lounge $lounge
     * @param \NaturaPass\UserBundle\Entity\User $subscriber
     * @param Request $request
     *
     * @ParamConverter("lounge", class="NaturaPassLoungeBundle:Lounge")
     * @ParamConverter("subscriber", class="NaturaPassUserBundle:User")
     *
     * @return \FOS\RestBundle\View\View
     * @throws HttpException
     */
    public function putLoungeSubscriberQuietAction(Lounge $lounge, User $subscriber, Request $request)
    {
        $this->authorize(array_merge(array($subscriber), $lounge->getAdmins()->toArray()));

        if ($request->request->has('quiet')) {
            if ($loungeUser = $lounge->isSubscriber($subscriber)) {
                $loungeUser->setQuiet($request->request->get('quiet'));

                $manager = $this->getDoctrine()->getManager();
                $manager->persist($loungeUser);
                $manager->flush();

                $this->getNotificationService()->queue(
                    new LoungeSubscriberQuietNotification($lounge, $loungeUser), array()
                );

                return $this->view($this->success(), Codes::HTTP_OK);
            }
            throw new HttpException(Codes::HTTP_BAD_REQUEST, $this->message('errors.lounge.subscriber.unregistered'));
        }
        throw new HttpException(Codes::HTTP_BAD_REQUEST, $this->message('errors.parameters'));
    }

    /**
     * Modifie un salon
     *
     * PUT /lounges/{lounge}
     *
     *   {
     *     "lounge": {
     *       "name": "This is the new name",
     *       "description": "This is the new description",
     *       "access": "2",
     *       "allow_add": [0 => ADMIN, 1 => ALL_MEMBERS],
     *       "allow_show": [0 => ADMIN, 1 => ALL_MEMBERS],
     *       "allow_add_chat": [0 => ADMIN, 1 => ALL_MEMBERS],
     *       "allow_show_chat": [0 => ADMIN, 1 => ALL_MEMBERS],
     *       "geolocation": "on",
     *       "meetingDate": "20/03/2015 18:30",
     *       "endDate": "20/03/2015 18:30",
     *       "meetingAddress": {
     *         "address": "This is the address",
     *         "latitude": "21.0277749",
     *         "longitude": "105.83417499999996",
     *         "altitude": "0"
     *       }
     *     }
     *   }
     *
     * @param Lounge $lounge
     * @param Request $request
     *
     * @return \FOS\RestBundle\View\View
     *
     * @View(serializerGroups={"LoungeLess", "UserLess"})
     *
     * @ParamConverter("lounge", class="NaturaPassLoungeBundle:Lounge")
     */
    public function putLoungeAction(Lounge $lounge, Request $request)
    {
        $this->authorize($lounge->getAdmins());
        $update = $lounge->getUpdated()->getTimestamp();
        $form = $this->createForm(new LoungeType($this->getUser(), $this->container), $lounge, array('csrf_protection' => false, 'method' => 'PUT'));
        $handler = new LoungeHandler($form, $request, $this->getDoctrine()->getManager());
        if ($lounge = $handler->process()) {
            return $this->view(array(
                'lounge' => $this->getFormatLounge($lounge, true),
                "sqlite" => SqliteSerialization::serializeSqliteInserOrReplace("tb_hunt", array(HuntSerialization::serializeHuntSqliteRefresh(array(), null, $lounge, $this->getUser(), true))),
                "sqlite_agenda" => SqliteSerialization::serializeSqliteInserOrReplace("tb_agenda", array(HuntSerialization::serializeAllHuntArraySqlite($lounge, $this->getUser())))
            ), Codes::HTTP_OK);
        }

        return $this->view($form->getErrors(true), Codes::HTTP_BAD_REQUEST);
    }

    /**
     * Modifie le visuel d'un salon
     *
     * PUT /lounges/{lounge}/photos
     *
     * Content-Type: form-data
     *      lounge[photo] = Données FILES
     *
     * @param Lounge $lounge
     * @param Request $request
     *
     * @return \FOS\RestBundle\View\View
     * @throws HttpException
     *
     * @ParamConverter("lounge", class="NaturaPassLoungeBundle:Lounge")
     */
    public function postLoungePhotoAction(Request $request, Lounge $lounge)
    {
        $this->authorize($lounge->getAdmins()->toArray());

        if ($file = $request->files->get('lounge[photo]', false, true)) {
            $media = new LoungeMedia();
            $media->setFile($file);

            $manager = $this->getDoctrine()->getManager();

            $manager->persist($media);
            $manager->flush();

            $lounge->setPhoto($media);
            $manager->persist($lounge);
            $manager->flush();

            return $this->view(array('lounge' => $this->getFormatLounge($lounge)), Codes::HTTP_OK);
        }

        throw new HttpException(Codes::HTTP_BAD_REQUEST, $this->message('errors.parameters'));
    }

    /**
     * Confirme le rattachement d'un utilisateur à un salon, par le créateur du salon ou un administrateur
     *
     * PUT /lounges/{lounge}/users/{user}/join
     *
     * @param \NaturaPass\LoungeBundle\Entity\Lounge $lounge
     * @param \NaturaPass\UserBundle\Entity\User $user
     *
     * @ParamConverter("lounge", class="NaturaPassLoungeBundle:Lounge")
     * @ParamConverter("user", class="NaturaPassUserBundle:User")
     * @View(serializerGroups={"LoungeUserLess", "LoungeUserDetail", "UserDetail", "UserLess"})
     *
     * @return \FOS\RestBundle\View\View
     * @throws HttpException
     */
    public function putLoungeUserJoinAction(Lounge $lounge, User $user)
    {
        $this->authorize(array_merge(array($user, $this->getUser()), $lounge->getAdmins()->toArray()));
        $manager = $this->getDoctrine()->getManager();
        $repository = $manager->getRepository('NaturaPassLoungeBundle:LoungeUser');
        $loungeUser = $repository->findOneBy(
            array(
                'user' => $user,
                'lounge' => $lounge
            )
        );
        if ($loungeUser instanceof LoungeUser) {
            if ($loungeUser->getAccess() === LoungeUser::ACCESS_INVITED && $this->getUser() == $user) {
                $loungeUser->setAccess(LoungeUser::ACCESS_DEFAULT);
                $manager->persist($loungeUser);
                $manager->flush();

                return $this->view(array_merge(
                    $this->success(),
                    array(
                        "sqlite" => SqliteSerialization::serializeSqliteInserOrReplace("tb_hunt", array(HuntSerialization::serializeHuntSqliteRefresh(array(), null, $lounge, $this->getUser(), true))),
                        "sqlite_agenda" => SqliteSerialization::serializeSqliteInserOrReplace("tb_agenda", array(HuntSerialization::serializeAllHuntArraySqlite($lounge, $this->getUser())))
                    )), Codes::HTTP_OK);
            } else {
                if ($loungeUser->getAccess() === LoungeUser::ACCESS_RESTRICTED && $lounge->isAdmin($this->getUser())) {
                    $loungeUser->setAccess(LoungeUser::ACCESS_DEFAULT);
                    $manager->persist($loungeUser);
                    $manager->flush();

                    $this->getNotificationService()->queue(
                        new LoungeJoinAcceptedNotification($lounge), $user
                    );

                    return $this->view(
                        array(
                            'subscriber' => $this->getFormatLoungeSubscriber($loungeUser),
                            "sqlite" => SqliteSerialization::serializeSqliteInserOrReplace("tb_hunt", array(HuntSerialization::serializeHuntSqliteRefresh(array(), null, $lounge, $this->getUser(), true))),
                            "sqlite_agenda" => SqliteSerialization::serializeSqliteInserOrReplace("tb_agenda", array(HuntSerialization::serializeAllHuntArraySqlite($lounge, $this->getUser())))
                        ), Codes::HTTP_OK
                    );
                } else {
                    if ($loungeUser->getAccess() === LoungeUser::ACCESS_INVITED && $lounge->isAdmin($this->getUser())) {
                        $loungeUser->setAccess(LoungeUser::ACCESS_DEFAULT);

                        $manager->persist($loungeUser);
                        $manager->flush();

                        return $this->view(
                            array(
                                'subscriber' => $this->getFormatLoungeSubscriber($loungeUser),
                                "sqlite" => SqliteSerialization::serializeSqliteInserOrReplace("tb_hunt", array(HuntSerialization::serializeHuntSqliteRefresh(array(), null, $lounge, $this->getUser(), true))),
                                "sqlite_agenda" => SqliteSerialization::serializeSqliteInserOrReplace("tb_agenda", array(HuntSerialization::serializeAllHuntArraySqlite($lounge, $this->getUser())))
                            ), Codes::HTTP_OK
                        );
                    }
                }
            }
            throw new HttpException(Codes::HTTP_BAD_REQUEST, $this->message('errors.lounge.subscriber.already'));
        }
        throw new HttpException(Codes::HTTP_BAD_REQUEST, $this->message('errors.lounge.subscriber.unregistered'));
    }

    //vietlh
    /**
     * Confirme le rattachement d'un utilisateur à un salon, par le créateur du salon ou un administrateur
     *
     * PUT /lounges/{lounge}/users/{user}/joinnew
     *
     * @param \NaturaPass\LoungeBundle\Entity\Lounge $lounge
     * @param \NaturaPass\UserBundle\Entity\User $user
     *
     * @ParamConverter("lounge", class="NaturaPassLoungeBundle:Lounge")
     * @ParamConverter("user", class="NaturaPassUserBundle:User")
     * @View(serializerGroups={"LoungeUserLess", "LoungeUserDetail", "UserDetail", "UserLess"})
     *
     * @return \FOS\RestBundle\View\View
     * @throws HttpException
     */
    public function putLoungeUserJoinnewAction(Lounge $lounge, User $user)
    {
        $this->authorize(array_merge(array($user, $this->getUser()), $lounge->getAdmins()->toArray()));
        $manager = $this->getDoctrine()->getManager();
        $repository = $manager->getRepository('NaturaPassLoungeBundle:LoungeUser');
        $loungeUser = $repository->findOneBy(
            array(
                'user' => $user,
                'lounge' => $lounge
            )
        );
        if ($loungeUser instanceof LoungeUser) {

            if ($loungeUser->getAccess() === LoungeUser::ACCESS_INVITED && $this->getUser() == $user) {

                $loungeUser->setAccess(LoungeUser::ACCESS_DEFAULT);
                $manager->persist($loungeUser);
                $manager->flush();

                //vietlh add default notifications to user invited
                    $parameters[0]['type'] = 'lounge.publication.new';
                    $parameters[0]['wanted'] = '1';

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
                return $this->view(array_merge(
                    $this->success(),
                    array(
                        "sqlite" => SqliteSerialization::serializeSqliteInserOrReplace("tb_hunt", array(HuntSerialization::serializeHuntSqliteRefresh(array(), null, $lounge, $this->getUser(), true))),
                        "sqlite_agenda" => SqliteSerialization::serializeSqliteInserOrReplace("tb_agenda", array(HuntSerialization::serializeAllHuntArraySqlite($lounge, $this->getUser())))
                    )), Codes::HTTP_OK);
            } else {
                if ($loungeUser->getAccess() === LoungeUser::ACCESS_RESTRICTED && $lounge->isAdmin($this->getUser())) {
                    $loungeUser->setAccess(LoungeUser::ACCESS_DEFAULT);
                    $manager->persist($loungeUser);
                    $manager->flush();
                    //vietlh add default notifications to user invited
                    $parameters[0]['type'] = 'lounge.publication.new';
                    $parameters[0]['wanted'] = '1';

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
                    $this->getNotificationService()->queue(
                        new LoungeJoinAcceptedNotification($lounge), $user
                    );

                    return $this->view(
                        array(
                            'subscriber' => $this->getFormatLoungeSubscriber($loungeUser),
                            "sqlite" => SqliteSerialization::serializeSqliteInserOrReplace("tb_hunt", array(HuntSerialization::serializeHuntSqliteRefresh(array(), null, $lounge, $this->getUser(), true))),
                            "sqlite_agenda" => SqliteSerialization::serializeSqliteInserOrReplace("tb_agenda", array(HuntSerialization::serializeAllHuntArraySqlite($lounge, $this->getUser())))
                        ), Codes::HTTP_OK
                    );
                } else {
                    if ($loungeUser->getAccess() === LoungeUser::ACCESS_INVITED && $lounge->isAdmin($this->getUser())) {
                        $loungeUser->setAccess(LoungeUser::ACCESS_DEFAULT);

                        $manager->persist($loungeUser);
                        $manager->flush();

                        //vietlh add default notifications to user invited
                            $parameters[0]['type'] = 'lounge.publication.new';
                            $parameters[0]['wanted'] = '1';

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
                        return $this->view(
                            array(
                                'subscriber' => $this->getFormatLoungeSubscriber($loungeUser),
                                "sqlite" => SqliteSerialization::serializeSqliteInserOrReplace("tb_hunt", array(HuntSerialization::serializeHuntSqliteRefresh(array(), null, $lounge, $this->getUser(), true))),
                                "sqlite_agenda" => SqliteSerialization::serializeSqliteInserOrReplace("tb_agenda", array(HuntSerialization::serializeAllHuntArraySqlite($lounge, $this->getUser())))
                            ), Codes::HTTP_OK
                        );
                    }
                }
            }
            throw new HttpException(Codes::HTTP_BAD_REQUEST, $this->message('errors.lounge.subscriber.already'));
        }
        throw new HttpException(Codes::HTTP_BAD_REQUEST, $this->message('errors.lounge.subscriber.unregistered'));
    }

    /**
     * POST /lounges/{lounge}/invites/mails
     *
     * Envoie un email d'invitation pour le salon
     * Vérifie auparavant que les emails spécifiés n'existent pas déjà; le cas échéant, on envoie une notification
     *
     * @param \NaturaPass\LoungeBundle\Entity\Lounge $lounge
     * @param Request $request
     *
     * @ParamConverter("lounge", class="NaturaPassLoungeBundle:Lounge")
     *
     * @View()
     *
     * @return \FOS\RestBundle\View\View
     */
    public function postLoungeInviteMailAction(Lounge $lounge, Request $request)
    {
        $this->authorize($lounge->getAdmins());
        $request = $request->request;
        $manager = $this->getDoctrine()->getManager();
        $repo = $manager->getRepository('NaturaPassUserBundle:User');
        $to = explode(';', $request->get('to', '', true));

        $addresses = array();
        $receivers = array();
        foreach ($to as $email) {
            $user = $repo->findOneBy(
                array(
                    'email' => $email
                )
            );
            if ($user && !$lounge->isSubscriber(
                    $user, array(
                        LoungeUser::ACCESS_ADMIN,
                        LoungeUser::ACCESS_DEFAULT,
                        LoungeUser::ACCESS_INVITED,
                        LoungeUser::ACCESS_RESTRICTED
                    )
                )
            ) {
                $loungeUser = new LoungeUser();
                $loungeUser->setAccess(LoungeUser::ACCESS_INVITED)
                    ->setUser($user)
                    ->setLounge($lounge);
                $manager->persist($loungeUser);
                $receivers[] = $user;
                $manager->flush();
            } else {
                $loungeInvitation = $manager->getRepository('NaturaPassLoungeBundle:LoungeInvitation')->findOneBy(
                    array(
                        'email' => $email,
                        'lounge' => $lounge
                    )
                );
                if (!$loungeInvitation) {
                    $loungeInvitation = new LoungeInvitation();
                    $loungeInvitation->setLounge($lounge)
                        ->setUser($this->getUser())
                        ->setEmail($email);
                    $manager->persist($loungeInvitation);
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
                    $invitation->setEmail($email);
                    $invitation->setUser($this->getUser());
                    $manager->persist($invitation);
                    $manager->flush();
                    $addresses[] = $email;
                }
            }
        }
        if (!empty($addresses)) {
            $this->getEmailService()->generate(
                'lounge.invite_unregistered', array('%loungename%' => $lounge->getName(), '%fullname%' => $this->getUser()->getFullName()), $addresses, 'NaturaPassEmailBundle:Lounge:invite-email.html.twig', array(
                    'message' => $request->get('body', '', true),
                    'lounge' => $lounge,
                    'fullname' => $this->getUser()->getFullName()
                )
            );
        }
        if (count($receivers)) {
            $this->getNotificationService()->queue(
                new LoungeJoinInvitedNotification($lounge), $receivers
            );

            $this->getEmailService()->generate(
                'lounge.invite', array('%loungename%' => $lounge->getName(), '%fullname%' => $this->getUser()->getFullName()), $receivers, 'NaturaPassEmailBundle:Lounge:invite-email.html.twig', array(
                    'message' => $request->get('body', '', true),
                    'lounge' => $lounge,
                    'fullname' => $this->getUser()->getFullName()
                )
            );
        }

        return $this->view($this->success(), Codes::HTTP_OK);
    }

    /**
     * POST /lounges/{lounge}/revives/mails
     *
     * Envoie un email de relance d'invitation pour le salon
     *
     * @param \NaturaPass\LoungeBundle\Entity\Lounge $lounge
     * @param Request $request
     *
     * @ParamConverter("lounge", class="NaturaPassLoungeBundle:Lounge")
     *
     * @View()
     *
     * @return \FOS\RestBundle\View\View
     */
    public function postLoungeReviveMailAction(Lounge $lounge, Request $request)
    {
        $this->authorize($lounge->getAdmins());
        $request = $request->request;
        $manager = $this->getDoctrine()->getManager();
        $repo = $manager->getRepository('NaturaPassUserBundle:User');
        $to = explode(';', $request->get('to', '', true));
        $addresses = array();
        $receivers = array();
        foreach ($to as $email) {
            $user = $repo->findOneBy(
                array(
                    'email' => $email
                )
            );
            if ($user && $lounge->isSubscriber($user, array(LoungeUser::ACCESS_INVITED))) {
                $receivers[] = $user;
            }
        }
        if (count($receivers)) {
            $this->getNotificationService()->queue(
                new LoungeJoinInvitedNotification($lounge), $receivers
            );

            $this->getEmailService()->generate(
                'lounge.revive-invite', array('%loungename%' => $lounge->getName(), '%fullname%' => $this->getUser()->getFullName()), $receivers, 'NaturaPassEmailBundle:Lounge:invite-email.html.twig', array(
                    'message' => $request->get('body', '', true),
                    'lounge' => $lounge,
                    'fullname' => $this->getUser()->getFullName()
                )
            );
        }

        return $this->view($this->success(), Codes::HTTP_OK);
    }

    /**
     * Ajoute un message au fil de discussion d'un salon pour l'utilisateur connecté, si il est inscrit (defaut ou admin)
     *
     * POST /lounges/{lounge}/messages
     *
     * JSON LIE
     * {
     *      "content": "Ça sent le sapin",
     *      "create_time" = "2014-07-16 16:16:59"
     * }
     *
     * @param Lounge $lounge
     * @param Request $request
     *
     * @throws HttpException
     *
     * @return \FOS\RestBundle\View\View
     *
     * @ParamConverter("lounge", class="NaturaPassLoungeBundle:Lounge")
     */
    public function postLoungeMessageAction(Lounge $lounge, Request $request)
    {
        $this->authorize();
        if (!$lounge->checkAllowAddChat($this->getUser())) {
            throw new HttpException(Codes::HTTP_FORBIDDEN, $this->message('codes.403'));
        }
        $content = $request->request->get('content', null);
        $createTime = $request->request->get('create_time', false);
        $guid = $request->request->get('guid', null);
        if ($lounge->isSubscriber($this->getUser())) {
            if ($content) {
                $message = new LoungeMessage;
                $message->setLounge($lounge)
                    ->setOwner($this->getUser())
                    ->setContent(SecurityUtilities::sanitize($content));
                if ($createTime) {
                    $message->setCreated(new \DateTime(date("Y-m-d H:i:s", strtotime($createTime))));
                }
                //vietlh
                if(!is_null($guid)){
                    $message->setGuid($guid);
                }
                //
                $manager = $this->getDoctrine()->getManager();
                $manager->persist($message);
                $manager->flush();

                //vietlh
                $checkDuplicate = $this->checkDuplicatedLoungeMessage($message);
                if ($checkDuplicate) {
                    foreach ($checkDuplicate as $msg) {
                        $manager = $this->getDoctrine()->getManager();
                        $manager->remove($msg);
                        $manager->flush();
                    }
                }
                //
                $formatted = $this->getFormatLoungeMessage($message);

                $loungeSubscribers = $lounge->getSubscribers(
                    array(LoungeUser::ACCESS_DEFAULT, LoungeUser::ACCESS_ADMIN), true
                );

                $loungeSubscribers->removeElement($this->getUser());
                $subscribes = array();
                $ids = array();
                foreach ($loungeSubscribers as $loungeSubscriber) {
                    if (($lounge->getAllowShowChat() == Lounge::ALLOW_ALL_MEMBERS) || $lounge->isAdmin($loungeSubscriber)) {
                        $subscribes[] = $loungeSubscriber;
                        $ids[] = $loungeSubscriber->getId();
                    }
                }

                $this->getNotificationService()->queue(
                    new LoungeMessageNotification($lounge, $message), $subscribes
                );

                return $this->view(array('message' => $formatted), Codes::HTTP_OK);
            }
            throw new HttpException(Codes::HTTP_BAD_REQUEST, $this->message('errors.lounge.message.empty'));
        }
        throw new HttpException(Codes::HTTP_FORBIDDEN, $this->message('errors.lounge.subscriber.unregistered'));
    }

    /**
     * Un utilisateur invite un ami à souscrire à un salon
     *
     * POST /lounges/{lounge}/invites/{receiver}/users
     *
     * @deprecated Utiliser la fonction postLoungeJoinAction
     *
     * @ParamConverter("lounge", class="NaturaPassLoungeBundle:Lounge")
     * @ParamConverter("receiver", class="NaturaPassUserBundle:User")
     */
    public function postLoungeInviteUserAction(Lounge $lounge, User $receiver)
    {
        if (in_array($lounge->getAccess(), array(Lounge::ACCESS_SEMIPROTECTED, Lounge::ACCESS_PROTECTED))) {
            $this->authorize($lounge->getAdmins());
        } else {
            $this->authorize();
        }
        // On vérifie qu'il n'existe pas déjà
        if (!$lounge->isSubscriber(
            $receiver, array(LoungeUser::ACCESS_DEFAULT, LoungeUser::ACCESS_ADMIN, LoungeUser::ACCESS_INVITED)
        )
        ) {
            $manager = $this->getDoctrine()->getManager();
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

            return $this->view($this->success(), Codes::HTTP_OK);
        }
        throw new HttpException(Codes::HTTP_BAD_REQUEST, $this->message('errors.lounge.subscriber.already'));
    }

    /**
     * Un utilisateur invite un groupe à souscrire à un salon
     *
     * POST /lounges/{lounge}/invites/{group}/groups
     *
     * @param Lounge $lounge
     * @param Group $groupFriends
     * @return \FOS\RestBundle\View\View
     *
     * @ParamConverter("lounge", class="NaturaPassLoungeBundle:Lounge")
     * @ParamConverter("groupFriends", class="NaturaPassGroupBundle:Group")
     */
    public function postLoungeInviteGroupAction(Lounge $lounge, Group $groupFriends)
    {
        $this->authorize($lounge->getAdmins());
        $manager = $this->getDoctrine()->getManager();
        $receivers = array();
        foreach ($groupFriends->getSubscribers() as $subscriber) {
            if (!$lounge->isSubscriber(
                $subscriber->getUser(), array(
                    LoungeUser::ACCESS_DEFAULT,
                    LoungeUser::ACCESS_ADMIN,
                    LoungeUser::ACCESS_INVITED,
                    LoungeUser::ACCESS_RESTRICTED
                )
            )
            ) {
                $receivers[] = $subscriber->getUser();
                $loungeUser = new LoungeUser();
                $loungeUser->setUser($subscriber->getUser())
                    ->setLounge($lounge)
                    ->setAccess(LoungeUser::ACCESS_INVITED);
                $manager->persist($loungeUser);
            }
        }
        $this->getNotificationService()->queue(
            new LoungeJoinInvitedNotification($lounge), $receivers
        );

        $manager->flush();

        return $this->view($this->success(), Codes::HTTP_OK);
    }

    /**
     * Do the attachment to a lounge
     *
     * @param Lounge $lounge
     * @param User $user
     *
     * @throws HttpException
     * @return LoungeUser|void
     */
    protected function addSubscriberToLounge(Lounge $lounge, User $user)
    {
        $loungeUser = new LoungeUser;

        $loungeUser->setUser($user)
            ->setLounge($lounge);

        $isUserAdmin = $lounge->isAdmin($this->getUser());
        if ($lounge->getAccess() === Lounge::ACCESS_PUBLIC) {
            if ($isUserAdmin) {
                $loungeUser->setAccess(LoungeUser::ACCESS_INVITED);

//                $this->delay(function () use ($user, $lounge) {
//                    $this->getGraphService()->generateEdge($user, $lounge->getSubscribers(array(LoungeUser::ACCESS_INVITED, LoungeUser::ACCESS_ADMIN, LoungeUser::ACCESS_DEFAULT), true), Edge::LOUNGE_SUBSCRIBER, $lounge->getId());
//                });

                $this->getNotificationService()->queue(
                    new LoungeJoinInvitedNotification($lounge), $user
                );

                $this->getEmailService()->generate(
                    'lounge.invite', array('%loungename%' => $lounge->getName(), '%fullname%' => $this->getUser()->getFullName()), array($user), 'NaturaPassEmailBundle:Lounge:invite-email-without-message.html.twig', array('lounge' => $lounge, 'fullname' => $this->getUser()->getFullName())
                );
            } else {
                $loungeUser->setAccess(LoungeUser::ACCESS_DEFAULT);
//                $this->delay(function () use ($lounge) {
//                    $this->getGraphService()->generateEdge(
//                        $this->getUser(), $lounge->getSubscribers(array(LoungeUser::ACCESS_DEFAULT, LoungeUser::ACCESS_ADMIN), true), Edge::LOUNGE_SUBSCRIBER, $lounge->getId()
//                    );
//                });
            }
        } else {
            if ($lounge->getAccess() === Lounge::ACCESS_SEMIPROTECTED) {
                if ($isUserAdmin) {
                    $loungeUser->setAccess(LoungeUser::ACCESS_INVITED);

//                    $this->delay(function () use ($user, $lounge) {
//                        $this->getGraphService()->generateEdge($user, $lounge->getSubscribers(array(LoungeUser::ACCESS_INVITED, LoungeUser::ACCESS_ADMIN, LoungeUser::ACCESS_DEFAULT), true), Edge::LOUNGE_SUBSCRIBER, $lounge->getId());
//                    });

                    $this->getNotificationService()->queue(
                        new LoungeJoinInvitedNotification($lounge), $user
                    );

                    $this->getEmailService()->generate(
                        'lounge.invite', array(
                        '%loungename%' => $lounge->getName(),
                        '%fullname%' => $this->getUser()->getFullName()
                    ), array($user), 'NaturaPassEmailBundle:Lounge:invite-email-without-message.html.twig', array('lounge' => $lounge, 'fullname' => $this->getUser()->getFullName())
                    );
                } else {
                    $loungeUser->setAccess(LoungeUser::ACCESS_RESTRICTED);

                    $this->getNotificationService()->queue(
                        new LoungeJoinValidationAskedNotification($lounge), $lounge->getAdmins()->toArray()
                    );

                    $adminEmail = array();
                    foreach ($lounge->getAdmins() as $admins) {
                        $adminEmail[] = $admins->getEmail();
                    }
                    $this->getEmailService()->generate(
                        'lounge.valid-invite', array(
                        '%loungename%' => $lounge->getName(),
                        '%fullname%' => $this->getUser()->getFullName()
                    ), $adminEmail, 'NaturaPassEmailBundle:Lounge:valid-invite.html.twig', array('lounge' => $lounge, 'fullname' => $this->getUser()->getFullName())
                    );
                }
            } else {
                if ($lounge->getAccess() === Lounge::ACCESS_PROTECTED) {
                    if ($isUserAdmin) {
                        $loungeUser->setAccess(LoungeUser::ACCESS_INVITED);

//                        $this->delay(function () use ($user, $lounge) {
//                            $this->getGraphService()->generateEdge($user, $lounge->getSubscribers(array(LoungeUser::ACCESS_INVITED, LoungeUser::ACCESS_ADMIN, LoungeUser::ACCESS_DEFAULT), true), Edge::LOUNGE_SUBSCRIBER, $lounge->getId());
//                        });

                        $this->getNotificationService()->queue(
                            new LoungeJoinInvitedNotification($lounge), $user
                        );

                        $this->getEmailService()->generate(
                            'lounge.invite', array(
                            '%loungename%' => $lounge->getName(),
                            '%fullname%' => $this->getUser()->getFullName()
                        ), array($user), 'NaturaPassEmailBundle:Lounge:invite-email-without-message.html.twig', array('lounge' => $lounge, 'fullname' => $this->getUser()->getFullName())
                        );
                    } else {
                        throw new HttpException(Codes::HTTP_BAD_REQUEST, $this->message('errors.lounge.private'));
                    }
                } else {
                    throw new HttpException(Codes::HTTP_BAD_REQUEST, $this->message('errors.unknown'));
                }
            }
        }

        return $loungeUser;
    }

    /**
     * FR : Rattache un où plusieurs utilisateurs à une chasse
     * EN : Connects one or more users to a hunt
     *
     * POST /api/lounges/{lounge}/joins/multiples
     *
     * JSON DATA:
     * {"lounge": {"subscribers": [1,3,10]}}
     *
     * Where subscribers contains the identifier of the invited users
     *
     * @param Request $request
     * @param \NaturaPass\LoungeBundle\Entity\Lounge $lounge
     *
     * @return \FOS\RestBundle\View\View
     * @throws HttpException
     *
     * @ParamConverter("lounge", class="NaturaPassLoungeBundle:Lounge")
     */
    public function postLoungeJoinMultipleAction(Request $request, Lounge $lounge)
    {
        $this->authorize();

        $subscribers = $request->request->get('lounge[subscribers]', null, true);

        $response = array();

        if (is_array($subscribers) && count($subscribers) > 0) {
            foreach ($subscribers as $user_id) {
                try {
                    $manager = $this->getDoctrine()->getManager();

                    $user = $manager->getRepository('NaturaPassUserBundle:User')
                        ->find($user_id);

                    if ($user instanceof User) {
                        $subscriber = $manager->getRepository('NaturaPassLoungeBundle:LoungeUser')
                            ->findOneBy(array('user' => $user, 'lounge' => $lounge));

                        if (!$subscriber) {
                            $subscriber = $this->addSubscriberToLounge($lounge, $user);

                            $manager->persist($subscriber);
                            $manager->flush();

                            $response[] = array(
                                'added' => true,
                                'user_id' => $user_id,
                                'access' => $subscriber->getAccess()
                            );
                        } else {
                            $response[] = array(
                                'added' => false,
                                'user_id' => $user_id,
                                'error' => $this->message('errors.lounge.subscriber.already')
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

        return $this->view(array(
            'subscribers' => $response
            // "sqlite" => SqliteSerialization::serializeSqliteInserOrReplace("tb_hunt", array(HuntSerialization::serializeHuntSqliteRefresh(array(), null, $lounge, $this->getUser(), true))),
            // "sqlite_agenda" => SqliteSerialization::serializeSqliteInserOrReplace("tb_agenda", array(HuntSerialization::serializeAllHuntArraySqlite($lounge, $this->getUser())))
        ), Codes::HTTP_OK);
    }

    /**
     * Rattache un utilisateur à un salon
     *
     * POST /lounges/{user}/joins/{lounge}
     *
     * @param \NaturaPass\UserBundle\Entity\User $user
     * @param \NaturaPass\LoungeBundle\Entity\Lounge $lounge
     *
     * @ParamConverter("user", class="NaturaPassUserBundle:User")
     * @ParamConverter("lounge", class="NaturaPassLoungeBundle:Lounge")
     *
     * @return \FOS\RestBundle\View\View
     * @throws HttpException
     */
    public function postLoungeJoinAction(User $user, Lounge $lounge)
    {
        $this->authorize();
        $manager = $this->getDoctrine()->getManager();
        $repository = $manager->getRepository('NaturaPassLoungeBundle:LoungeUser');
        $loungeUser = $repository->findOneBy(array('user' => $user, 'lounge' => $lounge));
        if (!$loungeUser) {
            $loungeUser = $this->addSubscriberToLounge($lounge, $user);

            $manager->persist($loungeUser);
            $manager->flush();

            if ($user == $this->getUser() && $lounge->getAccess() === Lounge::ACCESS_PUBLIC) {
                return $this->view(array(
                    'access' => $loungeUser->getAccess(),
                    "sqlite" => SqliteSerialization::serializeSqliteInserOrReplace("tb_hunt", array(HuntSerialization::serializeHuntSqliteRefresh(array(), null, $lounge, $this->getUser(), true))),
                    "sqlite_agenda" => SqliteSerialization::serializeSqliteInserOrReplace("tb_agenda", array(HuntSerialization::serializeAllHuntArraySqlite($lounge, $this->getUser())))
                ), Codes::HTTP_OK);
            } else {
                return $this->view(array('access' => $loungeUser->getAccess()), Codes::HTTP_OK);
            }
        }
        throw new HttpException(Codes::HTTP_BAD_REQUEST, $this->message('errors.lounge.subscriber.already'));
    }

    /**
     * Ajoute un salon à la base de données
     *
     * POST /lounges
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
     * @return \FOS\RestBundle\View\View
     *
     * @View()
     */
    public function postLoungeAction(Request $request)
    {
        $this->authorize();
        $form = $this->createForm(new LoungeType($this->getUser(), $this->container), new Lounge(), array('csrf_protection' => false));
        $handler = new LoungeHandler($form, $request, $this->getDoctrine()->getManager());
        if ($lounge = $handler->process()) {
            return $this->view(
                array(
                    'lounge_id' => $lounge->getId(),
                    'loungename' => $lounge->getName(),
                    'loungetag' => $lounge->getLoungetag(),
                    'description' => $lounge->getDescription(),
                    'updated' => $lounge->getLastUpdated($this->getUser())->format(\DateTime::ATOM),
                    "sqlite" => SqliteSerialization::serializeSqliteInserOrReplace("tb_hunt", array(HuntSerialization::serializeHuntSqliteRefresh(array(), null, $lounge, $this->getUser(), true))),
                    "sqlite_agenda" => SqliteSerialization::serializeSqliteInserOrReplace("tb_agenda", array(HuntSerialization::serializeAllHuntArraySqlite($lounge, $this->getUser())))
                ), Codes::HTTP_CREATED
            );
        }

        return $this->view(array($form->getErrors(true)), Codes::HTTP_BAD_REQUEST);
    }

    /**
     * Ajoute un non-membre à la base de données
     *
     * POST /lounges/{lounge}/notmembers
     *
     * Content-Type: form-data
     *      firstname = "Nicolas"
     *      lastname = "Mendez"
     *      participation = 1
     *      publicComment = "public"
     *      privateComment = "prive"
     *
     * @param Lounge $lounge
     * @param Request $request
     *
     * @throws HttpException
     *
     * @return \FOS\RestBundle\View\View
     *
     * @ParamConverter("lounge", class="NaturaPassLoungeBundle:Lounge")
     */
    public function postLoungeNotmemberAction(Lounge $lounge, Request $request)
    {
        $this->authorize($lounge->getAdmins()->toArray());

        $firstname = $request->request->get('firstname', false);
        $lastname = $request->request->get('lastname', false);
        $participation = $request->request->get('participation', 2);
        $publiccomment = $request->request->get('publicComment', null);
        $privatecomment = $request->request->get('privateComment', null);

        if ($firstname) {
            $loungeNotMember = new LoungeNotMember();

            $loungeNotMember->setLounge($lounge)
                ->setFirstname($firstname)
                ->setLastname($lastname)
                ->setParticipation($participation)
                ->setPublicComment($publiccomment)
                ->setPrivateComment($privatecomment);

            $manager = $this->getDoctrine()->getManager();
            $manager->persist($loungeNotMember);
            $manager->flush();

            $this->getNotificationService()->queue(
                new LoungeNotUserAddNotification($lounge, $loungeNotMember), array()
            );

            return $this->view($this->success(), Codes::HTTP_OK);
        }
        throw new HttpException(Codes::HTTP_BAD_REQUEST, $this->message('errors.parameters'));
    }

    /**
     * Supprime le rattachement d'un utilisateur à un salon
     *
     * DELETE /lounges/{user_id}/joins/{lounge_id}
     *
     * @param \NaturaPass\UserBundle\Entity\User $user
     * @param \NaturaPass\LoungeBundle\Entity\Lounge $lounge
     *
     * @ParamConverter("user", class="NaturaPassUserBundle:User")
     * @ParamConverter("lounge", class="NaturaPassLoungeBundle:Lounge")
     *
     * @return \FOS\RestBundle\View\View
     * @throws HttpException
     */
    public function deleteLoungeJoinAction(User $user, Lounge $lounge)
    {
        $this->authorize(array_merge(array($user), $lounge->getAdmins()->toArray()));

        $manager = $this->getDoctrine()->getManager();

        $repository = $manager->getRepository('NaturaPassLoungeBundle:LoungeUser');
        $loungeUser = $repository->findOneBy(
            array(
                'user' => $user,
                'lounge' => $lounge
            )
        );
        $id = $lounge->getId();
        if ($loungeUser instanceof LoungeUser) {
            $manager->remove($loungeUser);
            $manager->flush();

            $this->getNotificationService()->queue(
                new LoungeSubscriberRemoveNotification($lounge, $loungeUser), array()
            );

            return $this->view(array_merge($this->success(),array(
                "sqlite" => array("DELETE FROM `tb_hunt` WHERE `c_id` IN (" . $id . ") AND `c_user_id` = '" . $this->getUser()->getId() . "';"),
                "sqlite_agenda" => SqliteSerialization::serializeSqliteInserOrReplace("tb_agenda", array(HuntSerialization::serializeAllHuntArraySqlite($lounge, $this->getUser())))
            )), Codes::HTTP_OK);
        }

        throw new HttpException(Codes::HTTP_BAD_REQUEST, $this->message('errors.lounge.subscriber.unregistered'));
    }

    //vietlh
    /**
     * Supprime le rattachement d'un utilisateur à un salon
     *
     * DELETE /lounges/{user_id}/joinnews/{lounge_id}
     *
     * @param \NaturaPass\UserBundle\Entity\User $user
     * @param \NaturaPass\LoungeBundle\Entity\Lounge $lounge
     *
     * @ParamConverter("user", class="NaturaPassUserBundle:User")
     * @ParamConverter("lounge", class="NaturaPassLoungeBundle:Lounge")
     *
     * @return \FOS\RestBundle\View\View
     * @throws HttpException
     */
    public function deleteLoungeJoinnewAction(User $user, Lounge $lounge)
    {
        $this->authorize(array_merge(array($user), $lounge->getAdmins()->toArray()));

        $manager = $this->getDoctrine()->getManager();

        $repository = $manager->getRepository('NaturaPassLoungeBundle:LoungeUser');
        $loungeUser = $repository->findOneBy(
            array(
                'user' => $user,
                'lounge' => $lounge
            )
        );
        $id = $lounge->getId();
        if ($loungeUser instanceof LoungeUser) {
            $manager->remove($loungeUser);
            $manager->flush();
            // remove parameters when user leave hunt
                // author: vietlh            
                $repository2 = $manager->getRepository('NaturaPassUserBundle:ParametersNotification');
                $paras = $repository2->findBy(
                    array(
                        'parameters' => $user->getParameters(),
                        'objectID' => $lounge->getId(),
                    )
                );
                foreach ($paras as $p) {
                        $manager->remove($p);
                        $manager->flush();

                    }
                // end
            $this->getNotificationService()->queue(
                new LoungeSubscriberRemoveNotification($lounge, $loungeUser), array()
            );

            return $this->view(array_merge($this->success(),array(
                "sqlite" => array("DELETE FROM `tb_hunt` WHERE `c_id` IN (" . $id . ") AND `c_user_id` = '" . $this->getUser()->getId() . "';"),
                "sqlite_agenda" => SqliteSerialization::serializeSqliteInserOrReplace("tb_agenda", array(HuntSerialization::serializeAllHuntArraySqlite($lounge, $this->getUser())))
            )), Codes::HTTP_OK);
        }

        throw new HttpException(Codes::HTTP_BAD_REQUEST, $this->message('errors.lounge.subscriber.unregistered'));
    }

    /**
     * Supprimer un message du fil de discussion d'un salon
     *
     * DELETE /lounges/{message_id}/message
     *
     * @param LoungeMessage $message
     * @return \FOS\RestBundle\View\View
     *
     * @ParamConverter("message", class="NaturaPassLoungeBundle:LoungeMessage")
     */
    public function deleteLoungeMessageAction(LoungeMessage $message)
    {
        $this->authorize($message->getLounge()->getAdmins());
        $manager = $this->getDoctrine()->getManager();
        $manager->remove($message);
        $manager->flush();

        return $this->view($this->success(), Codes::HTTP_OK);
    }

    /**
     * Supprime un salon de la base de données
     *
     * DELETE /lounges/{lounge}
     *
     * @param Lounge $lounge
     * @return \FOS\RestBundle\View\View
     *
     * @ParamConverter("lounge", class="NaturaPassLoungeBundle:Lounge")
     */
    public function deleteLoungeAction(Lounge $lounge)
    {
        $this->authorize($lounge->getAdmins());
        $manager = $this->getDoctrine()->getManager();
        $id = $lounge->getId();

//        $this->delay(function () use ($lounge) {
//        $this->getGraphService()->deleteEdgeByObject(Edge::LOUNGE, $lounge->getId());
//        });

        $manager->remove($lounge);
        $manager->flush();

        return $this->view(array_merge($this->success(), array("sqlite" => array("hunts" => array("DELETE FROM `tb_hunt` WHERE `c_id` IN (" . $id . ") AND `c_user_id` = '" . $this->getUser()->getId() . "';")),"sqlite_agenda" => array("agendas"=>array("DELETE FROM `tb_agenda` WHERE `c_id` IN (" . $id . ") AND `c_user_id` = '" . $this->getUser()->getId() . "';")))), Codes::HTTP_OK);
    }

    /**
     *
     *
     * DELETE /lounges/{lounge}/notmembers/{id}
     *
     * @param Lounge $lounge
     * @param int $id
     * @return \FOS\RestBundle\View\View
     *
     * @ParamConverter("lounge", class="NaturaPassLoungeBundle:Lounge")
     */
    public function deleteLoungeNotmemberAction(Lounge $lounge, $id)
    {
        $this->authorize($lounge->getAdmins());
        $manager = $this->getDoctrine()->getManager();
        if ($loungeNotMember = $lounge->isNotMember($id)) {
            $manager->remove($loungeNotMember);
            $manager->flush();

            $this->getNotificationService()->queue(
                new LoungeNotUserRemoveNotification($lounge, $loungeNotMember), array()
            );

            return $this->view($this->success(), Codes::HTTP_OK);
        }
        throw new HttpException(
            Codes::HTTP_BAD_REQUEST, $this->message('errors.lounge.subscribernotmember.unregistered')
        );
    }

    /**
     * Supprime la presence de l'utilisateur dans la carte du salon base de données
     *
     * DELETE /lounges/{lounge}/map?identifier=4c9ca1365d479d70c17d7fc9d83d797a695af51368c6791d337718e04aa55abf
     *
     * @param \NaturaPass\LoungeBundle\Entity\Lounge $lounge
     * @param Request $request
     * @return \FOS\RestBundle\View\View
     *
     * @ParamConverter("lounge", class="NaturaPassLoungeBundle:Lounge")
     */
    public function deleteLoungeMapAction(Lounge $lounge, Request $request)
    {
        $this->authorize();
        $identifier = $request->query->get('identifier', false);
        if ($identifier) {
            $manager = $this->getDoctrine()->getManager();
            $device = $this->getDoctrine()->getRepository('NaturaPassUserBundle:Device')->findOneBy(
                array('identifier' => $identifier)
            );
            $qb = $manager->createQueryBuilder();
            $qb->select(array('m'))
                ->from('NaturaPassUserBundle:UserMap', 'm')
                ->join('m.device', 'd', 'WITH', 'd.id = :device')
                ->setParameter(':device', $device->getId())
                ->where('m.owner = :owner')
                ->andWhere('m.type = :type')
                ->setParameter(':type', UserMap::LOUNGE)
                ->setParameter(':owner', $this->getUser());
            $results = $qb->getQuery()->getResult();
            if (count($results)) {
                foreach ($results as $userMap) {
                    $manager->remove($userMap);
                    $manager->flush();
                }

                return $this->view($this->success(), Codes::HTTP_OK);
            }
            throw new HttpException(Codes::HTTP_BAD_REQUEST, $this->message('errors.lounge.map.bad_identifier'));
        }
        throw new HttpException(Codes::HTTP_BAD_REQUEST, $this->message('errors.parameters'));
    }

/**
     * FR : Mets à jour le paramètre permettant de savoir si l'utilisateur souhaite recevoir par email des notifications pour ce groupe
     * EN : Upgrade the parameter to receive email notifications for this lounge
     *
     * PUT /lounges/{lounge}/subscribers/{mailable}/mailable
     *
     *
     * @param \NaturaPass\LoungeBundle\Entity\Lounge $lounge
     * @param integer $mailable
     * @return \FOS\RestBundle\View\View
     * @throws HttpException
     *
     * @ParamConverter("lounge", class="NaturaPassLoungeBundle:Lounge")
     * @View()
     */
    public function putLoungeSubscriberMailableAction(Lounge $lounge, $mailable)
    {
        $this->authorize();
        if ($subscriber = $lounge->isSubscriber($this->getUser())) {
            $subscriber->setMailable($mailable);
            $this->getDoctrine()->getManager()->persist($subscriber);
            $this->getDoctrine()->getManager()->flush();

            return $this->view($this->success(), Codes::HTTP_OK);
        }
        throw new HttpException(Codes::HTTP_BAD_REQUEST, $this->message('errors.group.subscriber.unregistered'));
    }    

}

