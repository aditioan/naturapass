<?php

namespace Api\ApiBundle\Controller\v1;

use Api\ApiBundle\Form\Handler\RegistrationFormHandler;
use Api\ApiBundle\Form\Type\RegistrationFormType;
use NaturaPass\MainBundle\Entity\PostRequest;
use NaturaPass\EmailBundle\Entity\EmailModel;
use NaturaPass\GraphBundle\Entity\Edge;
use NaturaPass\MainBundle\Component\Security\SecurityUtilities;
use NaturaPass\NotificationBundle\Entity\AbstractNotification;
use NaturaPass\NotificationBundle\Entity\NotificationReceiver;
use NaturaPass\NotificationBundle\Entity\User\UserFriendshipAskedNotification;
use NaturaPass\NotificationBundle\Entity\User\UserFriendshipConfirmedNotification;
use NaturaPass\UserBundle\Entity\Device;
use NaturaPass\UserBundle\Entity\PaperModel;
use NaturaPass\UserBundle\Entity\PaperParameter;
use NaturaPass\UserBundle\Entity\Parameters;
use NaturaPass\UserBundle\Entity\ParametersEmail;
use NaturaPass\UserBundle\Entity\ParametersFilter;
use NaturaPass\UserBundle\Entity\ParametersNotification;
use NaturaPass\UserBundle\Entity\UserAddress;
use NaturaPass\UserBundle\Entity\UserDevice;
use NaturaPass\UserBundle\Form\Handler\General\UserAddressFormHandler;
use NaturaPass\UserBundle\Form\Handler\ParametersFormHandler;
use NaturaPass\UserBundle\Form\Handler\UserMediaHandler;
use NaturaPass\UserBundle\Form\Type\General\UserAddressFormType;
use NaturaPass\UserBundle\Form\Type\ParametersFormType;
use NaturaPass\UserBundle\Form\Type\UserMediaType;
use Predis\Client as RedisClient;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\HttpFoundation\Request;
use FOS\RestBundle\Controller\Annotations\View;
use FOS\RestBundle\Util\Codes;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use NaturaPass\MainBundle\Entity\Geolocation;
use NaturaPass\UserBundle\Entity\User;
use NaturaPass\UserBundle\Entity\UserFriend;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\HttpKernel\Exception\HttpException;
use NaturaPass\UserBundle\Entity\UserMedia;
use NaturaPass\LoungeBundle\Entity\LoungeUser;
use NaturaPass\UserBundle\Entity\Invitation;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * Description of UsersController
 *
 * @author vincentvalot
 */
class UsersController extends ApiRestController
{

    /**
     * Envoie de l'interstice
     *
     * GET /users/interstice
     *
     * @return array
     */
    public function getUsersIntersticeAction()
    {
        if ($this->container->getParameter("visibility")['interstice'] == true) {
            $array = array(
                "enable" => true,
                "link" => "",
                "url" => "/img/slide-partenaires.jpg",
                "displaytime" => 3);
        } else {
            $array = array(
                "enable" => false
            );
        }

        return $this->view($array, Codes::HTTP_OK);
    }

    /**
     * Envoie de l'interstice
     *
     * GET /users/site/interstice
     *
     * @return array
     */
    public function getUsersSiteIntersticeAction()
    {
        $array = array(
            "enable" => false,
            "link" => "/challenge/3/challengenaturapass-lenatizdelasaison20142015",
            "url" => "/img/interstice-naturapass-concours.jpg");
        return $this->view($array, Codes::HTTP_OK);
    }

    /**
     * Retourne tous les users matchant le paramètre
     *
     * GET /users/search?q=Chass
     *
     * q contient le nom recherché encodé en format URL
     *
     * @param Request $request
     * @return \FOS\RestBundle\View\View
     *
     * @View(serializerGroups={"UserDetail", "UserFriend"})
     */
    public function getUsersSearchAction(Request $request)
    {
        $this->authorize();
        $search = urldecode($request->query->get('q', ''));
        $searchWithoutSpace = str_replace(' ', '', urldecode(trim($request->query->get('q', ''))));
        $select2 = $request->query->get('select2', false);
        $users = $this->getDoctrine()->getManager()
            ->createQueryBuilder()
            ->select('u')
            ->from('NaturaPassUserBundle:User', 'u')
            ->where(
                "CONCAT(u.firstname, u.lastname) LIKE :name OR CONCAT(u.lastname, u.firstname) LIKE :name OR u.firstname LIKE :firstname OR u.lastname LIKE :lastname"
            )
            ->andWhere('u.id != :connected')
            ->setParameter('lastname', '%' . $search . '%')
            ->setParameter('firstname', '%' . $search . '%')
            ->setParameter('name', '%' . $searchWithoutSpace . '%')
            ->setParameter('connected', $this->getUser()->getId())
            ->setMaxResults($request->query->get('page_limit', 100000))
            ->setFirstResult($request->query->get('page_offset', 0))
            ->getQuery()
            ->getResult();
        $array = array();
        foreach ($users as $user) {
            $status = $this->getUser()->getStateFriendsWith(
                $user, array(UserFriend::CONFIRMED, UserFriend::ASKED, UserFriend::REJECTED)
            );
            list($way, $friendship) = $this->getUser()->hasFriendshipWith(
                $user, array(UserFriend::CONFIRMED, UserFriend::ASKED, UserFriend::REJECTED)
            );
            if ($select2) {
                $array[] = array(
                    'id' => $user->getId(),
                    'text' => $user->getFullname()
                );
            } else {
                $array[] = array(
                    'id' => $user->getId(),
                    'fullname' => $user->getFullName(),
                    'firstname' => $user->getFirstName(),
                    'lastname' => $user->getLastName(),
                    'usertag' => $user->getUsertag(),
                    'state' => is_array($status) ? $status['state'] : 0,
                    'friendship' => is_object($friendship) ? array(
                        'way' => $way,
                        'state' => $friendship->getState()
                    ) : false,
                    'mutualFriends' => $this->getUser()->getMutualFriendsWith($user)->count(),
                    'profilepicture' => $user->getProfilePicture() ? $user->getProfilePicture()->getThumb() : $this->getAssetHelper()->getUrl('img/interface/default-avatar.jpg')
                );
            }
        }
        return $this->view(array('users' => $array, 'total' => count($array), 'term' => $search), Codes::HTTP_OK);
    }

    /**
     * Retourne tous les utilisateurs de la base de données
     *
     * GET /users
     *
     * @return array
     *
     * @View(serializerGroups={"UserDetail"})
     *
     * @deprecated Will be deleted in API v2
     *
     */
    public function getUsersAction()
    {
        $this->authorize();
        $users = $this->getDoctrine()->getRepository('NaturaPassUserBundle:User')->findAll();
        return $this->view(array('users' => $users), Codes::HTTP_OK);
    }

    /**
     * Retourne les adresses de l'utilisateur connecté
     *
     * GET /user/addresses?limit=5&offset=0&favorite
     *
     * favorite permet de ne récupérer que l'adresse favorite de l'utilisateur
     *
     * @param Request $request
     *
     * @return \FOS\RestBundle\View\View
     * @throws HttpException
     */
    public function getUserAddressesAction(Request $request)
    {
        $this->authorize();
        if ($request->query->has('favorite')) {
            $address = $this->getDoctrine()->getManager()->getRepository('NaturaPassUserBundle:UserAddress')->findOneBy(
                array(
                    'owner' => $this->getUser(),
                    'favorite' => true
                )
            );
            if ($address instanceof UserAddress) {
                return $this->view(array('address' => $this->getFormatAddress($address)), Codes::HTTP_OK);
            }
            throw new HttpException(Codes::HTTP_BAD_REQUEST, $this->message('errors.user.addresses.nofavorite'));
        } else {
            $addresses = $this->getUser()->getAddresses()->slice(
                $request->query->get('offset', 0), $request->query->get('limit', 20)
            );
            $result = array();
            foreach ($addresses as $address) {
                $result[] = $this->getFormatAddress($address);
            }
            return $this->view(array('addresses' => $result), Codes::HTTP_OK);
        }
    }

    /**
     * Retourne le partage par defaut des publications
     *
     * GET /users/parameters/sharing
     *
     * @return array
     *
     * @View(serializerGroups={"UserDetail"})
     *
     */
    public function getUsersParametersSharingAction()
    {
        $this->authorize();
        return $this->view(array('sharing' => $this->getUser()->getParameters()->getPublicationSharing()->getShare()), Codes::HTTP_OK);
    }

    /**
     * retourne les parametre de partage par default du filtre partage
     *
     * GET /users/parameter/sharingfilter
     *
     * @return array
     *
     */
    public function getUsersParameterSharingfilterAction()
    {
        $this->authorize();
        $parameters = $this->getUser()->getParameters();
        $sharingFilter = $parameters->getSharingFilter();
        return $this->view(array('sharingFilter' => $sharingFilter), Codes::HTTP_OK);
    }

    /**
     * retourne les parametre de groupe par default du filtre partage
     *
     * GET /users/parameter/groupfilter
     *
     * @return array
     *
     */
    public function getUsersParameterGroupfilterAction()
    {
        $this->authorize();
        $parameters = $this->getUser()->getParameters();
        $groupFilters = $parameters->getFilters();
        $group = array();
        foreach ($groupFilters as $groupFilter) {
            $group[] = $groupFilter->getGroupFilter();
        }
        return $this->view(array('groupFilter' => $group), Codes::HTTP_OK);
    }

    /**
     * retourne les parametre de groupe par default du filtre partage
     *
     * GET /users/parameter/groupfilter
     *
     * @return array
     *
     */
    public function getUsersParameterFilterAction()
    {
        $this->authorize();
        $parameters = $this->getUser()->getParameters();
        $groupFilters = $parameters->getFilters();
        $sharingFilter = $parameters->getSharingFilter();
        $group = array();
        foreach ($groupFilters as $groupFilter) {
            $group[] = $groupFilter->getGroupFilter();
        }
        return $this->view(array('sharingFilter' => $sharingFilter, 'groupFilter' => $group), Codes::HTTP_OK);
    }

    /**
     * Retourne l'utilisateur connecté
     *
     * GET /user/connected
     *
     * @return array
     *
     * @View(serializerGroups={"UserDetail"})
     *
     */
    public function getUserConnectedAction()
    {
        if ($this->isConnected()) {
            $address = $this->getUser()->getFavoriteAddress();
            $user = $this->getFormatUser($this->getUser(), false);
            if ($address instanceof UserAddress) {
                $user = array_merge($user, array('address' => $this->getFormatAddress($address)));
            }
            return $this->view(array('user' => $user), Codes::HTTP_OK);
        }
        return $this->view(null, Codes::HTTP_NO_CONTENT);
    }

    /**
     * Retourne tous les utilisateurs en attente de validation de l'utilisateur
     *
     * GET /users/waiting
     *
     * @return array
     *
     * @View(serializerGroups={"UserDetail"})
     *
     */
    public function getUsersWaitingAction()
    {
        $this->authorize();

        $users = $this->getUser()->getFriends(
            UserFriend::TYPE_BOTH, UserFriend::ASKED, User::USERFRIENDWAY_FRIENDTOUSER
        );

        $array = array();
        foreach ($users as $user) {
            $array[] = $this->getFormatUser($user);
        }

        return $this->view(array('users' => $array), Codes::HTTP_OK);
    }

    /**
     * Retourne les parametres d'un utilisateur de la base de données
     *
     * GET /users/{user_id}/parameters
     *
     * @View(serializerGroups={"UserParameters", "UserParametersDetail", "SharingLess"})
     *
     * @param User $user
     * @ParamConverter("user", class="NaturaPassUserBundle:User")
     *
     * @return \FOS\RestBundle\View\View
     */
    public function getUserParametersAction(User $user)
    {
        return $this->view(array('user' => $user), Codes::HTTP_OK);
    }

    /**
     * Retourne la photo de profil d'un utilisateur de la base de données
     *
     * GET /users/{user}/profilepicture
     *
     * @param User $user
     * @ParamConverter("user", class="NaturaPassUserBundle:User")
     *
     * @View()
     *
     * @return \FOS\RestBundle\View\View
     *
     * @deprecated Will be removed in v2 as it will be returned each time
     */
    public function getUsersProfilepictureAction(User $user)
    {
        return $this->view(
            array(
                'profilePicture' => $user->getProfilePicture() ? $user->getProfilePicture()->getResize() : $this->getAssetHelper()->getUrl('img/interface/default-avatar.jpg')
            )
        );
    }

    /**
     * Retourne le profil d'un utilisateur de la base de données
     *
     * GET /users/{user}/profile
     *
     * @param User $user
     * @ParamConverter("user", class="NaturaPassUserBundle:User", options={"mapping": {"user": "usertag"}})
     *
     * @View(serializerGroups={"UserDetail", "UserFriends", "UserFriendLess"})
     *
     * @return \FOS\RestBundle\View\View
     *
     * @deprecated Will be removed in v2
     */
    public function getUserProfileAction(User $user)
    {
        $this->authorize();
        list($way, $friendship) = $this->getUser()->hasFriendshipWith(
            $user, array(UserFriend::CONFIRMED, UserFriend::ASKED, UserFriend::REJECTED)
        );
        $view = array(
            'user' => $this->getFormatUser($user),
            'friendship' => is_object($friendship) ? array('way' => $way, 'state' => $friendship->getState()) : false,
            'mutualFriends' => $this->getUser()->getMutualFriendsWith($user)->count()
        );
        if ($user->getParameters()->getFriends()) {
            $view['user']['friends'] = count($user->getFriends());
        }
        return $this->view(array('profile' => $view), Codes::HTTP_OK);
    }

    /**
     * Retourne les données d'un utilisateur
     *
     * GET /users/{user}
     *
     * @param \NaturaPass\UserBundle\Entity\User $user
     * @return array
     *
     * @View(serializerGroups={"UserDetail"})
     *
     * @ParamConverter("user", class="NaturaPassUserBundle:User")
     */
    public function getUserAction(User $user)
    {
        return $this->view(
            array(
                'user' => array(
                    'firstname' => $user->getFirstname(),
                    'lastname' => $user->getLastname(),
                    'email' => $user->getEmail(),
                    'courtesy' => $user->getCourtesy(),
                    'photo' => $this->getBaseUrl() . ($user->getProfilePicture() ? $user->getProfilePicture()->getResize() : $this->getAssetHelper()->getUrl('img/interface/default-avatar.jpg'))
                )
            ), Codes::HTTP_OK
        );
    }

    /**
     * Retourne les données d'un utilisateur
     *
     * GET /user/login?email=v.valot@e-conception.fr
     *                  &password=22e0366ffd02fdd942a960119e71fce654c2ccf5
     *                  &device=[ios|android]
     *                  &identifier=e0366ffd02fdd942a960119e71fce654c2ccf5
     *                  &authorized=1
     *                  &name=[iphone|ipod|ipad|...]
     *
     * GET /user/login?facebookid=100006772984423
     *                  &device=[ios|android]
     *                  &identifier=e0366ffd02fdd942a960119e71fce654c2ccf5
     *                  &authorized=1
     *                  &name=[iphone|ipod|ipad|...]
     *
     * authorized: Défini si un utilisateur a autorisé les notifications PUSH sur son appareil
     *
     * @param Request $request
     * @return \FOS\RestBundle\View\View
     *
     * @View(serializerGroups={"UserLess"})
     */
    public
    function getUserLoginAction(Request $request)
    {
        $manager = $this->getDoctrine()->getManager();
        if ($fid = $request->query->get('facebookid', false)) {
            $params = array(
                'facebook_id' => $request->query->get('facebookid', '')
            );
        } else {
            $params = array(
                'email' => urldecode($request->query->get('email', '')),
                'password' => $request->query->get('password', '')
            );
            $params2 = array(
                'email' => urldecode($request->query->get('email', ''))
            );
        }
        $user = $manager->getRepository('NaturaPassUserBundle:User')->findOneBy($params);

        if (isset($params2)) {
            $user2 = $manager->getRepository('NaturaPassUserBundle:User')->findOneBy($params2);
        }
        if (!$user instanceof User) {
            if ((isset($params2) && !$user2 instanceof User) || !isset($params2)) {
                throw new HttpException(Codes::HTTP_NOT_FOUND, $this->message('errors.user.nonexistent'));
            } else {
                throw new HttpException(Codes::HTTP_NOT_FOUND, $this->message('errors.user.passwordnonexistent'));
            }
        }

        $device = $request->query->get('device', false);
        $identifier = $request->query->get('identifier', false);
        $authorized = $request->query->get('authorized', false);
        $name = urldecode($request->query->get('name', null));

        if ($device && $identifier) {
            $type = $device == 'ios' ? Device::IOS : Device::ANDROID;

            $device = $manager->getRepository('NaturaPassUserBundle:Device')->findOneBy(
                array(
                    'type' => $type,
                    'identifier' => $identifier
                )
            );

            if (!$device instanceof Device) {
                $device = new Device();
                $device->setType($type)
                    ->setIdentifier($identifier)
                    ->setName($name);

                $manager->persist($device);
                $manager->flush();
            }

            foreach ($device->getOwners() as $owner) {
                if ($owner->getOwner()->getId() != $user->getId()) {
                    $manager->remove($owner);
                    $manager->flush();
                }
            }

            $userDevice = $device->hasOwner($user);

            if (!$userDevice instanceof UserDevice) {
                $userDevice = new UserDevice();
                $userDevice->setOwner($user)
                    ->setDevice($device)
                    ->setVerified(true)
                    ->setAuthorized(true);
            } else {
                $userDevice->setVerified(true)
                    ->setAuthorized(true);
            }

            $manager->persist($userDevice);
            $manager->flush();

            $token = new UsernamePasswordToken($user, null, "main", $user->getRoles());
            $this->getSecurityTokenStorage()->setToken($token);

            $session = $this->get('session');
            $session->start();

            $this->getRedisService()->addSession();
            $parameters = $user->getParameters();
            return $this->view(
                array(
                    'user' => array(
                        'id' => $user->getId(),
                        'sid' => $session->getId(),
                        'usertag' => $user->getUsertag(),
                        'firstname' => $user->getFirstname(),
                        'email' => $user->getEmail(),
                        'lastname' => $user->getLastname(),
                        'profilepicture' => $user->getProfilePicture() ? $user->getProfilePicture()->getThumb() : $this->getAssetHelper()->getUrl('img/interface/default-avatar.jpg'),
                        'courtesy' => $user->getCourtesy(),
                        'birthday' => ($user->getBirthday() ? date("d/m/Y", $user->getBirthday()->getTimestamp()) : "")
                    )
                ), Codes::HTTP_OK
            );
        }
        throw new HttpException(Codes::HTTP_BAD_REQUEST, $this->message('errors.parameters'));
    }

    /**
     * Envoi un email de changement de mot de passe pour l'utilisateur connecté et enregistre le token associé
     *
     * PUT /user/reset/password
     *
     * JSON associé
     * {
     *      "email": "v.valot@e-conception.fr"
     * }
     *
     * @param Request $request
     * @return \FOS\RestBundle\View\View
     */
    public
    function putUserResetPasswordAction(Request $request)
    {
        $username = $request->request->get('email');
        $user = $this->get('fos_user.user_manager')->findUserByUsernameOrEmail($username);
        if (null === $user) {
            throw new HttpException(Codes::HTTP_NOT_FOUND, $this->message('errors.user.nonexistent'));
        }
        if ($user->isPasswordRequestNonExpired($this->container->getParameter('fos_user.resetting.token_ttl'))) {
            throw new HttpException(Codes::HTTP_BAD_REQUEST, $this->message('errors.user.password.requested'));
        }
        if (null === $user->getConfirmationToken()) {
            /** @var $tokenGenerator \FOS\UserBundle\Util\TokenGeneratorInterface */
            $tokenGenerator = $this->get('fos_user.util.token_generator');
            $user->setConfirmationToken($tokenGenerator->generateToken());
        }
        $message = \Swift_Message::newInstance()
            ->setContentType("text/html")
            ->setSubject($this->get('translator')->trans('user.changepassword.subject', array(), $this->container->getParameter("translation_name") . 'email'))
            ->setFrom($this->get('translator')->trans('user.changepassword.from', array(), $this->container->getParameter("translation_name") . 'email'))
            ->setTo($user->getEmail())
            ->addBcc($this->container->getParameter("email_bcc"))
            ->setBody(
                $this->get('templating')->render(
                    'NaturaPassEmailBundle:User:change-password.html.twig', array(
                        'fullname' => $user->getFullName(),
                        'lien' => $user->getConfirmationToken()
                    )
                )
            );
        // \Doctrine\Common\Util\Debug::dump($message);die("fck");

        $this->get('mailer')->send($message);
        $user->setPasswordRequestedAt(new \DateTime());
        $this->get('fos_user.user_manager')->updateUser($user);
        return $this->view($this->success(), Codes::HTTP_OK);
    }

    /**
     * Change le mot de passe de l'utilisateur si le token associé à l'email est valide
     *
     * PUT /user/change/password.
     *
     * JSON associé
     * {
     *      "email": "v.valot@e-conception.fr",
     *      "token": "dkjdklo6sdf56seza",
     *      "password": {
     *          "new": "mot de passe encodé",
     *          "confirmation": "mot de passe encodé"
     *      }
     * }
     *
     * @param Request $request
     * @return \FOS\RestBundle\View\View
     */
    public
    function putUserChangePasswordAction(Request $request)
    {
        $email = $request->request->get('email', false);
        $token = $request->request->get('token', false);
        $new = $request->request->get('password[new]', false, true);
        $confirmation = $request->request->get('password[confirmation]', false, true);
        if (!$email || !$token || !$new || !$confirmation) {
            throw new HttpException(Codes::HTTP_BAD_REQUEST, $this->message('errors.parameters'));
        }
        if ($new !== $confirmation) {
            throw new HttpException(Codes::HTTP_BAD_REQUEST, $this->message('errors.user.password.both'));
        }
        /**
         * @var \NaturaPass\UserBundle\Entity\User $user
         */
        $user = $this->container->get('fos_user.user_manager')->findUserByConfirmationToken($token);
        if ($user === null) {
            throw new HttpException(Codes::HTTP_NOT_FOUND, $this->message('errors.user.nonexistent'));
        }
        if (!$user->isPasswordRequestNonExpired($this->container->getParameter('fos_user.resetting.token_ttl'))) {
            throw new HttpException(Codes::HTTP_NOT_FOUND, $this->message('errors.user.password.requested'));
        }
        $user->setPassword($new)
            ->setConfirmationToken(null)
            ->setPasswordRequestedAt(null);
        $this->container->get('fos_user.user_manager')->updateUser($user);
        return $this->view($this->success(), Codes::HTTP_OK);
    }

    /**
     * Retourne les notifications de l'utilisateur
     *
     * GET /user/notifications
     *
     * group.join.accepted          =>  Page de groupe
     * group.join.invited           =>  Listing de groupes
     * group.join.refused           =>  Listing de groupes
     * group.join.asked             =>  Listing de groupes
     *
     * lounge.join.accepted          =>  Page de salon
     * lounge.join.invited           =>  Listing de salons
     * lounge.join.refused           =>  Listing de salons
     * lounge.join.asked             =>  Listing de salons
     *
     * publication.commented        =>  Page de la publication
     * publication.liked            =>  Page de la publication
     *
     * @View()
     */
    public
    function getUserNotificationsAction()
    {
        $this->authorize();
        $tmp = $this->getUser()->getNotifications();
        $notifications = array();
        foreach ($tmp as $notification) {
            $sender = $notification->getNotification()->getSender();

            $notifications[] = array(
                'id' => $notification->getNotification()->getId(),
                'content' => $notification->getNotification()->getContent(),
                'link' => $notification->getNotification()->getLink(),
                'readed' => $notification->getState(),
                'updated' => $notification->getUpdated()->format(\DateTime::ATOM),
                'type' => $notification->getNotification()->getType(),
                'object_id' => $notification->getNotification()->getObjectID(),
                'sender' => array(
                    'id' => $sender->getId(),
                    'firstname' => $sender->getFirstname(),
                    'lastname' => $sender->getLastname(),
                    'fullname' => $sender->getFullname(),
                    'usertag' => $sender->getUsertag(),
                    'profilepicture' => $sender->getProfilePicture() ? $sender->getProfilePicture()->getThumb() : $this->getAssetHelper()->getUrl('img/interface/default-avatar.jpg'),
                )
            );
        }
        return $this->view(array('notifications' => $notifications));
    }

    /**
     * Retourne les amis d'un utilisateur
     *
     * GET /users/{user}/friends?name=Nom
     *
     * @param \NaturaPass\UserBundle\Entity\User $user
     * @param Request $request
     *
     * @return \FOS\RestBundle\View\View
     *
     * @ParamConverter("user", class="NaturaPassUserBundle:User")
     */
    public
    function getUserFriendsAction(User $user, Request $request)
    {
        $this->authorize();

        if ($this->getUser() != $user && !$user->getParameters()->getFriends()) {
            throw new HttpException(Codes::HTTP_BAD_REQUEST, $this->message('errors.user.no_friend_sharing'));
        }

        if ($request->query->get('mutual', false)) {
            $friends = $user->getMutualFriendsWith($this->getUser());
        } else {
            $friends = $user->getFriends();
        }
        if ($name = $request->query->get('name', false)) {
            $pending = $friends->filter(
                function ($element) use ($name) {
                    return stristr($element->getFullname(), $name) ? true : false;
                }
            );
            $friends = array();
            foreach ($pending as $friend) {
                $friends[] = array(
                    'id' => "" . $friend->getId(),
                    'text' => $friend->getFullname()
                );
            }
            return $this->view(array('friends' => $friends));
        }
        $array = array();
        foreach ($friends as $friend) {
            $array[] = $this->getFormatUser($friend, true);
        }
        return $this->view(array('friends' => $array), Codes::HTTP_OK);
    }

    /**
     * Retourne les amis d'un utilisateur
     *
     * GET /me/friends?name=Nom
     *
     * @param Request $request
     *
     * @return \FOS\RestBundle\View\View
     */
    public
    function getMeFriendsAction(Request $request)
    {
        $this->authorize();
        $friends = $this->getUser()->getFriends();
        if ($name = $request->query->get('name', false)) {
            $pending = $friends->filter(
                function ($element) use ($name) {
                    return stristr($element->getFullname(), $name) ? true : false;
                }
            );
            $friends = array();
            foreach ($pending as $friend) {
                $friends[] = array(
                    'id' => "" . $friend->getId(),
                    'text' => $friend->getFullname(),
                    'usertag' => $friend->getUsertag(),
                    'profilepicture' => $friend->getProfilePicture() ? $friend->getProfilePicture()->getThumb() : $this->getAssetHelper()->getUrl('img/interface/default-avatar.jpg'),
                    'parameters' => array("friend" => (boolean)$friend->getParameters()->getFriends()),
                );
            }
            return $this->view(array('friends' => $friends));
        }
        $array = array();
        foreach ($friends as $friend) {
            $array[] = array(
                'id' => "" . $friend->getId(),
                'text' => $friend->getFullname(),
                'usertag' => $friend->getUsertag(),
                'profilepicture' => $friend->getProfilePicture() ? $friend->getProfilePicture()->getThumb() : $this->getAssetHelper()->getUrl('img/interface/default-avatar.jpg'),
                'parameters' => array("friend" => (boolean)$friend->getParameters()->getFriends()),
            );
        }
        return $this->view(array('friends' => $array), Codes::HTTP_OK);
    }

    /**
     * Retourne les groupes d'un utilisateur
     *
     * GET /users/{user}/groups
     *
     * @param \NaturaPass\UserBundle\Entity\User $user
     * @return array
     *
     * @View(serializerGroups={"UserLess", "GroupID", "GroupLess"})
     *
     * @ParamConverter("user", class="NaturaPassUserBundle:User")
     */
    public
    function getUserGroupsAction(User $user)
    {
        $groups = $user->getGroupSubscribes();
        $aGroup = array();
        foreach ($groups as $group) {
            $aGroup[] = $this->getFormatGroupLess($group->getGroup());
        }
        return $this->view(
            array(
                'user_id' => $user->getId(),
                'groups' => $aGroup,
                'nbGroups' => count($aGroup)
            ), Codes::HTTP_OK
        );
    }

    /**
     * Ruturn all hunts and groups of one user
     *
     * GET /users/{user}/groups/hunts
     *
     * @param \NaturaPass\UserBundle\Entity\User $user
     * @return array
     *
     * @ParamConverter("user", class="NaturaPassUserBundle:User")
     */
    public
    function getUserGroupsHuntsAction(User $user)
    {
        $groups = $user->getGroupSubscribes();

        $aGroup = array();
        foreach ($groups as $group) {
            $aGroup[] = $this->getFormatGroup($group->getGroup());
        }
        $lounges = $user->getLoungeSubscribes();
        $aLounge = array();
        foreach ($lounges as $lounge) {
            if (in_array($lounge->getAccess(), array("2", "3"))) {
                if (new \DateTime() < $lounge->getLounge()->getEndDate()) {
                    $aLounge[] = $this->getFormatLounge($lounge->getLounge());
                }
            }
        }
        return $this->view(
            array(
                'user_id' => $user->getId(),
                'groups' => $aGroup,
                'nbGroups' => count($aGroup),
                'hunts' => $aLounge,
                'nbHunts' => count($aLounge),
            ), Codes::HTTP_OK
        );
    }

    /**
     * Retourne les médias d'un utilisateur
     *
     * GET /users/{user}/medias
     *
     * @param \NaturaPass\UserBundle\Entity\User $user
     * @return array
     *
     * @View(serializerGroups={"UserLess", "MediaLess"})
     *
     * @ParamConverter("user", class="NaturaPassUserBundle:User")
     */
    public
    function getUserMediasAction(User $user)
    {
        $this->authorize();
        return $this->view(array('medias' => $user->getMedias()), Codes::HTTP_OK);
    }

    /**
     * Retourne les salons d'un utilisateur
     *
     * GET /users/{user}/lounges?limit=10&offset=0
     *
     * @deprecated Utiliser getLoungesOwningAction()
     *
     * @View(serializerGroups={"UserLess", "LoungeLess", "LoungeUserLess", "LoungeSubscribers", "GeolocationDetail"})
     *
     * @param \NaturaPass\UserBundle\Entity\User $user
     * @param Request $request
     *
     * @ParamConverter("user", class="NaturaPassUserBundle:User")
     *
     * @return \FOS\RestBundle\View\View
     */
    public
    function getUserLoungesAction(User $user, Request $request)
    {
        $this->authorize($user);
        $limit = $request->query->get('limit', 10);
        $offset = $request->query->get('offset', 0);
        $lounges = $user->getLounges(
            array(
                LoungeUser::ACCESS_INVITED,
                LoungeUser::ACCESS_RESTRICTED,
                LoungeUser::ACCESS_DEFAULT,
                LoungeUser::ACCESS_ADMIN
            )
        );
        $views = array();
        $array = array();
        foreach ($lounges as $lounge) {
            $subscriberAccess = $lounge->getSubscriberAccess($user);
            $view = array(
                'lounge' => $lounge,
                'subscriberAccess' => $subscriberAccess,
                'photo' => $this->getBaseUrl() . ($lounge->getPhoto() ? $lounge->getPhoto()->getResize() : $this->getAssetHelper()->getUrl('/img/interface/default-media.jpg'))
            );
            if ($subscriberAccess == LoungeUser::ACCESS_ADMIN) {
                $view = array_merge(
                    $view, array(
                        'participation' => $lounge->isSubscriberParticipation($user),
                        'isAdmin' => $lounge->isAdmin($user),
                        'nbWaiting' => $lounge->isWaitingValidation($user),
                    )
                );
            } else {
                if ($subscriberAccess == LoungeUser::ACCESS_DEFAULT) {
                    $view = array_merge(
                        $view, array(
                            'participation' => $lounge->isSubscriberParticipation($user),
                            'isAdmin' => $lounge->isAdmin($user),
                        )
                    );
                }
            }
            array_push($array, $view);
        }
        $views['lounges'] = array_splice($array, $offset, $limit);
        return $this->view($views, Codes::HTTP_OK);
    }

    /**
     * Recherche si un email est déjà utilisé en base ou non
     *
     * GET /users/{email}/emailisunique
     *
     * @param string $email
     * @return array
     *
     * @View()
     */
    public
    function getUserEmailisuniqueAction($email)
    {
        $user = $this->getDoctrine()->getManager()->getRepository('NaturaPassUserBundle:User')->findOneByEmail($email);
        return $this->view(
            array(
                'unique' => is_object($user)
            ), Codes::HTTP_OK
        );
    }

    /**
     * Retourne l'ensemble des devices utilisés par un utilisateur
     *
     * GET /user/devices
     *
     */
    public
    function getUserDevicesAction()
    {
        $this->authorize();
        $result = $this->getUser()->getDevices();
        $devices = array();
        foreach ($result as $device) {
            $devices[] = $this->getFormatDevice($device);
        }
        return $this->view(array('devices' => $devices), Codes::HTTP_OK);
    }

    /**
     * Supprime l'appareil ciblé d'un utilisateur (doit être appelé avant la déconnexion de l'utilisateur)
     *
     * DELETE /users/{type}/devices/{identifier}
     *
     * @var string type         Le type d'appareil (ios ou android)
     * @var string identifier   L'identifiant appareil
     *
     * @return \FOS\RestBundle\View\View
     * @throws HttpException
     */
    public
    function deleteUserDeviceAction($type, $identifier)
    {
        $this->authorize();
        $manager = $this->getDoctrine()->getManager();

        $type = ($type == 'ios' ? Device::IOS : Device::ANDROID);

        $device = $manager->getRepository('NaturaPassUserBundle:Device')->findOneBy(
            array(
                'type' => $type,
                'identifier' => $identifier
            )
        );

        if ($device instanceof Device) {
            $userDevice = $manager->getRepository('NaturaPassUserBundle:UserDevice')->findOneBy(
                array(
                    'device' => $device,
                    'owner' => $this->getUser()
                )
            );

            if ($userDevice instanceof UserDevice) {
                $manager->remove($userDevice);
                $manager->flush();
            }

            $this->getRedisService()->removeSession();
        } else {
//            throw new HttpException(Codes::HTTP_NOT_FOUND, $this->message('errors.user.device.nonexistent'));
        }

        return $this->view($this->success(), Codes::HTTP_OK);
    }

    /**
     * Ajoute une adresse à un utilisateur
     *
     * POST /users/addresses
     *
     * @param Request $request
     * @return \FOS\RestBundle\View\View
     *
     * JSON lié
     * {
     *      "address": {
     *          "title":        "Description",
     *          "latitude":     43.3232,
     *          "longitude":    4.3392,
     *          "address":      "La bas !",
     *          "altitude":     "72.4", (optionnel)
     *          "favorite":     true (optionnel)
     *      }
     * }
     */
    public
    function postUserAddressAction(Request $request)
    {
        $this->authorize();
        $form = $this->createForm(
            new UserAddressFormType($this->getSecurityTokenStorage(), $this->container), new UserAddress(), array('csrf_protection' => false)
        );
        $handler = new UserAddressFormHandler($form, $request, $this->getDoctrine()->getManager());
        if ($address = $handler->process()) {
            $addressVerif = $this->getDoctrine()->getManager()->getRepository('NaturaPassUserBundle:UserAddress')->findOneBy(
                array(
                    'owner' => $this->getUser(),
                    'favorite' => true
                )
            );
            if (!is_object($addressVerif)) {
                $this->putUserAddressFavoriteAction($address, 1);
            }
            return $this->view(array('address' => $this->getFormatAddress($address)), Codes::HTTP_CREATED);
        }
        return $this->view($form->getErrors(true), Codes::HTTP_BAD_REQUEST);
    }

    /**
     * Modifie une adresse utilisateur
     *
     * PUT /users/{address}/address
     *
     * @param Request $request
     * @param UserAddress $address
     *
     * @return \FOS\RestBundle\View\View
     *
     * JSON lié
     * {
     *      "address": {
     *          "title":        "Description",
     *          "latitude":     43.3232,
     *          "longitude":    4.3392,
     *          "address":      "La bas !",
     *          "altitude":     "72.4", (optionnel)
     *          "favorite":     true (optionnel)
     *      }
     * }
     *
     * @ParamConverter("address", class="NaturaPassUserBundle:UserAddress")
     */
    public
    function putUserAddressAction(Request $request, UserAddress $address)
    {
        $this->authorize();
        $form = $this->createForm(
            new UserAddressFormType($this->getSecurityTokenStorage(), $this->container), $address, array('csrf_protection' => false, 'method' => 'PUT')
        );

        $handler = new UserAddressFormHandler($form, $request, $this->getDoctrine()->getManager());

        if ($address = $handler->process()) {
            return $this->view($this->success(), Codes::HTTP_NO_CONTENT);
        }

        return $this->view($form->getErrors(true), Codes::HTTP_BAD_REQUEST);
    }

    /**
     * Mets une adresse en favorite
     *
     * PUT /api/users/{address}/addresses/{favorite}/favorite
     *
     * @param UserAddress $address
     * @param integer $favorite
     *
     * @return \FOS\RestBundle\View\View
     *
     * @ParamConverter("address", class="NaturaPassUserBundle:UserAddress")
     */
    public
    function putUserAddressFavoriteAction(UserAddress $address, $favorite)
    {
        $this->authorize($address->getOwner());
        $manager = $this->getDoctrine()->getManager();
        $addresses = $this->getUser()->getAddresses();
        foreach ($addresses as $a) {
            $a->setFavorite(false);
            $manager->persist($a);
        }
        $address->setFavorite($favorite);
        $manager->persist($address);
        $manager->flush();
        return $this->view($this->success(), Codes::HTTP_NO_CONTENT);
    }

    /**
     * Ajoute un utilisateur à la base de données
     *
     * POST /users
     *
     * Content-Type: form-data
     *      user[courtesy] = 0 => Indéfini, 1 => Monsieur, 2 => Madame
     *      user[lastname] = "VALOT"
     *      user[firstname] = "Vincent"
     *      user[email] = "v.valot@e-conception.fr"
     *      user[password] = "1234"
     *      user[photo][file] = Données de fichier
     *      user[avatar] = ID avatar
     *      user[facebook_id] = ID Facebook
     *
     *      device[identifier] = Identifiant appareil
     *      device[type] = [ios|android]
     *      device[name] = Apple iPhone
     *      device[authorized] = 1
     *
     * Le mot de passe doit être envoyé crypté
     *
     * @param Request $request
     *
     * @return \FOS\RestBundle\View\View
     *
     * @throws HttpException
     */
    public
    function postUserAction(Request $request)
    {
        $user = $this->getDoctrine()->getManager()->getRepository('NaturaPassUserBundle:User')->findByEmail(
            $request->request->get('user[email]', false, true)
        );
        if ($user) {
            throw new HttpException(Codes::HTTP_BAD_REQUEST, $this->message('errors.user.email'));
        }
        $dataDevice = $request->request->get('device', false);
        $request->request->remove('device');
        if ($avatar = $request->request->get('user[avatar]', false, true)) {
            $request->files->replace(
                array(
                    'user' => array(
                        'photo' => array(
                            'file' => new File(__DIR__ . '/../../../../../web/img/avatars/avatar-' . $avatar . '.png', 'avatar-' . $avatar . '.png')
                        )
                    )
                )
            );
            $params = $request->request->all();
            unset($params['user']['avatar']);
            $request->request->replace($params);
        }
        $manager = $this->getDoctrine()->getManager();
        $form = $this->createForm(
            new RegistrationFormType($this->get('security.token_storage')), new User(), array('csrf_protection' => false)
        );
        $formHandler = new RegistrationFormHandler($form, $request, $manager);
        if ($process = $formHandler->process()) {
            foreach ($manager->getRepository('NaturaPassUserBundle:PaperModel')->findAll() as $papermodel) {
//                $papermodel = new PaperModel();
                $paper = new PaperParameter();
                $paper->setOwner($process);
                $paper->setName($papermodel->getName());
                $paper->setType($papermodel->getType());
                $paper->setDeletable(PaperParameter::NO_DELETABLE);
                $manager->persist($paper);
                $manager->flush();
            }
            $invitations = $manager->getRepository('NaturaPassUserBundle:Invitation')->findBy(array(
                'email' => $process->getEmail(),
                'state' => Invitation::INVITATION_SENT,
            ));

            foreach ($invitations as $invite) {
                $userFriend = new UserFriend();
                $userFriend->setUser($invite->getUser())
                    ->setFriend($process)
                    ->setState(UserFriend::ASKED)
                    ->setType(UserFriend::TYPE_FRIEND);
                $invite->setState(Invitation::INVITATION_INSCRIPTION_SUCCESS);
                $manager->persist($userFriend);
                $manager->persist($invite);

                $this->getNotificationService()->queue(
                    new UserFriendshipAskedNotification($process), $process, $invite->getUser()
                );
            }
            if (is_array($dataDevice)) {
                $device = $manager->getRepository('NaturaPassUserBundle:Device')->findOneBy(
                    array(
                        'type' => $dataDevice['type'] === 'ios' ? Device::IOS : Device::ANDROID,
                        'identifier' => $dataDevice['identifier']
                    )
                );
                if (!$device instanceof Device) {
                    $device = new Device();
                    $device->setIdentifier($dataDevice['identifier'])
                        ->setType($dataDevice['type'] === 'ios' ? Device::IOS : Device::ANDROID)
                        ->setName($dataDevice['name']);
                    $manager->persist($device);
                    $manager->flush();
                } else {
                    $userDevices = $manager->getRepository('NaturaPassUserBundle:UserDevice')->findOneBy(
                        array(
                            'device' => $device,
                        )
                    );
                    foreach ($userDevices as $userDevice) {
                        $manager->remove($userDevice);
                        $manager->flush();
                    }
                }
                $userDevice = new UserDevice();
                $userDevice->setDevice($device)
                    ->setOwner($process)
                    ->setVerified(true)
                    ->setAuthorized($dataDevice['authorized']);
                $manager->persist($userDevice);
                $manager->flush();
            }
            $message = \Swift_Message::newInstance()
                ->setContentType("text/html")
                ->setSubject(
                    $this->get('translator')->trans('user.register_without_confirmation.subject', array(), $this->container->getParameter("translation_name") . 'email')
                )
                ->setFrom($this->get('translator')->trans('user.register_without_confirmation.from', array(), $this->container->getParameter("translation_name") . 'email'))
                ->setTo($process->getEmail())
                ->addBcc($this->container->getParameter("email_bcc"))
                ->setBody(
                    $this->get('templating')->render(
                        'NaturaPassEmailBundle:User:register_api.html.twig', array(
                            'fullname' => $process->getFullName()
                        )
                    )
                );
            $this->get('mailer')->send($message);
            if ($this->container->getParameter("application") == "Naturapass") {
                $request = new PostRequest('http://webmarketing.e-conception.fr/inscr/naturapass/register.php');
                $request->setData('email', $process->getEmail())
                    ->setData('nom', $process->getLastname())
                    ->setData('prenom', $process->getFirstname())
                    ->setData('id_groupe', '4011');
                $request->send();
            }
            return $this->view(array('user_id' => $process->getId()), Codes::HTTP_CREATED);
        }

        return $this->view(array('errors' => $form->getErrors(true, false)), Codes::HTTP_BAD_REQUEST);
    }

    /**
     * Ajoute un média utilisateur
     *
     * POST /users/media
     *
     * @param Request $request
     *
     * @return \FOS\RestBundle\View\View
     *
     * @View(serializerGroups={"UserLess"})
     *
     * @deprecated Will be deleted in API v2, use postUserProfilePicture instead
     */
    public
    function postUserMediaAction(Request $request)
    {
        $this->authorize();
        if (!$request->files->has('usermedia[file]', false, true) && $path = $this->get('session')->get(
                'upload_handler/user.upload'
            )
        ) {
            $files = array(
                'usermedia' => array(
                    'file' => new File($path, uniqid())
                )
            );
            $request->files->replace($files);
        }
        $form = $this->createForm(
            new UserMediaType(), new UserMedia(), array('csrf_protection' => false)
        );
        $handler = new UserMediaHandler($form, $request, $this->getDoctrine()->getManager());
        if ($process = $handler->process()) {
            $this->get('session')->remove('upload_handler/user.upload');
            return $this->view($this->success(), Codes::HTTP_CREATED);
        }
        return $this->view($form->getErrors(true), Codes::HTTP_BAD_REQUEST);
    }

    /**
     * Ajoute une nouvelle entrée de géolocalisation pour l'utilisateur
     *
     * POST /users/geolocations
     *
     * JSON
     * {
     *      "geolocation": {
     *          "latitude": "45.75",
     *          "longitude": "4.85",
     *          "altitude": "230",
     *          "address": "Adresse au format Google"
     *      }
     * }
     *
     *
     * @param Request $request
     *
     * @return \FOS\RestBundle\View\View
     * @throws HttpException
     */
    public
    function postUserGeolocationAction(Request $request)
    {
        $this->authorize();
        if ($params = $request->request->get('geolocation', false)) {
            $verif = ($params['longitude'] == 0 && $params['latitude'] == 0) ? false : true;
            if (isset($params['longitude'], $params['latitude']) && $verif) {
                $user = $this->getUser();
                $geolocation = new Geolocation();
                $geolocation->setLatitude($params['latitude'])
                    ->setLongitude($params['longitude'])
                    ->setAltitude($request->request->get('geolocation[altitude]', '', true))
                    ->setAddress(SecurityUtilities::sanitize($request->request->get('geolocation[address]', '', true)));
                $em = $this->getDoctrine()->getManager();
                $user->addGeolocation($geolocation);
                $em->persist($user);
                $em->flush();
                $subscribes = $user->getLoungeSubscribes();
                foreach ($subscribes as $subscriber) {
                    if ($subscriber->getLounge()->getGeolocation() && $subscriber->getParticipation() == LoungeUser::PARTICIPATION_YES) {
                        $data = array(
                            'user' => array(
                                'fullname' => $user->getFullName(),
                                'usertag' => $subscriber->getUser()->getUsertag(),
                                'profilepicture' => $user->getProfilePicture() ? $user->getProfilePicture()->getThumb() : $this->getAssetHelper()->getUrl('img/default-avatar.jpg'),
                            ),
                            'geolocation' => array(
                                'latitude' => $geolocation->getLatitude(),
                                'longitude' => $geolocation->getLongitude(),
                                'created' => $geolocation->getCreated()->format(\DateTime::ATOM)
                            )
                        );

                        $this->sendSocketEvent(
                            'api-lounge:subscriber-geolocation', array('lounge' => $subscriber->getLounge()->getLoungetag(), 'data' => $data)
                        );

                        $lounge = $subscriber->getLounge();
                        $loungeSubscribers = $lounge->getSubscribers(array(LoungeUser::ACCESS_DEFAULT, LoungeUser::ACCESS_ADMIN), true);
                        $loungeSubscribers->removeElement($this->getUser());
                        /*                        $this->getNotificationService()->generate(
                          'lounge.geolocation.user', $this->getUser(), $loungeSubscribers->toArray(), array('lounge' => $lounge->getName()), array(), $lounge->getId()
                          ); */
                    }
                }
                return $this->view($this->success(), Codes::HTTP_CREATED);
            }
        }
        throw new HttpException(Codes::HTTP_BAD_REQUEST, $this->message('errors.user.geolocation'));
    }

    /**
     * Enregistre les demande d'invitation en BDD
     *
     * POST /users/invitations
     *
     * JSON
     *  {
     *      "emails": "fail2ban@e-conception.fr;test@e-conception.fr;test2@e-conception.fr",
     *      "body": "invitation email content"
     *  }
     *
     * @param Request $request
     *
     * @View()
     *
     * @return \FOS\RestBundle\View\View
     */
    public
    function postUserInvitationAction(Request $request)
    {
        $this->authorize();
        $request = $request->request;
        if ($emails = explode(";", $request->get('emails', '', true))) {
            $manager = $this->getDoctrine()->getManager();
            $repo = $manager->getRepository('NaturaPassUserBundle:User');
            $hostUser = $thisUser = $this->getUser();
            $userEmail = $hostUser->getEmail();
            $email_body = $request->get('body', '', true);

            $errors = array();
            $validator = $this->container->get('validator');

            $constraints = array(
                new \Symfony\Component\Validator\Constraints\Email(),
                new \Symfony\Component\Validator\Constraints\NotBlank()
            );

            foreach ($emails as $email) {
                $user = $repo->findOneBy(array(
                    'email' => $email
                ));

                $error = $validator->validateValue($email, $constraints);

                // l'utilisateur ne peut pas s'auto-envoyer une notification ou un mail, non il ne peut pas le renard
                if ($email != $userEmail && count($error) == 0) {
                    if ($user) {
                        // si le contact est deja un utitlisateur
                        // envoi d'une notifiactin a l'utilisateur
                        $repoFriend = $manager->getRepository('NaturaPassUserBundle:UserFriend');
                        $userFriend = $repoFriend->findOneBy(array(
                            'user' => $thisUser,
                            'friend' => $user
                        ));
                        if (is_null($userFriend) || !$userFriend) {
                            $userFriend = new UserFriend();
                            $userFriend->setUser($thisUser)
                                ->setFriend($user)
                                ->setState(UserFriend::ASKED)
                                ->setType(UserFriend::TYPE_FRIEND);
                            $manager->persist($userFriend);
                            $manager->flush();
                            $this->getNotificationService()->queue(
                                new UserFriendshipAskedNotification($user), $user
                            );
                            $message = \Swift_Message::newInstance()
                                ->setContentType("text/html")
                                ->setSubject($this->get('translator')->trans('invitation.friend.subject', array('%fullname%' => $hostUser->getFullName()), $this->container->getParameter("translation_name") . 'email'))
                                ->setFrom($this->get('translator')->trans('invitation.friend.from', array(), $this->container->getParameter("translation_name") . 'email'))
                                // ->setFrom($hostUser->getEmail())
                                ->setTo($email)
                                ->addBcc($this->container->getParameter("email_bcc"))
                                ->setBody($this->renderView('NaturaPassEmailBundle:User:friend-email.html.twig', array(
                                    'user_fullname' => $user->getFullName(),
                                    'fullname' => $hostUser->getFullName(),
                                    'user_tag' => $thisUser->getUserTag(),
                                    'email_body' => $email_body
                                )));
                            $this->get('mailer')->send($message);
                        }
                    } else {
                        // creation et sauvegarde entite Invitation
                        $repository = $manager->getRepository('NaturaPassUserBundle:Invitation');
                        $invitation = $repository->findOneBy(array(
                            'email' => $email,
                            'user' => $thisUser
                        ));
                        if (!$invitation) {
                            $invitation = new Invitation();
                        }
                        $invitation->setEmail($email);
                        $invitation->setUser($thisUser);
                        $manager->persist($invitation);
                        $manager->flush();
                        //envoi du mail
                        $message = \Swift_Message::newInstance()
                            ->setContentType("text/html")
                            ->setSubject($this->get('translator')->trans('invitation.new.subject', array('%fullname%' => $hostUser->getFullName()), $this->container->getParameter("translation_name") . 'email'))
                            ->setFrom($this->get('translator')->trans('invitation.new.from', array(), $this->container->getParameter("translation_name") . 'email'))
                            ->setTo($email)
                            ->addBcc($this->container->getParameter("email_bcc"))
                            ->setBody($this->renderView('NaturaPassEmailBundle:User:invitation-email.html.twig', array(
                                'user_fullname' => $hostUser->getFullName(),
                                'email' => $email,
                                'email_body' => $email_body
                            )));
                        $this->get('mailer')->send($message);
                    }
                }
            }
            return $this->view($this->success(), Codes::HTTP_CREATED);
        }
        throw new HttpException(Codes:: HTTP_BAD_REQUEST, $this->message('errors.parameters'));
    }

    /**
     * Ajoute une demande d'amitité entre deux utilisateurs
     *
     * POST /users/{receiver}/friendships/asks
     *
     * @param \NaturaPass\UserBundle\Entity\User $receiver
     *
     * @ParamConverter("receiver", class="NaturaPassUserBundle:User")
     *
     * @return \FOS\RestBundle\View\View
     * @throws HttpException
     */
    public
    function postUserFriendshipAskAction(User $receiver)
    {
        $this->authorize();

        $manager = $this->getDoctrine()->getManager();

        if ($this->getUser() == $receiver) {
            throw new HttpException(Codes::HTTP_BAD_REQUEST, $this->message('errors.user.friendship.self'));
        }

        if (!$this->getUser()->hasFriendshipWith($receiver, array(UserFriend::CONFIRMED, UserFriend::ASKED, UserFriend::REJECTED))) {
            $userFriend = new UserFriend();
            $userFriend->setUser($this->getUser())
                ->setFriend($receiver)
                ->setState(UserFriend::ASKED);
            $manager->persist($userFriend);
            $manager->flush();

            $this->getNotificationService()->queue(
                new UserFriendshipAskedNotification($receiver), $receiver
            );

            $this->getEmailService()->generate(
                'invitation.friend', array('%fullname%' => $this->getUser()->getFullName()), array($receiver), 'NaturaPassEmailBundle:User:friend-email.html.twig', array(
                    'user_fullname' => $receiver->getFullName(),
                    'fullname' => $this->getUser()->getFullName(),
                    'user_tag' => $this->getUser()->getUserTag()
                )
            );

            $manager->getRepository('NaturaPassGraphBundle:Recommendation')->deleteRecommendationBetween($this->getUser(), $receiver);
            return $this->view(
                array(
                    'friendship' => array(
                        'way' => User::USERFRIENDWAY_USERTOFRIEND,
                        'state' => $userFriend->getState()
                    )), Codes::HTTP_OK
            );
        } else {
            if ($data = $this->getUser()->hasFriendshipWith($receiver, array(UserFriend::REJECTED))) {
                $friendship = $data[1];
                $friendship->setState(UserFriend::ASKED);
                $manager->persist($friendship);
                $manager->flush();

                $this->getEmailService()->generate(
                    'invitation.friend', array('%fullname%' => $this->getUser()->getFullName()), array($receiver), 'NaturaPassEmailBundle:User:friend-email.html.twig', array(
                        'user_fullname' => $receiver->getFullName(),
                        'fullname' => $this->getUser()->getFullName(),
                        'user_tag' => $this->getUser()->getUserTag()
                    )
                );
                return $this->view(
                    array('friendship' => array('way' => $data[0], 'state' => $friendship->getState())), Codes::HTTP_OK
                );
            }
        }
        throw new HttpException(Codes:: HTTP_BAD_REQUEST, $this->message('errors.user.friendship.already'));
    }

    /**
     * Envoie d'un email en cas de problème
     *
     * POST /users/problems
     *
     * JSON lié:
     * {
     *      "email": {
     *          "body": "Problème rencontré"
     *      }
     * }
     *
     * @param Request $request
     *
     * @throws HttpException
     *
     * @return \FOS\RestBundle\View\View
     *
     */
    public
    function postUserProblemAction(Request $request)
    {
        $this->authorize();
        $request = $request->request;
        $body = $request->get('email[body]', false, true);
        if ($body && strlen($body) > 0) {
            $message = \Swift_Message::newInstance()
                ->setContentType("text/html")
                ->setSubject('Problème NaturaPass')
                ->setFrom($this->getUser()->getEmail())
                ->addTo('bug@naturapass.com')
                ->addTo($this->container->getParameter("email_to"))
                ->addBcc($this->container->getParameter("email_bcc"))
                ->setBody(
                    $this->renderView('NaturaPassEmailBundle:Main:report-problem.html.twig', array('body' => $body))
                );
            $this->get('mailer')->send($message);
            return $this->view($this->success(), Codes::HTTP_CREATED);
        }
        throw new HttpException(Codes:: HTTP_BAD_REQUEST, $this->message('errors.user.empty.problem'));
    }

    /**
     * Envoie d'un email de contact
     *
     * POST /users/contacts
     *
     * JSON lié:
     * {
     *      "email": {
     *          "body": "Problème rencontré"
     *      }
     * }
     *
     * @param Request $request
     *
     * @throws HttpException
     *
     * @return \FOS\RestBundle\View\View
     *
     */
    public
    function postUserContactAction(Request $request)
    {
        $this->authorize();
        $request = $request->request;
        $body = $request->get('email[body]', false, true);
        if ($body && strlen($body) > 0) {
            $message = \Swift_Message::newInstance()
                ->setContentType("text/html")
                ->setSubject('Contact NaturaPass')
                ->setFrom($this->getUser()->getEmail())
                ->setTo($this->container->getParameter("email_to"))
                ->addBcc($this->container->getParameter("email_bcc"))
                ->setBody(
                    $this->renderView('NaturaPassEmailBundle:Main:contact-naturapass.html.twig', array('body' => $body))
                );
            $this->get('mailer')->send($message);
            return $this->view($this->success(), Codes::HTTP_CREATED);
        }
        throw new HttpException(Codes:: HTTP_BAD_REQUEST, $this->message('errors.user.empty.problem'));
    }

    /**
     * Envoie d'un email de signalementd d'abus
     *
     * POST /users/reportabuses
     *
     * JSON lié:
     * {
     *      "email": {
     *          "body": "Problème rencontré"
     *      }
     * }
     *
     * @param Request $request
     *
     * @throws HttpException
     *
     * @return \FOS\RestBundle\View\View
     *
     */
    public
    function postUserReportabuseAction(Request $request)
    {
        $this->authorize();
        $request = $request->request;
        $body = $request->get('email[body]', false, true);
        if ($body && strlen($body) > 0) {
            $message = \Swift_Message::newInstance()
                ->setContentType("text/html")
                ->setSubject("Signalement d'un abus " . $this->container->getParameter("application"))
                ->setFrom($this->getUser()->getEmail())
                ->setTo($this->container->getParameter("email_to"))
                ->addBcc($this->container->getParameter("email_bcc"))
                ->setBody(
                    $this->renderView('NaturaPassEmailBundle:Main:report-abuse.html.twig', array('body' => $body))
                );
            $this->get('mailer')->send($message);
            return $this->view($this->success(), Codes::HTTP_CREATED);
        }
        throw new HttpException(Codes:: HTTP_BAD_REQUEST, $this->message('errors.user.empty.problem'));
    }

    /**
     * Mets le statut de lecture de la notification à read
     *
     * PUT  /users/{notification}/read/notification
     *
     * @param AbstractNotification $notification
     * @return \FOS\RestBundle\View\View
     * @throws \Symfony\Component\HttpKernel\Exception\HttpException
     *
     * @ParamConverter("notification", class="NaturaPassNotificationBundle:AbstractNotification")
     */
    public
    function putUserReadNotificationAction(AbstractNotification $notification)
    {
        $this->authorize();

        $manager = $this->getDoctrine()->getManager();
        $receiver = $manager->getRepository('NaturaPassNotificationBundle:NotificationReceiver')->findOneBy(
            array(
                'notification' => $notification->getId(),
                'receiver' => $this->getUser()->getId()
            )
        );

        if ($receiver instanceof NotificationReceiver) {
            $receiver->setState(NotificationReceiver::STATE_READ);

            $manager->persist($receiver);
            $manager->flush();

            return $this->view($this->success(), Codes::HTTP_OK);
        }

        throw new HttpException(Codes:: HTTP_BAD_REQUEST, $this->message('errors.notification.notreceived'));
    }

    /**
     * Modifie un utilisateur
     *
     * PUT /users
     *
     * Content-Type: form-data
     *      user[courtesy] = 0 => Indéfini, 1 => Monsieur, 2 => Madame
     *      user[lastname] = "VALOT"
     *      user[firstname] = "Vincent"
     *      user[email] = "v.valot@e-conception.fr"
     *      user[password][old] = "Mot de passe encodé"
     *      user[password][new] = "Mot de passe encodé"
     *      user[password][confirmation] = "Mot de passe encodé"
     *      user[photo][file]
     *
     * @View(serializerGroups={"UserLess"})
     *
     * @param Request $request
     *
     * @return \FOS\RestBundle\View\View
     * @throws HttpException
     */
    public
    function putUsersAction(Request $request)
    {
        $this->authorize();
        $old = $request->request->get('user[password][old]', false, true);
        $new = $request->request->get('user[password][new]', false, true);
        $confirmation = $request->request->get('user[password][confirmation]', false, true);
        $params = $request->request->get('user');
        if (isset($params ['password']) && !is_array($params ['password']) && $this->getUser()->getPassword() !== $params['password']
        ) {
            throw new HttpException(Codes:: HTTP_BAD_REQUEST, $this->message("errors.parameters"));
        } else if (isset($params ['password']) && is_array($params['password'])) {
            unset($params['password']);
        } else {
            $params['password'] = $this->getUser()->getPassword();
        }
        if (!isset($params['facebook_id'])) {
            $params['facebook_id'] = $this->getUser()->getFacebookId();
        }
        if ($old && $new && $confirmation) {
            if ($this->getUser()->getPassword() === $old) {
                if ($new === $confirmation) {
                    $params['password'] = $new;
                } else {
                    throw new HttpException(Codes:: HTTP_BAD_REQUEST, $this->message("errors.user.password.both"));
                }
            } else {
                throw new HttpException(Codes:: HTTP_BAD_REQUEST, $this->message("errors.user.password.old"));
            }
        }
        if ($email = $request->request->get('user[email]', false, true) && $request->request->get(
                'user[email]', false, true) !== $this->getUser()->getEmail()
        ) {
            if ($this->getDoctrine()->getManager()->getRepository('NaturaPassUserBundle:User')->findOneByEmail(
                $email
            )
            ) {
                throw new HttpException(Codes:: HTTP_BAD_REQUEST, $this->message("errors.user.email"));
            }
        }
        $request->request->replace(array('user' => $params));
        if ($avatar = $request->request->get('user[avatar]', false, true)) {
            $request->files->replace(
                array(
                    'user' => array(
                        'photo' => array(
                            'file' => new File(__DIR__ . '/../../../../../web/img/avatars/avatar-' . $avatar . '.png', 'avatar-' . $avatar . '.png')
                        )
                    )
                )
            );
            $params = $request->request->all();
            unset($params['user']['avatar']);
            $request->request->replace($params);
        }
        $form = $this->createForm(
            new RegistrationFormType($this->getSecurityTokenStorage()), $this->getUser(), array('csrf_protection' => false, 'method' => 'PUT')
        );

        $form->setData($this->getUser());
        $handler = new RegistrationFormHandler($form, $request, $this->getDoctrine()->getManager());

        if ($process = $handler->process()) {
            return $this->view($this->success(), Codes::HTTP_OK);
        }
        return $this->view($form->getErrors(true), Codes::HTTP_BAD_REQUEST);
    }

    /**
     * Modifie la photo de profil d'un utilisateur
     *
     * POST /users/profiles/pictures
     *
     * Content-Type: form-data
     *      user[photo] => Fichier en données fichiers
     *
     * @param Request $request
     * @return \FOS\RestBundle\View\View
     */
    public
    function postUserProfilePictureAction(Request $request)
    {
        $this->authorize();
        if ($file = $request->files->get('user[photo]', false, true)) {
            $manager = $this->getDoctrine()->getManager();
            while ($other = $this->getUser()->getProfilePicture()) {
                $other->setState(UserMedia::STATE_NOTHING);
                $manager->persist($other);
            }
            $media = new UserMedia();
            $media->setFile($file)
                ->setState(UserMedia::STATE_PROFILE_PICTURE)
                ->setOwner($this->getUser());
            $manager->persist($media);
            $manager->flush();
            return $this->view($this->success(), Codes::HTTP_OK);
        }
        throw new HttpException(Codes:: HTTP_BAD_REQUEST, $this->message('errors.parameters'));
    }

    /**
     * Modifie les parametres d'un utilisateur
     *
     * PUT /users/parameters
     *
     * Les données doivent être formatées sous la forme
     * {
     *      "parameters": {
     *          "publication_sharing":  {
     *              "share": 3
     *          }
     *      }
     * }
     *
     * @param Request $request
     *
     * @return \FOS\RestBundle\View\View
     */
    public
    function putUsersParametersAction(Request $request)
    {
        $this->authorize();
        $form = $this->createForm(
            new ParametersFormType($this->getSecurityTokenStorage()), $this->getUser()->getParameters(), array('csrf_protection' => false, 'method' => 'PUT')
        );

        $handler = new ParametersFormHandler($form, $request, $this->getDoctrine()->getManager());

        if ($parameters = $handler->process()) {
            return $this->view($this->success(), Codes::HTTP_OK);
        }
        return $this->view($form->getErrors(true), Codes::HTTP_BAD_REQUEST);
    }

    /**
     * Change le niveau d'envoi pour un email spécifié, pour l'utilisateur connecté
     *
     * PUT  /users/{model}/emails/{wanted}/wanted
     *
     * @param EmailModel $model
     * @param integer $wanted
     *
     * @ParamConverter("model", class="NaturaPassEmailBundle:EmailModel")
     *
     * @return \FOS\RestBundle\View\View
     */
    public
    function putUsersEmailWantedAction(EmailModel $model, $wanted)
    {
        $this->authorize();

        $parameters = $this->getUser()->getParameters();

        $pref = $parameters->getEmailByType($model->getType());

        if (!$pref instanceof ParametersEmail) {
            $pref = new ParametersEmail();
            $pref->setWanted($wanted)
                ->setEmail($model)
                ->setParameters($parameters);
        }

        $pref->setWanted($wanted);

        $manager = $this->getDoctrine()->getManager();

        $manager->persist($pref);
        $manager->flush();

        return $this->view($this->success(), Codes::HTTP_OK);
    }

    /**
     * Change le niveau d'envoi pour une notification spéficié pour l'utilisateur connecté
     *
     * PUT  /users/{type}/notifications/{wanted}/wanted
     *
     * @param string $type
     * @param integer $wanted
     *
     * @return \FOS\RestBundle\View\View
     */
    public
    function putUsersNotificationWantedAction($type, $wanted)
    {
        $this->authorize();

        $manager = $this->getDoctrine()->getManager();
        $available = array_keys($manager->getMetadataFactory()->getMetadataFor('NaturaPass\NotificationBundle\Entity\AbstractNotification')->discriminatorMap);
        if (in_array($type, $available)) {
            $parameters = $this->getUser()->getParameters();
            $pref = $parameters->getNotificationByType($type);

            if (!$pref instanceof ParametersNotification) {
                $pref = new ParametersNotification();
                $pref->setParameters($parameters)
                    ->setType($type);
            }

            $pref->setWanted($wanted);
            $pref->setObjectID(0);

            $manager->persist($pref);

            $manager->flush();

            return $this->view($this->success(), Codes::HTTP_OK);
        }

        throw new HttpException(Codes::HTTP_BAD_REQUEST, $this->message('errors.user.parameters.notifications.nonexistent'));
    }

    /**
     * Change le niveau d'envoi pour une notification spéficié pour l'utilisateur connecté
     *
     * PUT  /users/{type}/notifications/{wanted}/wanted/{$object}/object
     *
     * @param string $type
     * @param integer $wanted
     *
     * @return \FOS\RestBundle\View\View
     */
    public
    function putUsersNotificationWantedObjectAction($type, $wanted, $object)
    {
        $this->authorize();

        $manager = $this->getDoctrine()->getManager();
        $available = array_keys($manager->getMetadataFactory()->getMetadataFor('NaturaPass\NotificationBundle\Entity\AbstractNotification')->discriminatorMap);
        if (in_array($type, $available)) {
            $parameters = $this->getUser()->getParameters();
            $pref = $parameters->getNotificationByType($type, $object);

            if (!$pref instanceof ParametersNotification) {
                $pref = new ParametersNotification();
                $pref->setParameters($parameters)
                    ->setType($type);
            }

            $pref->setWanted($wanted);
            $pref->setObjectID($object);
            $manager->persist($pref);

            $manager->flush();

            return $this->view($this->success(), Codes::HTTP_OK);
        }

        throw new HttpException(Codes::HTTP_BAD_REQUEST, $this->message('errors.user.parameters.notifications.nonexistent'));
    }

    /**
     * Change le display d'aide sur le site web
     *
     * PUT users/{help}/parameters/help.
     *
     * @param int $help
     * @return \FOS\RestBundle\View\View
     */
    public
    function putUserParametersHelpAction($help)
    {
        $this->authorize();
        $parameters = $this->getUser()->getParameters();
        $parameters->setHelp($help);
        $this->getDoctrine()->getManager()->persist($parameters);
        $this->getDoctrine()->getManager()->flush();
        return $this->view($this->success(), Codes::HTTP_OK);
    }

    /**
     * Change le display d'aide sur le site web
     *
     * PUT users/{friends}/parameters/friends
     *
     * @param int $friends
     * @return \FOS\RestBundle\View\View
     */
    public
    function putUserParametersFriendsAction($friends)
    {
        $this->authorize();
        $parameters = $this->getUser()->getParameters();
        $parameters->setFriends($friends);
        $this->getDoctrine()->getManager()->persist($parameters);
        $this->getDoctrine()->getManager()->flush();
        return $this->view($this->success(), Codes::HTTP_OK);
    }

    /**
     * Change le filtre partage par default du menu
     *
     * PUT users/{sharingFilter}/parameters/sharingfilter
     *
     * @param int $sharingFilter
     * @return \FOS\RestBundle\View\View
     */
    public
    function putUserParametersSharingfilterAction($sharingFilter)
    {
        $this->authorize();
        $parameters = $this->getUser()->getParameters();
        $parameters->setSharingFilter($sharingFilter);
//        $parameters->setGroupFilter(null);
        $this->getDoctrine()->getManager()->persist($parameters);
        $this->getDoctrine()->getManager()->flush();
        return $this->view($this->success(), Codes::HTTP_OK);
    }

    /**
     * Change le filtre groupe par default du menu
     *
     * PUT users/{groupfilter}/parameters/groupfilter
     *
     * @param int $groupFilter
     * @return \FOS\RestBundle\View\View
     */
    public
    function putUserParametersGroupfilterAction($groupFilter)
    {
        $this->authorize();
        $parameters = $this->getUser()->getParameters();
        $groupFil = new ParametersFilter();
        $groupFil->setGroupFilter($groupFilter);
        $groupFil->setParameters($parameters);
        $this->getDoctrine()->getManager()->persist($groupFil);
        $this->getDoctrine()->getManager()->flush();
        return $this->view($this->success(), Codes::HTTP_OK);
    }

    /**
     * Confirme une demande d'amitié envoyée au départ par le receiver
     *
     * PUT /users/{receiver}/friendship/confirm
     *
     * @param \NaturaPass\UserBundle\Entity\User $receiver
     *
     * @ParamConverter("receiver", class="NaturaPassUserBundle:User")
     *
     * @return \FOS\RestBundle\View\View
     * @throws HttpException
     */
    public
    function putUserFriendshipConfirmAction(User $receiver)
    {
        $this->authorize();
        list($way, $userFriend) = $this->getUser()->hasFriendshipWith(
            $receiver, array(UserFriend::ASKED), User::USERFRIENDWAY_FRIENDTOUSER
        );
        if ($userFriend) {
            $manager = $this->getDoctrine()->getManager();
            $userFriend->setState(UserFriend::CONFIRMED);
            $manager->persist($userFriend);
            $manager->flush();
            $manager->getRepository('NaturaPassGraphBundle:Recommendation')->deleteRecommendationBetween(
                $this->getUser(), $receiver
            );

            $this->delay(function () use ($receiver) {
                $this->getGraphService()->generateEdge($this->getUser(), $receiver, Edge::FRIENDSHIP_FRIEND);
            });

            $this->getNotificationService()->queue(
                new UserFriendshipConfirmedNotification($receiver), $receiver
            );

            return $this->view(
                array(
                    'friendship' => array(
                        'way' => User::USERFRIENDWAY_FRIENDTOUSER,
                        'state' => $userFriend->getState()
                    )), Codes::HTTP_OK
            );
        }
        throw new HttpException(Codes:: HTTP_BAD_REQUEST, $this->message('errors.user.friendship.nowaiting'));
    }

    /**
     * Change le type d'amitié entre les deux membres
     *
     * PUT /users/{receiver}/friendships
     *
     * JSON
     * {
     *      "type": [1 => Ami, 2 => Connaissance]
     * }
     *
     * @param Request $request
     * @param \NaturaPass\UserBundle\Entity\User $receiver
     *
     * @ParamConverter("receiver", class="NaturaPassUserBundle:User")
     *
     * @return \FOS\RestBundle\View\View
     * @throws HttpException
     */
    public
    function putUserFriendshipAction(Request $request, User $receiver)
    {
        $this->authorize();
        list($way, $userFriend) = $this->getUser()->hasFriendshipWith($receiver, array(UserFriend::CONFIRMED));
        if ($userFriend) {
            $manager = $this->getDoctrine()->getManager();
            if ($request->request->has('type')) {
                $userFriend->setType($request->request->get('type'));
            }
            $manager->persist($userFriend);
            $manager->flush();

            $this->getNotificationService()->queue(
                new UserFriendshipConfirmedNotification($receiver), $receiver
            );

            return $this->view($this->success(), Codes::HTTP_OK);
        }
        throw new HttpException(Codes:: HTTP_BAD_REQUEST, $this->message('errors.user.friendship.nonexistent'));
    }

    /**
     * Rejette une demande d'amitié envoyée au départ par le receiver
     *
     * PUT /users/{receiver_id}/friendship/reject
     *
     * @param \NaturaPass\UserBundle\Entity\User $receiver
     *
     * @ParamConverter("receiver", class="NaturaPassUserBundle:User")
     *
     * @return \FOS\RestBundle\View\View
     * @throws HttpException
     */
    public
    function putUserFriendshipRejectAction(User $receiver)
    {
        list($way, $userFriend) = $this->getUser()->hasFriendshipWith(
            $receiver, array(UserFriend::ASKED), User::USERFRIENDWAY_FRIENDTOUSER
        );
        if ($userFriend) {
            $manager = $this->getDoctrine()->getManager();
            $userFriend->setState(UserFriend::REJECTED);
            $manager->persist($userFriend);
            $manager->flush();
            return $this->view(
                array(
                    'friendship' => array(
                        'way' => User::USERFRIENDWAY_FRIENDTOUSER,
                        'state' => $userFriend->getState()
                    )), Codes::HTTP_OK
            );
        }
        throw new HttpException(Codes:: HTTP_BAD_REQUEST, $this->message('errors.user.friendship.nonexistent'));
    }

    /**
     * Supprime une adresse utilisateur
     *
     * @param UserAddress $address
     *
     * @return \FOS\RestBundle\View\View
     *
     * @ParamConverter("address", class="NaturaPassUserBundle:UserAddress")
     */
    public
    function deleteUserAddressAction(UserAddress $address)
    {
        $this->authorize($address->getOwner());
        $manager = $this->getDoctrine()->getManager();
        $manager->remove($address);
        $manager->flush();
        return $this->view($this->success(), Codes::HTTP_NO_CONTENT);
    }

    /**
     * Supprime une amitié entre deux utilisateurs
     *
     * DELETE /users/{receiver}/friendship
     *
     * @param \NaturaPass\UserBundle\Entity\User $receiver
     *
     * @ParamConverter("receiver", class="NaturaPassUserBundle:User")
     *
     * @return \FOS\RestBundle\View\View
     * @throws HttpException
     */
    public
    function deleteUserFriendshipAction(User $receiver)
    {
        $this->authorize();
        list($way, $userFriend) = $this->getUser()->hasFriendshipWith(
            $receiver, array(UserFriend::CONFIRMED, UserFriend::ASKED)
        );
        if ($userFriend) {
            $manager = $this->getDoctrine()->getManager();
            $this->delay(function () use ($receiver) {
                $this->getGraphService()->deleteEdgeBetween($this->getUser(), $receiver, Edge::FRIENDSHIP_FRIEND);
            });
            $manager->remove($userFriend);
            $manager->flush();
            return $this->view($this->success(), Codes::HTTP_OK);
        }
        throw new HttpException(Codes:: HTTP_BAD_REQUEST, $this->message('errors.user.friendship.nonexistent'));
    }

    /**
     * Supprime un filtre groupe d'un utilisateur
     *
     * DELETE /users/{groupFilter}/parameters/groupfilter
     *
     * @param int $groupFilter
     * @return \FOS\RestBundle\View\View
     * @throws HttpException
     */
    public
    function deleteUserParametersGroupfilterAction($groupFilter)
    {
        $this->authorize();
        $manager = $this->getDoctrine()->getManager();
        $filters = $this->getUser()->getParameters()->getFilters();
        foreach ($filters as $filter) {
            if ($filter->getGroupFilter() == $groupFilter) {
                $manager->remove($filter);
                $manager->flush();
                return $this->view($this->success(), Codes::HTTP_OK);
            }
        }
        throw new HttpException(Codes:: HTTP_BAD_REQUEST, $this->message('errors.user.groupfilter.nonexistent'));
    }

    /**
     * Supprime un filtre de partage d'un utilisateur
     *
     * DELETE /users/{sharingFilter}/parameters/sharingfilter
     *
     * @param int $sharingFilter
     * @return \FOS\RestBundle\View\View
     * @throws HttpException
     */
    public
    function deleteUserParametersSharingfilterAction($sharingFilter)
    {
        $this->authorize();
        $manager = $this->getDoctrine()->getManager();
        $parameters = $this->getUser()->getParameters();
        if ($parameters->getSharingFilter() == $sharingFilter) {
            $parameters->setSharingFilter(null);
            $manager->persist($parameters);
            $manager->flush();
            return $this->view($this->success(), Codes::HTTP_OK);
        }
        throw new HttpException(Codes:: HTTP_BAD_REQUEST, $this->message('errors.user.sharingfilter.nonexistent'));
    }

    /**
     * Supprime un utilisateur de la base de données
     *
     * DELETE /user
     *
     * {
     *      "password": "passwordencodé"
     * }
     *
     * @param Request $request
     * @return \FOS\RestBundle\View\View
     */
    public
    function deleteUserAction(Request $request)
    {
        $this->authorize();
        if ($password = $request->request->get('password', false, true)) {
            $user = $this->getSecurityTokenStorage()->getToken()->getUser();
            if ($user->getPassword() === $password) {
                $entities = array();
                $manager = $this->container->get('doctrine')->getManager();
                $entities = array_merge($entities, $manager->getRepository('NaturaPassMessageBundle:Message')->findByOwner($user));
                $entities = array_merge($entities, $manager->getRepository('NaturaPassUserBundle:Invitation')->findByUser($user));
                $entities = array_merge($entities, $manager->getRepository('NaturaPassLoungeBundle:Lounge')->findByOwner($user));
                $entities = array_merge($entities, $manager->getRepository('NaturaPassGroupBundle:Group')->findByOwner($user));
                $entities = array_merge($entities, $manager->getRepository('NaturaPassGraphBundle:Edge')->findByFrom($user));
                $entities = array_merge($entities, $manager->getRepository('NaturaPassGraphBundle:Edge')->findByTo($user));
                $entities = array_merge($entities, $manager->getRepository('NaturaPassGraphBundle:Recommendation')->findByOwner($user));
                $entities = array_merge($entities, $manager->getRepository('NaturaPassGraphBundle:Recommendation')->findByTarget($user));
                $entities = array_merge($entities, $manager->getRepository('NaturaPassMessageBundle:Message')->findByOwner($user));
                $entities = array_merge($entities, $manager->getRepository('NaturaPassPublicationBundle:Publication')->findByOwner($user));
                $entities = array_merge($entities, $manager->getRepository('NaturaPassPublicationBundle:PublicationAction')->findByUser($user));
                $entities = array_merge($entities, $manager->getRepository('NaturaPassNotificationBundle:AbstractNotification')->findBySender($user));
                foreach ($entities as $entity) {
                    $manager->remove($entity);
                }
                $manager->remove($user);
                $manager->flush();
                return $this->view($this->success(), Codes::HTTP_OK);
            }
        }
        throw new HttpException(Codes::HTTP_BAD_REQUEST);
    }

    /**
     *
     * PUT /v1/user/identifier
     *
     * {
     *   "identifier": "e0366ffd02fdd942a960119e71fce654c2ccf5",
     *   "device": "ios",
     *   "name": "sony",
     * }
     *
     * @param Request $request
     * @return \FOS\RestBundle\View\View
     */
    public
    function putUserIdentifierAction(Request $request)
    {
        $manager = $this->getDoctrine()->getManager();

        $device = $request->request->get('device', null);
        $identifier = $request->request->get('identifier', null);
        $name = urldecode($request->request->get('name', null));
        if ($device && $identifier) {
            $type = $device == 'ios' ? Device::IOS : Device::ANDROID;

            $device = $manager->getRepository('NaturaPassUserBundle:Device')->findOneBy(
                array(
                    'type' => $type,
                    'identifier' => $identifier
                )
            );

            if (!$device instanceof Device) {
                $device = new Device();
                $device->setType($type)
                    ->setIdentifier($identifier)
                    ->setName($name);

                $manager->persist($device);
                $manager->flush();
            }

            foreach ($device->getOwners() as $owner) {
                if ($owner->getOwner()->getId() != $this->getUser()->getId()) {
                    $manager->remove($owner);
                    $manager->flush();
                }
            }

            $userDevice = $device->hasOwner($this->getUser());

            if (!$userDevice instanceof UserDevice) {
                $userDevice = new UserDevice();
                $userDevice->setOwner($this->getUser())
                    ->setDevice($device)
                    ->setVerified(true)
                    ->setAuthorized(true);
            } else {
                $userDevice->setVerified(true)
                    ->setAuthorized(true);
            }

            $manager->persist($userDevice);
            $manager->flush();

            return $this->view($this->success(), Codes::HTTP_OK);
        }
        else{
            throw new HttpException(Codes::HTTP_BAD_REQUEST, $this->message('errors.parameters'));
        }
    }

    /**
     * Envoie d'un email de signalementd d'abus
     *
     * POST /users/report
     *
     * JSON lié:
     * {
     *      "email": {
     *          "body": "Problème rencontré"
     *      }
     * }
     *
     * @param Request $request
     *
     * @throws HttpException
     *
     * @return \FOS\RestBundle\View\View
     *
     */
    public function postUserReportAction(Request $request)
    {
        if($this->getUser())
        {
            // $data = $request->request->get('sendToWebMaster');
            $request = $request->request;
            $body = $request->get('bodycontent', false, true);
            $opt['ssl']['verify_peer'] = FALSE;
            $opt['ssl']['verify_peer_name'] = FALSE;

            $this->get('swiftmailer.mailer.default.transport.real')->setStreamOptions($opt);

            $message = \Swift_Message::newInstance()
                                    ->setContentType("text/html")
                                    ->setSubject("Probleme sur le site " . $this->container->getParameter("application"))
                                    ->setFrom($this->getUser()->getEmail())
                                    ->setTo($this->container->getParameter("email_to"))
                                    ->addBcc($this->container->getParameter("email_bcc"))
                                    ->setBody($this->renderView('NaturaPassEmailBundle:Main:report-problem.html.twig', array(
                                    'body' => $body,
                                )));
                                $this->get('mailer')->send($message);
            return new RedirectResponse($this->container->get('router')->generate('naturapass_main_homepage'));
        }        
        return new RedirectResponse($this->container->get('router')->generate('naturapass_main_homepage'));
    }
}

