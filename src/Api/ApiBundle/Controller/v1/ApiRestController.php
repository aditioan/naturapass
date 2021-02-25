<?php

namespace Api\ApiBundle\Controller\v1;

use Admin\DistributorBundle\Entity\Brand;
use Admin\DistributorBundle\Entity\Distributor;
use Api\ApiBundle\Controller\v2\Serialization\GroupSerialization;
use Api\ApiBundle\Controller\v2\Serialization\MainSerialization;
use Doctrine\ORM\Query\Expr\Join;
use Doctrine\ORM\QueryBuilder;
use FOS\RestBundle\Controller\FOSRestController;
use FOS\RestBundle\Util\Codes;
use NaturaPass\GroupBundle\Entity\Group;
use NaturaPass\GroupBundle\Entity\GroupUser;
use NaturaPass\GroupBundle\Entity\GroupMessage;
use Admin\GameBundle\Entity\Game;
use NaturaPass\LoungeBundle\Entity\Lounge;
use NaturaPass\LoungeBundle\Entity\LoungeMessage;
use NaturaPass\LoungeBundle\Entity\LoungeUser;
use NaturaPass\LoungeBundle\Entity\LoungeNotMember;
use NaturaPass\MediaBundle\Entity\BaseMedia;
use NaturaPass\MessageBundle\Entity\Message;
use NaturaPass\PublicationBundle\Entity\Publication;
use NaturaPass\PublicationBundle\Entity\PublicationComment;
use NaturaPass\UserBundle\Entity\User;
use NaturaPass\UserBundle\Entity\UserMap;
use NaturaPass\UserBundle\Entity\UserAddress;
use NaturaPass\UserBundle\Entity\UserDevice;
use Symfony\Component\HttpKernel\Exception\HttpException;
use NaturaPass\MainBundle\Entity\Sharing;
use NaturaPass\UserBundle\Entity\UserFriend;
use NaturaPass\PublicationBundle\Entity\PublicationAction;
use NaturaPass\UserBundle\Entity\Device;
use Admin\SentinelleBundle\Entity\Card;
use Admin\SentinelleBundle\Entity\Category;
use Admin\AnimalBundle\Entity\Animal;
use Admin\SentinelleBundle\Entity\Receiver;
use Admin\SentinelleBundle\Entity\Zone;
use RMS\PushNotificationsBundle\Message\AndroidMessage;
use RMS\PushNotificationsBundle\Message\iOSMessage;
use ElephantIO\Client as ElephantIOClient;
use ElephantIO\Engine\SocketIO\Version1X as ElephantIOVersion1X;
use NaturaPass\ObservationBundle\Entity\Observation;

/**
 * @author vincentvalot
 */
class ApiRestController extends FOSRestController
{

    const SOCKET_PORT = 3000;

    /**
     * Vérifie si l'utilisateur est bien authentifié
     *
     * @throws HttpException
     *
     * @param mixed $allowed Un utilisateur ou un tableau d'utilisateurs autorisés
     * @param array $roles Role de l'utilisateur (par défault authentifié)
     *
     * @return boolean
     */
    protected function authorize($allowed = null, $roles = array('IS_AUTHENTICATED_FULLY', 'IS_AUTHENTICATED_REMEMBERED'))
    {
        if (!$this->getSecurityTokenStorage()->getToken() || !$this->getUser() instanceof User) {
            throw new HttpException(Codes::HTTP_UNAUTHORIZED, $this->message('codes.401'));
        }

        if ($this->getUser()->getLocked()) {
            throw new HttpException(Codes::HTTP_FORBIDDEN, $this->message('codes.403'));
        }

        if (!$this->getSecurityAuthorization()->isGranted($roles)) {
            throw new HttpException(Codes::HTTP_FORBIDDEN, $this->message('codes.403'));
        }

        if ($allowed instanceof User) {
            if ($this->getUser() != $allowed) {
                throw new HttpException(Codes::HTTP_FORBIDDEN, $this->message('codes.403'));
            }
        } else {
            if (is_array($allowed) && !empty($allowed)) {
                foreach ($allowed as $user) {
                    if ($this->getUser() == $user) {
                        return true;
                    }
                }

                throw new HttpException(Codes::HTTP_FORBIDDEN, $this->message('codes.403'));
            }
        }

        return false;
    }

    /**
     * check Message right
     *
     * @param Message $message
     * @throws \Symfony\Component\HttpKernel\Exception\HttpException
     */
    protected function authorizeMessage(Message $message)
    {
        $authorized = false;

        if ($this->getUser() == $message->getOwner()) {
            $authorized = true;
        }

        if (!$authorized && !$this->isConnected('ROLE_SUPER_ADMIN')) {
            throw new HttpException(Codes::HTTP_FORBIDDEN, $this->message('codes.403'));
        }
    }

    /**
     * Vérifie si un utilisateur est connecté
     *
     * @param array $roles
     *
     * @return bool true si connecté, sinon false
     */
    protected function isConnected($roles = array('IS_AUTHENTICATED_FULLY', 'IS_AUTHENTICATED_REMEMBERED'))
    {
        if ($this->getSecurityAuthorization()->isGranted($roles)) {
            return true;
        }

        return false;
    }

    /**
     * Retourne l'objet de sécurité
     *
     * @return \Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface
     */
    protected function getSecurityTokenStorage()
    {
        return $this->get('security.token_storage');
    }

    /**
     * Retourne l'objet de sécurité
     *
     * @return \Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface
     */
    protected function getSecurityAuthorization()
    {
        return $this->get('security.authorization_checker');
    }

    /**
     * Retourne l'objet de créationd d'asset
     *
     * @return \Symfony\Component\Asset\Packages
     */
    protected function getAssetHelper()
    {
        return $this->get('assets.packages');
    }

    /**
     * @return \Symfony\Bundle\FrameworkBundle\Translation\Translator
     */
    protected function getTranslator()
    {
        return $this->get('translator');
    }

    /**
     * @return string
     */
    protected function getBaseUrl()
    {
        return $this->getRequest()->getScheme() . '://' . $this->getRequest()->getHttpHost();
    }

    /**
     * Retourne le service gérant le graphe
     *
     * @return \NaturaPass\GraphBundle\Component\GraphService
     */
    protected function getGraphService()
    {
        return $this->get('naturapass.graph');
    }

    /**
     * Retourne le service créateur d'email
     *
     * @return \NaturaPass\EmailBundle\Component\EmailService
     */
    protected function getEmailService()
    {
        return $this->get('naturapass.email');
    }

    /**
     * Retourne le service créateur de notifications
     *
     * @return \NaturaPass\NotificationBundle\Component\NotificationService
     */
    protected function getNotificationService()
    {
        return $this->get('naturapass.notification');
    }

    /**
     * Retourne le service de géolocalisation
     *
     * @return \NaturaPass\MainBundle\Component\GeolocationService
     */
    protected function getGeolocationService()
    {
        return $this->get('naturapass.geolocation');
    }

    /**
     * Retourne le service redis
     *
     * @return \NaturaPass\MainBundle\Component\RedisService
     */
    protected function getRedisService()
    {
        return $this->get('naturapass.redis');
    }

    /**
     * Retourne les messages selon l'environnement courant
     *
     * @param string $message
     *
     * @return string
     */
    protected function message($message)
    {
        return $this->container->get('translator')->trans($message, array(), $this->container->getParameter("translation_name") . 'api');
    }

    /**
     * Retourne une données indiquant le succès de l'opération
     *
     * @return array
     */
    protected function success()
    {
        return array('success' => true);
    }

    /**
     * Envoie un message depuis une socket serveur
     *
     * @param string $name Nom de l'événement formaté de la façon suivante: api-[nom fonctionnalité]:[nom evenement] (api-message:incoming)
     * @param array $message Tableau de données envoyées au serveur Node.js
     *
     * Attention à l'objet DateTime qui est envoyé car json_encode le transforme en array plutôt qu'en string
     */
    public static function sendSocketEvent($name, $message)
    {
        $url = 'http://localhost:' . self::SOCKET_PORT;

        try {
            $elephant = new ElephantIOClient(new ElephantIOVersion1X($url));
            $elephant->initialize();

            $elephant->emit($name, $message);

            $elephant->close();
        } catch (\Exception $e) {

        }
    }

    /**
     * @param integer $type type de notification
     * @param array $users receivers
     * @param integer $objectID object
     * @param integer $pushMessage object
     * @param bool $forceSend
     *
     * @return array
     */
    protected function sendPushDevice($type, $users, $objectID, $pushMessage = 1, $forceSend = false)
    {
        $data = array('object_id' => $objectID);
        $content = array();
        $content['%sender%'] = $this->getUser()->getFullName();
        if ($type >= 1 && $type <= 99) {
            $entity = $this->getDoctrine()->getRepository('NaturaPassLoungeBundle:Lounge')->findOneBy(array('id' => $objectID));
            $content['%lounge%'] = $entity->getName();
        } else {
            if ($type >= 100 && $type <= 199) {
                $entity = $this->getDoctrine()->getRepository('NaturaPassPublicationBundle:Publication')->findOneBy(array('id' => $objectID));
            }
        }
        //$send = true;
        switch ($type) {
            case 1:
                $typenotification = 'geolocation';
                $element = 'lounge';
                $data['active'] = true;
                break;
            case 2:
                $typenotification = 'geolocation';
                $element = 'lounge';
                $data['active'] = false;
                break;
            case 3:
                $typenotification = 'geolocation.user';
                $element = 'lounge';
                $data['active'] = true;
                $data['user'] = array(
                    'id' => $this->getUser()->getId(),
                    'fullname' => $this->getUser()->getFullname(),
                    'usertag' => $this->getUser()->getUsertag(),
                    'profilepicture' => $this->getUser()->getProfilePicture() ? $this->getUser()->getProfilePicture()->getThumb() : $this->getAssetHelper()->getUrl('img/default-avatar.jpg')
                );
                $last = $this->getUser()->getLastGeolocation();
                if ($last) {
                    $data['geolocation'] = array(
                        'latitude' => $last->getLatitude(),
                        'longitude' => $last->getLongitude(),
                        'created' => $last->getCreated()->format(\DateTime::ATOM)
                    );
                }
                break;
            case 4:
                $typenotification = 'geolocation.user';
                $data['active'] = false;
                $data['user'] = $this->getUser()->getId();
                $element = 'lounge';
                break;
            case 5:
                $typenotification = 'chat';
                if ($entity) {
                    $modelContent = 'lounge.chat.new_message';
                }
                $data['user'] = $this->getUser()->getId();
                $element = 'lounge';
                break;
            case 100:
                $typenotification = 'publication.commented';
                $element = 'publication';
                break;
            case 101:
                $typenotification = 'publication.liked';
                $element = 'publication';
                break;
        }
        if (!isset($modelContent)) {
            $modelContent = $typenotification;
        }
        $model = $this->getDoctrine()->getRepository('NaturaPassNotificationBundle:NotificationModel')->findOneBy(
            array(
                'type' => $modelContent
            )
        );
        $messageContent = $typenotification;
        if ($model) {
            $messageContent = $this->getTranslator()->transChoice($modelContent, 1, $content, 'notifications');
            $data['content'] = $messageContent;
        }

        $data['type'] = $typenotification;
        $data['element'] = $element;
        try {
            $push = $this->getNotificationService()->push;
            foreach ($users as $receiver) {
                $devices = $receiver->getDevices();

                foreach ($devices as $device) {
                    $send = true;
                    if (in_array($type, array(1, 2, 3, 4)) && !$forceSend) {
                        if ($element == 'lounge') {
                            $data['priority'] = 10;
                            $manager = $this->getDoctrine()->getManager();
                            $qb = $manager->createQueryBuilder();
                            $qb->select(array('m'))
                                ->from('NaturaPassUserBundle:UserMap', 'm')
                                ->join('m.device', 'd', 'WITH', 'd.id = :device')
                                ->setParameter(':device', $device->getDevice()->getId())
                                ->where('m.owner = :owner')
                                ->andWhere('m.type = :type')
                                ->andWhere('m.objectID = :lounge_id')
                                ->setParameter(':type', UserMap::LOUNGE)
                                ->setParameter(':owner', $receiver)
                                ->setParameter(':lounge_id', $entity->getId());

                            $results = $qb->getQuery()->getResult();
                            if (count($results) == 0) {
                                $send = false;
                            }
                        }
                    }
                    if ($device->isAuthorized() && $send) {
                        switch ($device->getDevice()->getType()) {
                            case Device::IOS:
                                $message = new iOSMessage();
                                if ($pushMessage) {
                                    $message->setAPSBadge('1');
                                    $message->setAPSSound('default');
                                }
                                break;
                            case Device::ANDROID:
                                $message = new AndroidMessage();
//                                if ($pushMessage) {
                                $message->setGCM(true);
//                                }
                                break;
                        }

                        $message->setData($data);
                        if ($pushMessage) {
                            $message->setMessage($messageContent);
                        }
                        $message->setDeviceIdentifier($device->getDevice()->getIdentifier());


                        $push->send($message);
                    }
                }
            }
        } catch (\RuntimeException $exception) {
            return $exception->getMessage();
        }

        return $data;
    }

    /**
     * @return \NaturaPass\UserBundle\Entity\User|boolean
     */
    public function getUser()
    {
        return parent::getUser();
    }

    /**
     * Autorise la visualisation d'une publication
     *
     * @param Publication $publication
     * @throws \Symfony\Component\HttpKernel\Exception\HttpException
     */
    public function authorizePublication(Publication $publication)
    {
        $authorized = false;

        $sharing = $publication->getSharing()->getShare();
        if ($sharing >= Sharing::FRIENDS && !$authorized) {
            if ($friendship = $publication->getOwner()->getStateFriendsWith($this->getUser())) {
                if ($friendship['state'] === UserFriend::CONFIRMED && $friendship['type'] === UserFriend::TYPE_FRIEND) {
                    $authorized = true;
                }
            }
        }

        if ($sharing >= Sharing::NATURAPASS && !$authorized) {
            $authorized = true;
        }

        if (in_array($this->getUser(), $publication->getSharing()->getWithouts()->toArray())) {
            $authorized = false;
        }

        $groups = $publication->getGroups();

        foreach ($this->getUser()->getAllGroups() as $group) {
            if ($groups->contains($group)) {
                $authorized = true;
            }
        }

        $hunts = $publication->getHunts();

        foreach ($this->getUser()->getAllHunts() as $hunt) {
            if ($hunts->contains($hunt)) {
                $authorized = true;
            }
        }

        if (!$authorized && !$this->isConnected('ROLE_SUPER_ADMIN') && $publication->getOwner() != $this->getUser()) {
            throw new HttpException(Codes::HTTP_FORBIDDEN, $this->message('codes.403'));
        }
    }

    /**
     * @param UserDevice $device
     *
     * @return array
     */
    protected function getFormatDevice(UserDevice $device)
    {
        return array(
            'id' => $device->getDevice()->getId(),
            'name' => $device->getDevice()->getName(),
            'verified' => $device->isVerified(),
            'authorized' => $device->isAuthorized(),
        );
    }

    /**
     * @param string $class Classe à sélectionner
     * @param string $alias Alias à donner
     * @param integer $sharing Niveau de partage sélectionné
     * @param boolean $landmark
     *
     * @return \Doctrine\ORM\QueryBuilder $qb
     */
    protected function getSharingQueryBuilder($class, $alias, $sharing, $landmark = false)
    {
        $manager = $this->getDoctrine()->getManager();

        /**
         * @var QueryBuilder $qb
         */
        $qb = $manager->createQueryBuilder()->select($alias)
            ->from($class, $alias)
            ->join('NaturaPassMainBundle:Sharing', 's', Join::WITH, 's = ' . $alias . '.sharing');

        if (!$this->isConnected('ROLE_SUPER_ADMIN')) {
            $wheres = $qb->expr()->orX();

            if ($sharing >= Sharing::USER) {
                $wheres->add($qb->expr()->eq($alias . '.owner', ':owner'));
                $qb->setParameter('owner', $this->getUser());
            }

            if ($sharing >= Sharing::FRIENDS) {
                $friends = $this->getUser()->getFriends(UserFriend::TYPE_FRIEND)->getValues();

                if (!empty($friends)) {
//                    $wheres->add($qb->expr()->andx(
//                                    $qb->expr()->eq('s.share', Sharing::FRIENDS), $qb->expr()->in($alias . '.owner', ':friends')
//                    ));
                    $wheres->add(
                        $qb->expr()->orX(
                            $qb->expr()->andx(
                                $qb->expr()->eq('s.share', Sharing::FRIENDS), $qb->expr()->in($alias . '.owner', ':friends')
                            ), $qb->expr()->andx(
                            $qb->expr()->eq('s.share', Sharing::NATURAPASS), $qb->expr()->in($alias . '.owner', ':friends')
                        )
                        )
                    );

                    $qb->setParameter('friends', $friends);
                }
            }

            if ($sharing >= Sharing::NATURAPASS) {
                $wheres->add($qb->expr()->eq('s.share', Sharing::NATURAPASS));
            }

            $qb->where($wheres);

            $qb->andWhere(
                $qb->expr()->notIn(
                    ':connected', $manager->createQueryBuilder()->select('w.id')
                    ->from('NaturaPassMainBundle:Sharing', 's2')
                    ->innerJoin('s2.withouts', 'w')
                    ->where('s.id = s2.id')
                    ->getDql()
                )
            );
            $qb->setParameter('connected', $this->getUser()->getId());
        }

        if ($landmark < 2) {
            $qb->andWhere('p.landmark = :landmark');
            $qb->setParameter('landmark', $landmark);
        }

        return $qb;
    }

    /**
     * @param string $class Classe à sélectionner
     * @param string $alias Alias à donner
     * @param string $filter Niveau de partage sélectionné
     * @param boolean $landmark
     *
     * @return \Doctrine\ORM\QueryBuilder $qb
     */
    protected function getSharingQueryBuilderFilter($class, $alias, $filter, $landmark = false)
    {
        $manager = $this->getDoctrine()->getManager();

        /**
         * @var QueryBuilder $qb
         */
        $qb = $manager->createQueryBuilder()->select($alias)
            ->from($class, $alias);
        $qb->join('NaturaPassMainBundle:Sharing', 's', Join::WITH, 's = ' . $alias . '.sharing');

        $wheres = $qb->expr()->orX();
        if (!$this->getUser()->hasRole('ROLE_SUPER_ADMIN') || (!empty($filter) && $this->getUser()->hasRole('ROLE_SUPER_ADMIN'))) {
            $whereSharing = $qb->expr()->orX();

            if (isset($filter['sharing'])) {
                if ($filter['sharing'] >= Sharing::USER) {
                    $whereSharing->add($qb->expr()->eq($alias . '.owner', ':owner'));
                    $qb->setParameter('owner', $this->getUser());
                }

                if ($filter['sharing'] >= Sharing::FRIENDS) {
                    $friends = $this->getUser()->getFriends(UserFriend::TYPE_FRIEND)->getValues();

                    if (!empty($friends)) {
                        $whereSharing->add(
                            $qb->expr()->orX(
                                $qb->expr()->andx(
                                    $qb->expr()->eq('s.share', Sharing::FRIENDS), $qb->expr()->in($alias . '.owner', ':friends')
                                ), $qb->expr()->andx(
                                $qb->expr()->eq('s.share', Sharing::NATURAPASS), $qb->expr()->in($alias . '.owner', ':friends')
                            )
                            )
                        );

                        $qb->setParameter('friends', $friends);
                    }
                }

                if ($filter['sharing'] >= Sharing::NATURAPASS) {
                    $whereSharing->add($qb->expr()->eq('s.share', Sharing::NATURAPASS));
                }
                $wheres->add($whereSharing);
            }

            if (isset($filter['group'])) {
                $wheresGroup = $qb->expr()->orX();
                $qb->leftJoin('p.groups', 'g');
                foreach ($filter['group'] as $id_group) {
                    $wheresGroup->add($qb->expr()->eq('g.id', ':group' . $id_group));
                    $qb->setParameter('group' . $id_group, $id_group);
                }
                $wheres->add($wheresGroup);
            }

            $qb->where($wheres);

            $qb->andWhere(
                $qb->expr()->notIn(
                    ':connected', $manager->createQueryBuilder()->select('w.id')
                    ->from('NaturaPassMainBundle:Sharing', 's2')
                    ->innerJoin('s2.withouts', 'w')
                    ->where('s.id = s2.id')
                    ->getDql()
                )
            );
            $qb->setParameter('connected', $this->getUser()->getId());
        }

        if ($landmark < 2) {
            $qb->andWhere('p.landmark = :landmark');
            $qb->setParameter('landmark', $landmark);
        }

        return $qb;
    }

    public function getFormatPublicationComment(Publication $publication)
    {
        $comments = array();

        $tmp = $publication->getComments();
        if (count($publication->getComments()) > 4) {
            $tmp = $tmp->slice(count($publication->getComments()) - 4, 4);
        }

        foreach ($tmp as $comment) {
            $comments[] = $this->getFormatComment($comment);
        }

        $addPublication = array(
            'totalComments' => $publication->getComments()->count(),
            'unloadedComments' => $publication->getComments()->count() - count($tmp),
            'comments' => $comments
        );

        $publications = array_merge($this->getFormatPublication($publication), $addPublication);

        return $publications;
    }

    public function getFormatComment(PublicationComment $comment)
    {
        $currentUser = $this->getUser();

        return array(
            'id' => $comment->getId(),
            'created' => $comment->getCreated()->format(\DateTime::ATOM),
            'content' => $comment->getContent(),
            'likes' => $comment->getActions(PublicationAction::STATE_LIKE)->count(),
            'unlikes' => $comment->getActions(PublicationAction::STATE_UNLIKE)->count(),
            'isUserLike' => $currentUser ? $comment->isAction($currentUser, PublicationAction::STATE_LIKE) : false,
            'isUserUnlike' => $currentUser ? $comment->isAction($currentUser, PublicationAction::STATE_UNLIKE) : false,
            'owner' => array(
                'id' => $comment->getOwner()->getId(),
                'firstname' => $comment->getOwner()->getFirstname(),
                'lastname' => $comment->getOwner()->getLastname(),
                'profilepicture' => $comment->getOwner()->getProfilePicture() ? $comment->getOwner()->getProfilePicture()->getThumb() : $this->getAssetHelper()->getUrl('img/interface/default-avatar.jpg'),
                'usertag' => $comment->getOwner()->getUsertag()
            )
        );
    }

    public function getAddFormatComment($comment)
    {
        $user = $this->getUser();

        return array(
            'comment' => array(
                'id' => $comment->getId(),
                'created' => $comment->getCreated()->format(\DateTime::ATOM),
                'content' => $comment->getContent(),
                'owner' => array(
                    'id' => $user->getId(),
                    'firstname' => $user->getFirstname(),
                    'lastname' => $user->getLastname(),
                    'profilepicture' => $user->getProfilePicture() ? $user->getProfilePicture()->getThumb() : $this->getAssetHelper()->getUrl(
                        'img/interface/default-avatar.jpg'
                    ),
                    'usertag' => $user->getUsertag()
                )
            )
        );
    }

    public function getFormatAddress(UserAddress $address)
    {
        return array(
            'id' => $address->getId(),
            'address' => $address->getAddress(),
            'latitude' => $address->getLatitude(),
            'longitude' => $address->getLongitude(),
            'altitude' => $address->getAltitude(),
            'favorite' => (boolean)$address->isFavorite(),
            'title' => $address->getTitle()
        );
    }

    public function getFormatUser(User $user, $friendship = false)
    {
        $format = array(
            'id' => $user->getId(),
            'firstname' => $user->getFirstname(),
            'lastname' => $user->getLastname(),
            'fullname' => $user->getFullName(),
            'profilepicture' => $user->getProfilePicture() ? $user->getProfilePicture()->getThumb() : $this->getAssetHelper()->getUrl(
                'img/interface/default-avatar.jpg'
            ),
            'usertag' => $user->getUsertag(),
            'parameters' => array("friend" => (boolean)$user->getParameters()->getFriends()),
        );

        if ($friendship) {
            list($way, $friendship) = $this->getUser()->hasFriendshipWith($user, array(UserFriend::ASKED, UserFriend::CONFIRMED));
            $format['mutualFriends'] = $this->getUser()->getMutualFriendsWith($user)->count();

            if ($friendship instanceof UserFriend) {
                $format['friendship'] = array(
                    'state' => $friendship->getState(),
                    'way' => $way
                );
            } else {
                $format['friendship'] = $friendship;
            }
        }

        return $format;
    }

    public function getFormatPublication(Publication $publication)
    {
        $currentUser = $this->getUser();

        $withouts = array();
        if ($publication->getOwner()->getId() === $currentUser->getId()) {
            foreach ($publication->getSharing()->getWithouts() as $without) {
                $withouts[] = $this->getFormatUser($without);
            }
        }

        $groups = array();
        $objects = $publication->getGroups();
        if (!empty($objects)) {
            foreach ($objects as $group) {
                $groups[] = $this->getFormatGroup($group);
            }
        }


        return array(
            'id' => $publication->getId(),
            'content' => $publication->getContent(),
            'media' => $publication->getMedia(),
            'likes' => $publication->getActions(PublicationAction::STATE_LIKE)->count(),
            'unlikes' => $publication->getActions(PublicationAction::STATE_UNLIKE)->count(),
            'isUserLike' => $currentUser ? $publication->isAction($currentUser, PublicationAction::STATE_LIKE) : false,
            'isUserUnlike' => $currentUser ? $publication->isAction($currentUser, PublicationAction::STATE_UNLIKE) : false,
            'created' => $publication->getCreated()->format(\DateTime::ATOM),
            'sharing' => array(
                'share' => $publication->getSharing()->getShare(),
                'withouts' => $withouts
            ),
            'groups' => $groups,
            'date' => $publication->getDate(),
            'reported' => $publication->isReported($this->getUser()),
            'owner' => $this->getFormatUser($publication->getOwner()),
            'geolocation' => $publication->getGeolocation()
        );
    }

    public function getFormatGroupSubscriber(GroupUser $subscriber)
    {
        return array(
            'user' => array(
                'id' => $subscriber->getUser()->getId(),
                'fullname' => $subscriber->getUser()->getFullname(),
                'firstname' => $subscriber->getUser()->getFirstname(),
                'lastname' => $subscriber->getUser()->getLastname(),
                'usertag' => $subscriber->getUser()->getUsertag(),
                'profilepicture' => ($subscriber->getUser()->getProfilePicture() ? $subscriber->getUser()->getProfilePicture()->getThumb() : $this->getAssetHelper()->getUrl('img/interface/default-avatar.jpg'))
            ),
            'mailable' => $subscriber->getMailable() == 0 ? false : true,
            'access' => $subscriber->getAccess(),
        );
    }

    public function getFormatGroupSubscriberFriend(GroupUser $subscriber)
    {
        $friend = array(
            'user' => array(
                'id' => $subscriber->getUser()->getId(),
                'fullname' => $subscriber->getUser()->getFullname(),
                'usertag' => $subscriber->getUser()->getUsertag(),
                'firstname' => $subscriber->getUser()->getFirstname(),
                'lastname' => $subscriber->getUser()->getLastname(),
                'profilepicture' => ($subscriber->getUser()->getProfilePicture() ? $subscriber->getUser()->getProfilePicture()->getThumb() : $this->getAssetHelper()->getUrl('img/interface/default-avatar.jpg'))
            ),
            'mailable' => $subscriber->getMailable(),
            'access' => $subscriber->getAccess(),
        );

        $isFriend = $this->getUser()->getStateFriendsWith(
            $subscriber->getUser(), array(UserFriend::ASKED, UserFriend::CONFIRMED, UserFriend::REJECTED)
        );
        if ($isFriend) {
            $friend['isFriend'] = $isFriend;
        }

        return $friend;
    }

    public function getFormatLoungeSubscriber(LoungeUser $subscriber)
    {
        $array = array(
            'user' => array(
                'id' => $subscriber->getUser()->getId(),
                'email' => $subscriber->getUser()->getEmail(),
                'fullname' => $subscriber->getUser()->getFullname(),
                'firstname' => $subscriber->getUser()->getFirstname(),
                'lastname' => $subscriber->getUser()->getLastname(),
                'usertag' => $subscriber->getUser()->getUsertag(),
                'profilepicture' => ($subscriber->getUser()->getProfilePicture() ? $subscriber->getUser()->getProfilePicture()->getThumb() : $this->getAssetHelper()->getUrl('img/interface/default-avatar.jpg'))
            ),
            'quiet' => $subscriber->getQuiet() ? 1 : 0,
            'geolocation' => $subscriber->getGeolocation(),
            'publicComment' => $subscriber->getPublicComment(),
            'participation' => $subscriber->getParticipation(),
            'access' => $subscriber->getAccess(),
        );

        if ($subscriber->getAccess() === LoungeUser::ACCESS_ADMIN) {
            $array = array_merge($array, array('privateComment' => $subscriber->getPrivateComment()));
        }

        return $array;
    }

    public function getFormatLoungeSubscriberFriend(LoungeUser $subscriber)
    {
        $isFriend = $this->getUser()->getStateFriendsWith(
            $subscriber->getUser(), array(UserFriend::ASKED, UserFriend::CONFIRMED, UserFriend::REJECTED)
        );

        $array = array(
            'user' => array(
                'id' => $subscriber->getUser()->getId(),
                'email' => $subscriber->getUser()->getEmail(),
                'fullname' => $subscriber->getUser()->getFullname(),
                'firstname' => $subscriber->getUser()->getFirstname(),
                'lastname' => $subscriber->getUser()->getLastname(),
                'usertag' => $subscriber->getUser()->getUsertag(),
                'profilepicture' => ($subscriber->getUser()->getProfilePicture() ? $subscriber->getUser()->getProfilePicture()->getThumb() : $this->getAssetHelper()->getUrl('img/interface/default-avatar.jpg'))
            ),
            'quiet' => $subscriber->getQuiet() ? 1 : 0,
            'geolocation' => $subscriber->getGeolocation(),
            'publicComment' => $subscriber->getPublicComment(),
            'participation' => $subscriber->getParticipation(),
            'access' => $subscriber->getAccess(),
        );

        if ($isFriend) {
            $array['isFriend'] = $isFriend;
        }

        if ($subscriber->getAccess() === LoungeUser::ACCESS_ADMIN) {
            $array = array_merge($array, array('privateComment' => $subscriber->getPrivateComment()));
        }

        return $array;
    }

    public function getFormatLoungeSubscriberNotMember(LoungeNotMember $subscriberNotMember)
    {
        $array = array(
            'id' => $subscriberNotMember->getId(),
            'fullname' => $subscriberNotMember->getFullName(),
            'firstname' => $subscriberNotMember->getFirstname(),
            'lastname' => $subscriberNotMember->getLastname(),
            'publicComment' => $subscriberNotMember->getPublicComment(),
            'privateComment' => $subscriberNotMember->getPrivateComment(),
            'participation' => $subscriberNotMember->getParticipation(),
        );

        return $array;
    }

    public function getFormatLoungeMessage(LoungeMessage $message)
    {
        $subscriber = $message->getLounge()->isSubscriber(
            $message->getOwner(), array(LoungeUser::ACCESS_DEFAULT, LoungeUser::ACCESS_INVITED, LoungeUser::ACCESS_RESTRICTED, LoungeUser::ACCESS_ADMIN)
        );

        return array(
            'id' => $message->getId(),
            'content' => $message->getContent(),
            'created' => $message->getCreated()->format(\DateTime::ATOM),
            'owner' => array(
                'id' => $message->getOwner()->getId(),
                'isAdmin' => $subscriber ? $subscriber->getAccess() === LoungeUser::ACCESS_ADMIN : false,
                'fullname' => $message->getOwner()->getFullname(),
                'profilepicture' => $message->getOwner()->getProfilePicture() ? $message->getOwner()->getProfilePicture()->getWebPath() : 'img/default-avatar.jpg',
                'usertag' => $message->getOwner()->getUsertag()
            ),
        );
    }

    public function getFormatGroupMessage(GroupMessage $message)
    {
        $subscriber = $message->getGroup()->isSubscriber(
            $message->getOwner(), array(GroupUser::ACCESS_DEFAULT, GroupUser::ACCESS_INVITED, GroupUser::ACCESS_RESTRICTED, GroupUser::ACCESS_ADMIN)
        );

        return array(
            'id' => $message->getId(),
            'content' => $message->getContent(),
            'created' => $message->getCreated()->format(\DateTime::ATOM),
            'owner' => array(
                'id' => $message->getOwner()->getId(),
                'isAdmin' => $subscriber ? $subscriber->getAccess() === GroupUser::ACCESS_ADMIN : false,
                'fullname' => $message->getOwner()->getFullname(),
                'profilepicture' => $message->getOwner()->getProfilePicture() ? $message->getOwner()->getProfilePicture()->getWebPath() : $this->getAssetHelper()->getUrl('img/default-avatar.jpg'),
                'usertag' => $message->getOwner()->getUsertag()
            ),
        );
    }

    public function getFormatLounge(Lounge $lounge, $force_update = false)
    {
        $subscriber = $lounge->isSubscriber(
            $this->getUser(), array(LoungeUser::ACCESS_DEFAULT, LoungeUser::ACCESS_INVITED, LoungeUser::ACCESS_RESTRICTED, LoungeUser::ACCESS_ADMIN)
        );

        $array = array(
            'id' => $lounge->getId(),
            'owner' => array(
                'id' => $lounge->getOwner()->getId(),
                'firstname' => $lounge->getOwner()->getFirstname(),
                'lastname' => $lounge->getOwner()->getLastname(),
                'fullname' => $lounge->getOwner()->getFullname(),
                'usertag' => $lounge->getOwner()->getUsertag(),
                'profilepicture' => $lounge->getOwner()->getProfilePicture() ? $lounge->getOwner()->getProfilePicture()->getThumb() : $this->getAssetHelper()->getUrl(
                    'img/interface/default-avatar.jpg'
                ),
            ),
            'geolocation' => $lounge->getGeolocation() ? 1 : 0,
            'meetingDate' => $lounge->getMeetingDate(),
            'endDate' => $lounge->getEndDate(),
            'allow_show' => $lounge->getAllowShow() ? 1 : 0,
            'allow_add' => $lounge->checkAllowAdd($this->getUser()) ? 1 : 0,
            'allow_show_chat' => $lounge->getAllowShowChat() ? 1 : 0,
            'allow_add_chat' => $lounge->checkAllowAddChat($this->getUser()) ? 1 : 0,
            'access' => $lounge->getAccess(),
            'name' => $lounge->getName(),
            'loungetag' => $lounge->getLoungeTag(),
            'description' => $lounge->getDescription(),
            'nbSubscribers' => $lounge->getSubscribers(array(LoungeUser::ACCESS_DEFAULT, LoungeUser::ACCESS_ADMIN))->count(),
            'nbParticipants' => $lounge->getNbParticipants(),
            'photo' => $this->getBaseUrl() . ($lounge->getPhoto() ? $lounge->getPhoto()->getThumb() : $this->getAssetHelper()->getUrl(
                    '/img/interface/default-media.jpg'
                )),
            'connected' => $subscriber instanceof LoungeUser ? $this->getFormatLoungeSubscriber($subscriber) : false,
            'updated' => $force_update ? 0 : $lounge->getLastUpdated($this->getUser())->format(\DateTime::ATOM),
	    'limitation' => array(
                'allow_show' => $lounge->getAllowShow(),
                'allow_add' => $lounge->getAllowAdd(),
                'allow_show_chat' => $lounge->getAllowShowChat(),
                'allow_add_chat' => $lounge->getAllowAddChat(),
            ),
        );
        if (!is_null($lounge->getMeetingAddress())) {
            $array["meetingAddress"] = array(
                'latitude' => $lounge->getMeetingAddress()->getLatitude(),
                'longitude' => $lounge->getMeetingAddress()->getLongitude(),
                'altitude' => $lounge->getMeetingAddress()->getAltitude(),
                'address' => $lounge->getMeetingAddress()->getAddress(),
            );
        }

        if ($subscriber instanceof LoungeUser && $subscriber->getAccess() === LoungeUser::ACCESS_ADMIN) {
            $array = array_merge(
                $array, array(
                    'nbAdmins' => $lounge->getSubscribers(array(LoungeUser::ACCESS_ADMIN))->count(),
                    'nbPending' => $lounge->getSubscribers(array(LoungeUser::ACCESS_RESTRICTED))->count(),
                )
            );
        }

        return $array;
    }

    public function getFormatGame(Game $game)
    {

        $array = array(
            'id' => $game->getId(),
            'title' => $game->getTitle(),
            'titleFormat' => $game->getTitleFormat(),
            'color' => $game->getColor(),
            'debut' => $game->getDebut()->format("d/m/Y"),
            'fin' => $game->getFin()->format("d/m/Y"),
            'debutFormat' => $this->getTranslator()->trans(
                'concours.date.start', array('%date%' => $game->getDebut()->format("d/m/Y")), 'main'
            ),
            'finFormat' => $this->getTranslator()->trans(
                'concours.date.start', array('%date%' => $game->getFin()->format("d/m/Y")), 'main'
            ),
            'type' => $game->getType(),
            'created' => $game->getCreated()->format(\DateTime::ATOM),
            'updated' => $game->getUpdated()->format(\DateTime::ATOM),
            'visuel' => $game->getVisuel(),
            'resultat' => $game->getResultat(),
        );


        return $array;
    }

    public function getFormatLongGame(Game $game)
    {
        $array = $this->getFormatGame($game);
        $array = array_merge(
            $array, array(
                'top1' => $game->getTop1(),
                'top2' => $game->getTop2(),
                'titleExplanation' => $game->getTitleExplanation(),
                'explanation' => $game->getExplanation(),
                'reglement' => $game->getReglement(),
            )
        );

        if ($game->getType() === Game::TYPE_CHALLENGE) {
            $array = array_merge(
                $array, array(
                    'challenge' => $game->getChallenge(),
                )
            );
        }

        return $array;
    }

    public function getFormatGroup(Group $group, $force_update = false)
    {
        $data = array(
            'id' => $group->getId(),
            'owner' => array(
                'id' => $group->getOwner()->getId(),
                'firstname' => $group->getOwner()->getFirstname(),
                'lastname' => $group->getOwner()->getLastname(),
                'usertag' => $group->getOwner()->getUsertag(),
                'profilepicture' => $group->getOwner()->getProfilePicture() ? $group->getOwner()->getProfilePicture()->getThumb() : $this->getAssetHelper()->getUrl('img/interface/default-avatar.jpg'),
            ),
            'access' => $group->getAccess(),
            "allow_add" => $group->checkAllowAdd($this->getUser()) ? 1 : 0,
            "allow_show" => $group->getAllowShow() ? 1 : 0,
            'allow_show_chat' => $group->getAllowShowChat() ? 1 : 0,
            'allow_add_chat' => $group->checkAllowAddChat($this->getUser()) ? 1 : 0,
            'name' => $group->getName(),
            'grouptag' => $group->getGrouptag(),
            'description' => $group->getDescription(),
            'nbSubscribers' => $group->getSubscribers(array(GroupUser::ACCESS_DEFAULT, GroupUser::ACCESS_ADMIN))->count(),
            'nbAdmins' => $group->getSubscribers(array(GroupUser::ACCESS_ADMIN))->count(),
            'nbPending' => $group->getSubscribers(array(GroupUser::ACCESS_RESTRICTED))->count(),
            //'notifications' => GroupSerialization::serializeGroupNotifications($group->getNotifications()),
            //'emails' => GroupSerialization::serializeGroupEmails($group->getEmails()),
            'photo' => $this->getBaseUrl() . ($group->getPhoto() ? $group->getPhoto()->getThumb() : $this->getAssetHelper()->getUrl(
                    '/img/interface/default-media.jpg'
                )),
            'updated' => $force_update ? 0 : $group->getLastUpdated($this->getUser())->format(\DateTime::ATOM),
	    'limitation' => array(
                'allow_show' => $group->getAllowShow(),
                'allow_add' => $group->getAllowAdd(),
                'allow_show_chat' => $group->getAllowShowChat(),
                'allow_add_chat' => $group->getAllowAddChat(),
            ),
        );
        $subscriber = $group->getSubscriber($this->getUser());
        if ($subscriber instanceof GroupUser) {
            $subscriber = $this->getFormatGroupSubscriber($subscriber);
        }

        $data['connected'] = $subscriber;

        return $data;
    }

    public function getFormatGroupLess(Group $group)
    {
        return array(
            'id' => $group->getId(),
            'name' => $group->getName(),
            'grouptag' => $group->getGrouptag(),
            'description' => $group->getDescription(),
        );
    }

    public function getFormatDistibuteur(Distributor $distributor)
    {
        return array(
            'id' => $distributor->getId(),
            'name' => $distributor->getName(),
            'address' => $distributor->getAddress(),
            'cp' => $distributor->getCp(),
            'city' => $distributor->getCity(),
            'telephone' => $distributor->getTelephone(),
            'email' => $distributor->getEmail(),
            'logo' => $this->getBaseUrl() . ($distributor->getLogo() ? $distributor->getLogo()->getThumb() : $this->getAssetHelper()->getUrl(
                    '/img/interface/default-media.jpg'
                ))
        );
    }

    public function getFormatDistibuteurDetail(Distributor $distributor)
    {
        return array(
            'id' => $distributor->getId(),
            'name' => $distributor->getName(),
            'address' => $distributor->getAddress(),
            'cp' => $distributor->getCp(),
            'city' => $distributor->getCity(),
            'geolocation' => array(
                'latitude' => $distributor->getGeolocation()->getLatitude(),
                'longitude' => $distributor->getGeolocation()->getLongitude(),
                'altitude' => $distributor->getGeolocation()->getAltitude(),
            ),
            'brands' => $this->getFormatAllBrands($distributor->getBrands()),
            'telephone' => $distributor->getTelephone(),
            'email' => $distributor->getEmail(),
            'logo' => $distributor->getLogo() instanceof BaseMedia ? MainSerialization::serializeMedia($distributor->getLogo(), true) : null,
            'marker' => \Api\ApiBundle\Controller\v2\ApiRestController::getMarker("grey", "circle", array("picto" => "map_icon_distributor")),
            'mobile_marker' => \Api\ApiBundle\Controller\v2\ApiRestController::getMarker("grey", "circle", array("picto" => "map_icon_distributor"), false, false, false, false),
        );
    }

    public function getFormatAllBrands($aBrand = array())
    {
        $arrayChild = array();
        foreach ($aBrand as $brand) {
            $arrayChild[] = $this->getFormatBrandDetail($brand);
        }

        return $arrayChild;
    }

    public function getFormatBrandDetail(Brand $brand)
    {
        return array(
            'id' => $brand->getId(),
            'name' => $brand->getName(),
            'partner' => $brand->getPartner(),
            'logo' => $brand->getLogo() instanceof BaseMedia ? MainSerialization::serializeMedia($brand->getLogo(), true) : null,
        );
    }

    public function getFormatNewDetail(\Admin\NewsBundle\Entity\News $new)
    {
        return array(
            'id' => $new->getId(),
            'title' => $new->getTitle(),
            'date' => $new->getDate(),
            'active' => $new->getActive(),
            'content' => $new->getContent(),
            'photo' => $new->getPhoto(),
        );
    }

    public function getFormatCategory(Category $category, $cardLess = false)
    {
        $em = $this->getDoctrine()->getManager();
        $repo = $em->getRepository('AdminSentinelleBundle:Category');
        $childrens = $repo->children($category, true, 'lft');
        $arrayChild = array();
        if (count($childrens)) {
            $arrayChild = array();
            foreach ($childrens as $category2) {
                $arrayChild[] = $this->getFormatCategory($category2, $cardLess);
            }
        }
        $array = array(
            'id' => $category->getId(),
            'name' => $category->getName(),
            'children' => count($childrens) ? $arrayChild : ((object)array())
        );
//        if ($repo->isLeaf($category) && !is_null($category->getCard())) {
        if (!is_null($category->getCard())) {
            if ($cardLess) {
                $array = array_merge($array, array('card' => $this->getFormatCardLess($category->getCard())));
            } else {
                $array = array_merge($array, array('card' => $this->getFormatCard($category->getCard())));
            }
        }

        return $array;
    }

    public function getFormatCategoryAdmin(Category $category)
    {
        $em = $this->getDoctrine()->getManager();
//        if ($category->getVisible() == Category::VISIBLE_ON) {
        $repo = $em->getRepository('AdminSentinelleBundle:Category');
        $childrens = $repo->children($category, true, 'lft');
        if (count($childrens)) {
            $arrayChild = array();
            foreach ($childrens as $category2) {
                $arrayCh = $this->getFormatCategoryAdmin($category2);
                if (!is_null($arrayCh)) {
                    $arrayChild[] = $arrayCh;
                }
            }
        }
        $array = array(
            'id' => $category->getId(),
            'title' => $category->getName(),
            'nodes' => (count($childrens) ? $arrayChild : (array())),
            'groups' => $category->getGroupsFormat(),
            'visible' => $category->getVisible(),
            'search' => $category->getSearch() == 1 ? true : false
        );
        if (!is_null($category->getCard())) {
            $array = array_merge($array, array('card' => $this->getFormatCard($category->getCard())));
        }

//        }
        return $array;
    }

    public function getFormatCategoryByZone(Category $category, Zone $zone, $without = array())
    {
        $em = $this->getDoctrine()->getManager();
        $repo = $em->getRepository('AdminSentinelleBundle:Category');
        $childrens = $repo->children($category, true, 'lft');
        $arrayChild = array();
        if (count($childrens)) {
            $arrayChild = array();
            foreach ($childrens as $category2) {
                if (!in_array($category2->getId(), $without)) {
                    $arrayChild[] = $this->getFormatCategoryByZone($category2, $zone, $without);
                }
            }
        }
        $array = array(
            'id' => $category->getId(),
            'name' => $category->getName(),
            'children' => count($childrens) ? $arrayChild : ((object)array())
        );
//        if ($repo->isLeaf($category) && !is_null($category->getCard())) {
        $cardsZone = $category->getCardszone($zone);
        if (count($cardsZone)) {
            $cardCategoryZone = $cardsZone[0];
            $array = array_merge($array, array('card' => $this->getFormatCard($cardCategoryZone->getCard())));
        }
//        else if (!is_null($category->getCard())) {
//            $array = array_merge($array, array('card' => $this->getFormatCard($category->getCard())));
//        }
        return $array;
    }

    public function getFormatCategoryByReceiver(Category $category, Receiver $receiver, $without = array())
    {
        $em = $this->getDoctrine()->getManager();
        $repo = $em->getRepository('AdminSentinelleBundle:Category');
        $childrens = $repo->children($category, true, 'lft');
        $arrayChild = array();
        if (count($childrens)) {
            $arrayChild = array();
            foreach ($childrens as $category2) {
                if (!in_array($category2->getId(), $without)) {
                    $arrayChild[] = $this->getFormatCategoryByReceiver($category2, $receiver, $without);
                }
            }
        }
        $array = array(
            'id' => $category->getId(),
            'name' => $category->getName(),
            'children' => count($childrens) ? $arrayChild : ((object)array())
        );
//        if ($repo->isLeaf($category) && !is_null($category->getCard())) {
//        if (count($category->getCards($receiver))) {
//            $cardCategoryReceiver = $category->getCards($receiver)[0];
//            $array = array_merge($array, array('card' => $this->getFormatCard($cardCategoryReceiver->getCard())));
//        } else if (!is_null($category->getCard())) {
//            $array = array_merge($array, array('card' => $this->getFormatCard($category->getCard())));
//        }
        return $array;
    }

    public function getFormatCategoryByZoneAdmin(Category $category, Zone $zone, $arrayOk = array())
    {
        $em = $this->getDoctrine()->getManager();
        $array = null;
        if (array_key_exists($category->getId(), $arrayOk)) {
            $repo = $em->getRepository('AdminSentinelleBundle:Category');
            $childrens = $repo->children($category, true, 'lft');
            if (count($childrens)) {
                $arrayChild = array();
                foreach ($childrens as $category2) {
                    if (!in_array($category2->getId(), $arrayOk)) {
                        $arrayCh = $this->getFormatCategoryByZoneAdmin($category2, $zone, $arrayOk);
                        if (!is_null($arrayCh)) {
                            $arrayChild[] = $arrayCh;
                        }
                    }
                }
            }
            $array = array(
                'id' => $category->getId(),
                'title' => $category->getName(),
                'nodes' => (count($childrens) ? $arrayChild : (array()))
            );
            $cardsZone = $category->getCardszone($zone);
            if (!is_null($cardsZone)) {
                foreach ($cardsZone as $cardCategoryZone) {
                    $array = array_merge($array, array('card' => $this->getFormatCardLess($cardCategoryZone->getCard())));
                }
            }
        }

        return $array;
    }

    public function getFormatCategoryByReceiverAdmin(Category $category, Receiver $receiver)
    {
        $em = $this->getDoctrine()->getManager();
        $repo = $em->getRepository('AdminSentinelleBundle:Category');
        $childrens = $repo->children($category, true, 'lft');
        if (count($childrens)) {
            $arrayChild = array();
            foreach ($childrens as $category2) {
                $arrayCh = $this->getFormatCategoryByReceiverAdmin($category2, $receiver);
                if (!is_null($arrayCh)) {
                    $arrayChild[] = $arrayCh;
                }
            }
        }
        $repoReceiver = $em->getRepository('AdminSentinelleBundle:ReceiverRight')->findOneBy(
            array(
                'receiver' => $receiver,
                'category' => $category
            )
        );
        $array = array(
            'id' => $category->getId(),
            'title' => $category->getName(),
            'nodes' => (count($childrens) ? $arrayChild : (array())),
            'visible' => is_object($repoReceiver) ? 1 : 0
        );

        return $array;
    }

    public function getFormatCategoryByReceiverBackoffice(Category $category, Receiver $receiver)
    {
        $em = $this->getDoctrine()->getManager();
        $repo = $em->getRepository('AdminSentinelleBundle:Category');
        $childrens = $repo->children($category, true, 'lft');
        if (count($childrens)) {
            $arrayChild = array();
            foreach ($childrens as $category2) {
                $arrayCh = $this->getFormatCategoryByReceiverBackoffice($category2, $receiver);
                if (!is_null($arrayCh)) {
                    $arrayChild[] = $arrayCh;
                }
            }
        }
        $repoReceiver = $em->getRepository('AdminSentinelleBundle:ReceiverRight')->findOneBy(
            array(
                'receiver' => $receiver,
                'category' => $category
            )
        );
        if (is_object($repoReceiver)) {
            $array = array(
                'id' => $category->getId(),
                'name' => $category->getName(),
                'children' => (count($childrens) ? $arrayChild : (array())),
            );
        } else {
            $array = null;
        }

        return $array;
    }

    public function getFormatCardLess(Card $card)
    {
        return array(
            'id' => $card->getId(),
            'name' => $card->getName(),
        );
    }

    public function getFormatCard(Card $card)
    {
        if (count($card->getLabels())) {
            $arrayChild = array();
            foreach ($card->getLabels() as $cardCategoryLabel) {
                if ($cardCategoryLabel->getVisible() == 1) {
                    if (count($cardCategoryLabel->getContents())) {
                        $arrayChildContent = array();
                        foreach ($cardCategoryLabel->getContents() as $cardCategoryLabelContent) {
                            if ($cardCategoryLabelContent->getVisible() == 1) {
                                $arrayChildContent[] = array(
                                    'id' => $cardCategoryLabelContent->getId(),
                                    'name' => $cardCategoryLabelContent->getName(),
                                );
                            }
                        }
                    }
                    $arrayChild[] = array(
                        'id' => $cardCategoryLabel->getId(),
                        'name' => $cardCategoryLabel->getName(),
                        'type' => $cardCategoryLabel->getType(),
                        'required' => (boolean)$cardCategoryLabel->getRequired(),
                        'contents' => count($cardCategoryLabel->getContents()) ? $arrayChildContent : (array())
                    );
                }
            }
        }

        return array(
            'id' => $card->getId(),
            'name' => $card->getName(),
            'labels' => count($card->getLabels()) ? $arrayChild : (array())
        );
    }

    public function getFormatAnimalAdmin(Animal $animal)
    {
        $em = $this->getDoctrine()->getManager();
        $repo = $em->getRepository('AdminAnimalBundle:Animal');
        $childrens = $repo->children($animal, true, 'lft');
        if (count($childrens)) {
            $arrayChild = array();
            foreach ($childrens as $animal2) {
                $arrayChild[] = $this->getFormatAnimalAdmin($animal2);
            }
        }
        $array = array(
            'id' => $animal->getId(),
            'title' => $animal->getName_fr(),
            'nodes' => (count($childrens) ? $arrayChild : (array()))
        );

        return $array;
    }

    public function getFormatAnimalTreeAdmin(Animal $animal)
    {
        $em = $this->getDoctrine()->getManager();
        $repo = $em->getRepository('AdminAnimalBundle:Animal');
        $childrens = $repo->children($animal, true, 'lft');
        if (count($childrens)) {
            $arrayChild = array();
            foreach ($childrens as $animal2) {
                $arrayChild[] = $this->getFormatAnimalTreeAdmin($animal2);
            }
        }
        $array = array(
            'id' => "new",
            'title' => $animal->getName_fr(),
            'nodes' => (count($childrens) ? $arrayChild : (array())),
            'visible' => 1
        );

        return $array;
    }

    public function getFormatReceiverDetail(\Admin\SentinelleBundle\Entity\Receiver $receiver)
    {
        return array(
            'id' => $receiver->getId(),
            'name' => $receiver->getName(),
            'photo' => $receiver->getPhoto(),
        );
    }

    public function getFormatReceiverLess(\Admin\SentinelleBundle\Entity\Receiver $receiver)
    {
        return array(
            'id' => $receiver->getId(),
            'name' => $receiver->getName(),
        );
    }

    public function getFormatPublicationCategorieTree(Category $category, $zone, $arrayOk = array())
    {
        $em = $this->getDoctrine()->getManager();
        $array = null;
        if (array_key_exists($category->getId(), $arrayOk)) {
            $repo = $em->getRepository('AdminSentinelleBundle:Category');
            $childrens = $repo->children($category, true, 'lft');
            if (count($childrens)) {
                $arrayChild = array();
                foreach ($childrens as $category2) {
                    if (!in_array($category2->getId(), $arrayOk)) {
                        $arrayCh = $this->getFormatPublicationCategorieTree($category2, $zone, $arrayOk);
                        if (!is_null($arrayCh)) {
                            $arrayChild[] = $arrayCh;
                        }
                    }
                }
            }
            $array = array(
                'id' => $category->getId(),
                'name' => $category->getName(),
                'children' => (count($childrens) ? $arrayChild : (array())),
            );
            if (count($childrens) == 0) {
                $arrayReceiver = array();
                foreach ($arrayOk[$category->getId()] as $receiver_id) {
                    $arrayReceiver[] = $this->getFormatReceiverLess($em->getRepository('AdminSentinelleBundle:Receiver')->find($receiver_id));
                }
                $array = array_merge($array, array('sharing' => $arrayReceiver));
                if (is_object($zone)) {
                    $cardsZone = $category->getCardszone($zone);
                    if (!is_null($cardsZone)) {
                        foreach ($cardsZone as $cardCategoryZone) {
                            $array = array_merge($array, array('card' => $this->getFormatCardLess($cardCategoryZone->getCard())));
                        }
                    }
                }
            }
        }

        return $array;
    }

    public function getFormatObservationDetail(Observation $observation)
    {
        $attachements = array();
        foreach ($observation->getAttachments() as $attachement) {
            $attachements[] = array(
                'label' => $attachement->getLabel()->getName(),
                'value' => $attachement->getValue(),
            );
        }
        $sharingreceivers = array();
        foreach ($observation->getReceivers() as $receiver) {
            $sharingreceivers[] = array(
                'id' => $receiver->getReceiver()->getId(),
                'name' => $receiver->getReceiver()->getName(),
            );
        }

        return array(
            'id' => $observation->getId(),
            'publication' => $this->getFormatPublication($observation->getPublication()),
            'category' => array("name" => $observation->getCategory()->getName()),
            'attachments' => $attachements,
            'sharing_receiver' => $sharingreceivers,
        );
    }

    public function getFormatMessage($message)
    {
        $formatmessage = array(
            'userId' => $this->getUser()->getId(),
            'messageId' => $message->getId(),
            'content' => $message->getContent(),	   
            'updated' => $message->getUpdated()->format(\DateTime::ATOM),
            'created' => $message->getCreated()->format(\DateTime::ATOM),
            'owner' => array(
                'ownerId' => $this->getUser()->getId(),
                'firstname' => $this->getUser()->getFirstname(),
                'lastname' => $this->getUser()->getLastname(),
                'fullname' => $this->getUser()->getFullname(),
                'usertag' => $this->getUser()->getUsertag(),
                'profilepicture' => $this->getUser()->getProfilePicture() ? $this->getUser()->getProfilePicture()->getThumb() : $this->getAssetHelper()->getUrl('img/interface/default-avatar.jpg'),
            )
        );
        return $formatmessage;
    }

    /**
     * @return \Symfony\Component\HttpKernel\Debug\TraceableEventDispatcher
     */
    protected function getEventDispatcher()
    {
        return $this->get('event_dispatcher');
    }

    /**
     * Add an event to kernel terminate event
     *
     * @param callable $listener
     * @param int $priority
     */
    protected function delay($listener, $priority = 10)
    {
        $this->getEventDispatcher()->addListener('kernel.terminate', $listener, $priority);
    }

    //vietlh
    protected
    function checkDuplicatedLoungeMessage(LoungeMessage $message)
    {
        $manager = $this->getDoctrine()->getManager();
        $query = $manager
            ->createQueryBuilder('p')->select('p')
            ->from('NaturaPassLoungeBundle:LoungeMessage', 'p')
            ->where('p.owner = :user')
            ->andWhere('p.content = :content')
            ->andWhere('p.lounge = :lounge')
            ->andWhere('p.id != :id');

        if ($message->getCreated() != "") {
            $query->andWhere('p.created = :date');
            $query->setParameter('date', $message->getCreated()->format("Y-m-d H:i:s"));
        }
        
        $query->setParameter('content', $message->getContent());
        $query->setParameter('user', $this->getUser());
        $query->setParameter('id', $message->getId());
        $query->setParameter('lounge', $message->getLounge()->getId());
        $response = $query->getQuery()->getResult();
        if (count($response)) {
            return $response;
        }
        return false;
    }

    //vietlh
    protected
    function checkDuplicatedMessage(Message $message)
    {
        $manager = $this->getDoctrine()->getManager();
        $query = $manager
            ->createQueryBuilder('p')->select('p')
            ->from('NaturaPassMessageBundle:Message', 'p')
            ->where('p.owner = :user')
            ->andWhere('p.guid = :guid')
            ->andWhere('p.conversation = :conversation')
            ->andWhere('p.id != :id');

        if ($message->getCreated() != "") {
            $query->andWhere('p.created = :date');
            $query->setParameter('date', $message->getCreated()->format("Y-m-d H:i:s"));
        }
        
        $query->setParameter('guid', $message->getGuid());
        $query->setParameter('user', $this->getUser());
        $query->setParameter('id', $message->getId());
        $query->setParameter('conversation', $message->getConversation()->getId());
        $response = $query->getQuery()->getResult();
        if (count($response)) {
            return $response;
        }
        return false;
    }
}

