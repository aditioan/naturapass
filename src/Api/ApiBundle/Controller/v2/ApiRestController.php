<?php

/**
 * Created by PhpStorm.
 * User: vincentvalot
 * Date: 21/07/15
 * Time: 09:58
 */

namespace Api\ApiBundle\Controller\v2;

use Doctrine\ORM\Query\Expr\Join;
use Doctrine\ORM\QueryBuilder;
use FOS\RestBundle\Controller\FOSRestController;
use NaturaPass\GroupBundle\Entity\GroupUser;
use NaturaPass\LoungeBundle\Entity\LoungeUser;
use NaturaPass\MainBundle\Entity\Sharing;
use NaturaPass\PublicationBundle\Entity\Favorite;
use NaturaPass\PublicationBundle\Entity\Publication;
use NaturaPass\GroupBundle\Entity\Group;
use NaturaPass\LoungeBundle\Entity\Lounge;
use NaturaPass\UserBundle\Entity\DogParameter;
use NaturaPass\UserBundle\Entity\DogPhoto;
use NaturaPass\UserBundle\Entity\PaperParameter;
use NaturaPass\UserBundle\Entity\UserFriend;
use NaturaPass\UserBundle\Entity\WeaponParameter;
use Symfony\Component\HttpKernel\Exception\HttpException;
use FOS\RestBundle\Util\Codes;
use NaturaPass\UserBundle\Entity\User;
use NaturaPass\GroupBundle\Entity\GroupMessage;


class ApiRestController extends FOSRestController
{

    const SOCKET_PORT = 3000;

    /**
     * Vérifie si l'utilisateur est bien authentifié
     * @author Vincent Valot
     * @throws HttpException
     *
     * @param mixed $allowed Un utilisateur ou un tableau d'utilisateurs autorisés
     * @param array $roles Role de l'utilisateur (par défault authentifié)
     *
     * @return boolean
     */
    protected function authorize($allowed = NULL, $roles = array('IS_AUTHENTICATED_FULLY', 'IS_AUTHENTICATED_REMEMBERED'))
    {
        if (!$this->getSecurityTokenStorage()->getToken() || !$this->getUser() instanceof User) {
            throw new HttpException(Codes::HTTP_UNAUTHORIZED, $this->message('codes.401'));
        }

        if (!$this->getSecurityAuthorization()->isGranted($roles)) {
            throw new HttpException(Codes::HTTP_FORBIDDEN, $this->message('codes.403'));
        }

        if ($this->getUser()->getLocked()) {
            throw new HttpException(Codes::HTTP_FORBIDDEN, $this->message('codes.403'));
        }

        if ($allowed instanceof User) {
            if ($this->getUser() != $allowed) {
                throw new HttpException(Codes::HTTP_FORBIDDEN, $this->message('codes.403'));
            }
        } else if (is_array($allowed) && !empty($allowed)) {
            foreach ($allowed as $user) {
                if ($this->getUser() == $user) {
                    return true;
                }
            }

            throw new HttpException(Codes::HTTP_FORBIDDEN, $this->message('codes.403'));
        }

        return false;
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
     * @return \Symfony\Bundle\FrameworkBundle\Translation\Translator
     */
    protected function getTranslator()
    {
        return $this->get('translator');
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
     * @return \NaturaPass\UserBundle\Entity\User|boolean
     */
    public function getUser()
    {
        return parent::getUser();
    }

    /**
     * @param string $class Classe à sélectionner
     * @param string $alias Alias à donner
     * @param string $filter Niveau de partage sélectionné
     * @param boolean $landmark
     *
     * @return \Doctrine\ORM\QueryBuilder
     */
    protected function getSharingQueryBuilder($class, $alias, $filter, $landmark = false)
    {
        $manager = $this->getDoctrine()->getManager();

        /**
         * @var QueryBuilder $qb
         */
        $qb = $manager->createQueryBuilder()->select($alias)
            ->from($class, $alias);

        $wheres = $qb->expr()->orX();
        if (!$this->getUser()->hasRole('ROLE_SUPER_ADMIN') || (!empty($filter) && $this->getUser()->hasRole('ROLE_SUPER_ADMIN'))) {
            $whereSharing = $qb->expr()->orX();

            if (isset($filter['sharing'])) {
                $qb->join('NaturaPassMainBundle:Sharing', 's', Join::WITH, 's = ' . $alias . '.sharing');

                if ($filter['sharing'] == Sharing::USER) {// get only user
                    $whereSharing->add($qb->expr()->eq($alias . '.owner', ':owner'));
                    $qb->setParameter('owner', $this->getUser());
                }

                if ($filter['sharing'] == Sharing::ONLYFRIENDS) { // get only friends
                    $friends = $this->getUser()->getFriends(UserFriend::TYPE_FRIEND)->getValues();

                    if (!empty($friends)) {
                        $whereSharing->add($qb->expr()->orX(
                            $qb->expr()->andx(
                                $qb->expr()->eq('s.share', Sharing::FRIENDS), $qb->expr()->in($alias . '.owner', ':friends')
                            ), $qb->expr()->andx(
                            $qb->expr()->eq('s.share', Sharing::NATURAPASS), $qb->expr()->in($alias . '.owner', ':friends')
                        )
                        ));
                        $qb->setParameter('friends', $friends);
                    }
                }

                if ($filter['sharing'] == Sharing::NATURAPASS) { // get all
                    $whereSharing->add($qb->expr()->eq('s.share', Sharing::NATURAPASS));

                    if ($filter['sharing'] >= Sharing::USER) {
                    $whereSharing->add($qb->expr()->eq($alias . '.owner', ':owner'));
                    $qb->setParameter('owner', $this->getUser());
                    }

                    if ($filter['sharing'] >= Sharing::FRIENDS) {
                        $friends = $this->getUser()->getFriends(UserFriend::TYPE_FRIEND)->getValues();

                        if (!empty($friends)) {
                            $whereSharing->add($qb->expr()->orX(
                                $qb->expr()->andx(
                                    $qb->expr()->eq('s.share', Sharing::FRIENDS), $qb->expr()->in($alias . '.owner', ':friends')
                                ), $qb->expr()->andx(
                                $qb->expr()->eq('s.share', Sharing::NATURAPASS), $qb->expr()->in($alias . '.owner', ':friends')
                            )
                            ));

                            $qb->setParameter('friends', $friends);
                        }
                    }
                }

                if ($filter['sharing'] == 999) { // get all
                    $whereSharing->add($qb->expr()->eq('s.share', Sharing::NATURAPASS));
                }

                if ($filter['sharing'] == Sharing::USERFRIENDS || $filter['sharing'] == Sharing::FRIENDS) { // get user + friends
                    if ($filter['sharing'] >= Sharing::USER) {
                    $whereSharing->add($qb->expr()->eq($alias . '.owner', ':owner'));
                    $qb->setParameter('owner', $this->getUser());
                    }

                    if ($filter['sharing'] >= Sharing::FRIENDS) {
                        $friends = $this->getUser()->getFriends(UserFriend::TYPE_FRIEND)->getValues();

                        if (!empty($friends)) {
                            $whereSharing->add($qb->expr()->orX(
                                $qb->expr()->andx(
                                    $qb->expr()->eq('s.share', Sharing::FRIENDS), $qb->expr()->in($alias . '.owner', ':friends')
                                ), $qb->expr()->andx(
                                $qb->expr()->eq('s.share', Sharing::NATURAPASS), $qb->expr()->in($alias . '.owner', ':friends')
                            )
                            ));

                            $qb->setParameter('friends', $friends);
                        }
                    }
                }

                $wheres->add($whereSharing);
            }

            if (!empty($filter['groups'])) {
                $wheresGroup = $qb->expr()->orX();
                $qb->leftJoin('p.groups', 'gr');
                $qb->leftJoin('gr.subscribers', 'grs');
                foreach ($filter['groups'] as $id_group) {
                    $wheresGroupAllowShow = $qb->expr()->andX();
                    $wheresGroupAllowShow->add($qb->expr()->eq('gr.id', ':group' . $id_group));
                    $groupOr1 = $qb->expr()->orX();
                    $groupOr1->add($qb->expr()->eq('p.owner', ':owner' . $id_group));
                    $groupOr1->add($qb->expr()->eq('gr.allowShow', ':allowShow' . $id_group));
                    $groupAnd1 = $qb->expr()->andX();
                    $groupAnd1->add($qb->expr()->eq('gr.allowShow', ':allowShowFalse' . $id_group));
                    $groupAnd1->add($qb->expr()->eq('grs.access', ':access' . $id_group));
                    $groupAnd1->add($qb->expr()->eq('grs.user', ':user' . $id_group));
                    $groupOr1->add($groupAnd1);
                    $qb->setParameter('allowShow' . $id_group, Group::ALLOW_ALL_MEMBERS);
                    $qb->setParameter('allowShowFalse' . $id_group, Group::ALLOW_ADMIN);
                    $qb->setParameter('access' . $id_group, GroupUser::ACCESS_ADMIN);
                    $qb->setParameter('user' . $id_group, $this->getUser());
                    $qb->setParameter('owner' . $id_group, $this->getUser());
                    $wheresGroupAllowShow->add($groupOr1);
                    $wheresGroup->add($wheresGroupAllowShow);
                    $qb->setParameter('group' . $id_group, $id_group);
                }
                $wheres->add($wheresGroup);
            }
            if (!empty($filter['hunts'])) {
                $wheresHunt = $qb->expr()->orX();
                $qb->leftJoin('p.hunts', 'hu');
                foreach ($filter['hunts'] as $id_hunt) {
                    $wheresHunt->add($qb->expr()->eq('hu.id', ':hunt' . $id_hunt));
                    $qb->setParameter('hunt' . $id_hunt, $id_hunt);
                }
                $wheres->add($wheresHunt);
            }

            if (!empty($filter['persons'])) {
                if(!isset($filter['sharing'])){
                    $qb->join('NaturaPassMainBundle:Sharing', 's', Join::WITH, 's = ' . $alias . '.sharing');
                }
                $wheresPerson = $qb->expr()->orX();
                foreach ($filter['persons'] as $id_person) {
                    $wheresPerson->add($qb->expr()->andx($qb->expr()->eq('s.share', Sharing::NATURAPASS), $qb->expr()->eq('p.owner', ':owner' . $id_person)));
                    $qb->setParameter('owner' . $id_person, $id_person);
                }
                $wheres->add($wheresPerson);
            }

            if (!empty($filter['beShared'])) {
                $wheresBeShared = $qb->expr()->orX();
                foreach ($filter['beShared'] as $id_pubBeShared) {
                    $wheresBeShared->add($qb->expr()->eq('p.id', ':id_beShared'));
                    $qb->setParameter('id_beShared', $id_pubBeShared);
                }
                $wheres->add($wheresBeShared);
            }

            if (!empty($filter['sharesByFriend'])) {
                $wheressharesByFriend = $qb->expr()->orX();
                foreach ($filter['sharesByFriend'] as $id_pubBeShared) {
                    $wheressharesByFriend->add($qb->expr()->eq('p.id', ':id_beShared'. $id_pubBeShared));
                    $qb->setParameter('id_beShared'.$id_pubBeShared, $id_pubBeShared);
                }
                $wheres->add($wheressharesByFriend);
            }

            $qb->where($wheres);

            if ($this->getUser()->getLocks()->count()) {
                $wheresLock = $qb->expr()->andX();
                foreach ($this->getUser()->getLocks() as $userLock) {
                    $wheresLock->add($qb->expr()->neq('p.owner', ':lockOwner' . $userLock->getId()));
                    $qb->setParameter('lockOwner' . $userLock->getId(), $userLock->getId());
                }
                $qb->andWhere($wheresLock);
            }

            if (!empty($filter['categories'])) {
                $qb->innerJoin('p.observations', 'ob');
                $wheresCategory = $qb->expr()->orX();
                foreach ($filter['categories'] as $id_category) {
                    $wheresCategory->add($qb->expr()->eq('ob.category', ':category' . $id_category));
                    $qb->setParameter('category' . $id_category, $id_category);
                }
                $qb->andWhere($wheresCategory);
            }


            if (isset($filter['sharing'])) {
                $qb->andWhere($qb->expr()->notIn(
                    ':connected', $manager->createQueryBuilder()->select('w.id')
                    ->from('NaturaPassMainBundle:Sharing', 's2')
                    ->innerJoin('s2.withouts', 'w')
                    ->where('s.id = s2.id')
                    ->getDql()
                ));
                $qb->setParameter('connected', $this->getUser()->getId());
            }
            if ($class == "NaturaPassPublicationBundle:Publication") {
                if (isset($filter['filter']) && $filter['filter']) {

                    $qb->leftJoin('p.observations', 'obs');
                    $qb->leftJoin('obs.category', 'cat');
                    $qb->leftJoin('obs.animal', 'ani');
                    $qb->innerJoin('p.owner', 'usr');                    
                    $qb->andWhere("p.legend LIKE :filterSearch OR p.content LIKE :filterSearch OR CONCAT(usr.firstname,' ', usr.lastname) LIKE :filterSearch OR CONCAT(usr.lastname,' ', usr.firstname) LIKE :filterSearch OR cat.path LIKE :filterSearch OR (obs.specific=1 AND CONCAT(cat.path,'/',ani.name_fr) LIKE :filterSearch)");
                    $qb->setParameter('filterSearch', '%' . $filter['filter'] . '%');
//                    $qb->andWhere("p.legend LIKE :filterSearch OR p.content LIKE :filterSearch");
//                    $qb->setParameter('filterSearch', '%' . $filter['filter'] . '%');
//                    $qb->andWhere("p.getSearch LIKE :filterSearch");
//                    $qb->setParameter('filterSearch', '%' . $filter['filter'] . '%');
                }
                if (!$this->getUser()->hasRole('ROLE_SUPER_ADMIN') || (isset($filter['sharing']) && $filter['sharing']!=-1)) {
                    $qb->andWhere("p.date IS NULL OR p.date <= :todaydate");
                    $qb->setParameter('todaydate', date("Y-m-d H:i:s"));
                }
            }
        }

        if ($landmark < 2) {
            $qb->andWhere('p.landmark = :landmark');
            $qb->setParameter('landmark', $landmark);
        }

        return $qb;
    }

    /**
     * Allow the visualisation of a publication
     *
     * @param Publication $publication
     * @throws \Symfony\Component\HttpKernel\Exception\HttpException
     */
    protected
    function authorizePublication(Publication $publication)
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

        $shareUsers = $publication->getShareusers();
        $arrShared = array();
        foreach ($shareUsers as $userShared) {
            $arrShared[] = $userShared->getId();
        }
        if (in_array($this->getUser()->getId(), $arrShared)){
            $authorized = true;
        }
        if (!$authorized && !$this->isConnected('ROLE_SUPER_ADMIN') && $publication->getOwner() != $this->getUser()) {
            throw new HttpException(Codes::HTTP_FORBIDDEN, $this->message('codes.403'));
        }
    }


    /**
     * Allow the visualisation of a favorite
     *
     * @param Favorite $favorite
     * @throws \Symfony\Component\HttpKernel\Exception\HttpException
     */
    protected
    function authorizeFavorite(Favorite $favorite)
    {
        $authorized = false;

        if ($this->getUser() == $favorite->getOwner()) {
            $authorized = true;
        }

        if (!$authorized && !$this->isConnected('ROLE_SUPER_ADMIN')) {
            throw new HttpException(Codes::HTTP_FORBIDDEN, $this->message('codes.403'));
        }
    }

    /**
     * Allow the visualisation of a group
     *
     * @param Group $group
     * @throws \Symfony\Component\HttpKernel\Exception\HttpException
     */
    protected
    function authorizeGroup(Group $group)
    {
        $authorized = false;

        foreach ($this->getUser()->getGroupSubscribes() as $userGroup) {
            if ($userGroup->getGroup()->getId() == $group->getId()) {
                $authorized = true;
            }
        }

        if (!$authorized && !$this->isConnected('ROLE_SUPER_ADMIN')) {
            throw new HttpException(Codes::HTTP_FORBIDDEN, $this->message('codes.403'));
        }
    }

    /**
     * Allow the visualisation of a hunt live
     *
     * @param Lounge $hunt
     * @throws \Symfony\Component\HttpKernel\Exception\HttpException
     */
    protected
    function authorizeLive(Lounge $hunt)
    {
        $authorized = false;

//        $currentDate = new \DateTime();
//        $loungeUser = $hunt->isSubscriber($this->getUser());
//        if (!is_null($loungeUser) && $hunt->getMeetingDate()->sub(new \DateInterval('P2D')) <= $currentDate && $hunt->getEndDate() >= $currentDate) {
//            $hunt->getMeetingDate()->add(new \DateInterval('P2D'));
//            $authorized = true;
//        }
        $authorized = $hunt->isLiveActive($this->getUser());

        if (!$authorized) {
            throw new HttpException(Codes::HTTP_FORBIDDEN, $this->message('codes.403'));
        }
    }

    /**
     * Allow the visualisation of a hunt
     *
     * @param Lounge $hunt
     * @throws \Symfony\Component\HttpKernel\Exception\HttpException
     */
    protected
    function authorizeHunt(Lounge $hunt)
    {
        $authorized = false;

        foreach ($this->getUser()->getLoungeSubscribes() as $userLounge) {
            if ($userLounge->getLounge()->getId() == $hunt->getId()) {
                $authorized = true;
            }
        }

        if (!$authorized && !$this->isConnected('ROLE_SUPER_ADMIN')) {
            throw new HttpException(Codes::HTTP_FORBIDDEN, $this->message('codes.403'));
        }
    }

    /**
     * Allow the visualisation of a weapon
     *
     * @param WeaponParameter $weapon
     * @throws \Symfony\Component\HttpKernel\Exception\HttpException
     */
    protected
    function authorizeWeapon(WeaponParameter $weapon)
    {
        $authorized = false;

        if ($this->getUser()->getId() == $weapon->getOwner()->getId()) {
            $authorized = true;
        }

        if (!$authorized && !$this->isConnected('ROLE_SUPER_ADMIN')) {
            throw new HttpException(Codes::HTTP_FORBIDDEN, $this->message('codes.403'));
        }
    }

    /**
     * Allow the visualisation of a dog
     *
     * @param DogParameter $dog
     * @throws \Symfony\Component\HttpKernel\Exception\HttpException
     */
    protected
    function authorizeDog(DogParameter $dog)
    {
        $authorized = false;

        if ($this->getUser()->getId() == $dog->getOwner()->getId()) {
            $authorized = true;
        }

        if (!$authorized && !$this->isConnected('ROLE_SUPER_ADMIN')) {
            throw new HttpException(Codes::HTTP_FORBIDDEN, $this->message('codes.403'));
        }
    }

    /**
     * Allow the visualisation of a paper
     *
     * @param PaperParameter $paperParameter
     * @throws \Symfony\Component\HttpKernel\Exception\HttpException
     */
    protected
    function authorizePaper(PaperParameter $paperParameter)
    {
        $authorized = false;

        if ($this->getUser()->getId() == $paperParameter->getOwner()->getId()) {
            $authorized = true;
        }

        if (!$authorized && !$this->isConnected('ROLE_SUPER_ADMIN')) {
            throw new HttpException(Codes::HTTP_FORBIDDEN, $this->message('codes.403'));
        }
    }

    /**
     * Retourne l'objet de créationd d'asset
     *
     * @return \Symfony\Component\Asset\Packages
     */
    protected
    function getAssetHelper()
    {
        return $this->get('assets.packages');
    }

    /**
     * Allow the visualisation of a group
     *
     * @param Group $group
     * @throws \Symfony\Component\HttpKernel\Exception\HttpException
     */
//    public static function getMarker($getColor = "orange", $getType = "circle", $getImg = "", $picto = true, $getDrag = false, $getDirect = false, $getBg = false) {
    public
    static function getMarker($getColor = "orange", $getType = "circle", $element = array(), $getDrag = false, $getDirect = false, $getBg = false, $getSmall = true)
    {

        switch ($getColor) {
            default:
            case "orange":
                $color = "rgb(217, 137, 56)";
                $stringColor = "orange";
                break;
            case "green":
                $color = "rgb(70, 196, 59)";
                $stringColor = "green";
                break;
            case "grey":
                $color = "rgb(133, 131, 133)";
                $stringColor = "grey";
                break;
        }
        $backgroundColor = "white";

        switch ($getType) {
            default:
            case "circle":
                $stringType = "circle";
                break;
            case "carre":
                $stringType = "carre";
                break;
        }
        $getImgName = "";
        $urlImg = "";
        $typeElem = "";
        $rep = $_SERVER["DOCUMENT_ROOT"] . "/img/map/";
        if (isset($element["publication"])) {
            $arrayReplace = array("/.png/" => "", "/.jpeg/" => "");
            $publication = $element["publication"];
            if (!is_null($publication->getPublicationcolor())) {
                $color = "#" . $publication->getPublicationcolor()->getColor();
                $stringColor = str_replace(" ", "_", $publication->getPublicationcolor()->getName());
                $backgroundColor = $publication->getPublicationcolor()->getBackground();
            }
            if ($element["type"] == "photo") {
                if (!is_null($publication->getMedia()) && $publication->getMedia()->getType() == \NaturaPass\PublicationBundle\Entity\PublicationMedia::TYPE_IMAGE) {
                    if (file_exists($_SERVER["DOCUMENT_ROOT"] . $publication->getMedia()->getWebPath())) {
                        $getImgName = preg_replace(array_keys($arrayReplace), array_values($arrayReplace), $publication->getMedia()->getName());
                        $urlImg = $_SERVER["DOCUMENT_ROOT"] . $publication->getMedia()->getWebPath();
                        $typeElem = "photo";
                    }
                }
            } else if ($element["type"] == "picto") {
                $observations = $publication->getObservations();

                foreach ($observations as $observation) {
                    if ($observation->getSpecific() == 1 && !is_null($observation->getAnimal()) && ($observation->getAnimal()->getPicto() instanceof \Admin\AnimalBundle\Entity\AnimalMedia)) {
                        if (file_exists($_SERVER["DOCUMENT_ROOT"] . $observation->getAnimal()->getPicto()->getPath())) {
                            $getImgName = preg_replace(array_keys($arrayReplace), array_values($arrayReplace), $observation->getAnimal()->getPicto()->getName());
                            $urlImg = $_SERVER["DOCUMENT_ROOT"] . $observation->getAnimal()->getPicto()->getPath();
                            $typeElem = "picto";
                        }
                    } else if (!is_null($observation->getCategory()) && ($observation->getCategory()->getPicto() instanceof \Admin\SentinelleBundle\Entity\CategoryMedia)) {
                        if (file_exists($_SERVER["DOCUMENT_ROOT"] . $observation->getCategory()->getPicto()->getPath())) {
                            $getImgName = preg_replace(array_keys($arrayReplace), array_values($arrayReplace), $observation->getCategory()->getPicto()->getName());
                            $urlImg = $_SERVER["DOCUMENT_ROOT"] . $observation->getCategory()->getPicto()->getPath();
                            $typeElem = "picto";
                        }
                    }
                }
            }
            if ($urlImg == "") {
                if (!is_null($publication->getMedia())) {
                    if ($publication->getMedia()->getType() == \NaturaPass\PublicationBundle\Entity\PublicationMedia::TYPE_VIDEO) {
                        $getImgName = "map_icon_video";
                        $urlImg = $rep . "map_icon_video.png";
                    } else {
                        $getImgName = "map_icon_photo";
                        $urlImg = $rep . "map_icon_photo.png";
                    }
                } else {
                    $getImgName = "map_icon_text";
                    $urlImg = $rep . "map_icon_text.png";
                }
            }
        } else if (isset($element["picto"])) {
            $getImgName = $element["picto"];
            $urlImg = $rep . $element["picto"] . ".png";
        }

        if (!empty($urlImg) && file_exists($urlImg)) {
            $srcfilname = $urlImg;
            $nameFilePicto = $getImgName;
        } else {
            $srcfilname = $rep . "map_icon_text.png";
            $nameFilePicto = "map_icon_text";
        }
        $dirFile = $_SERVER["DOCUMENT_ROOT"] . "/uploads/gmap/marker/" . (($getSmall) ? "website/" : "mobile/");
        $nameFile = "";
        if ($stringColor != "") {
            $nameFile .= $stringColor . "_";
        }
        if ($getType != "") {
            $nameFile .= $stringType . "_";
        }
        if ($getDrag != "") {
            $nameFile .= "drag_";
        }
        if ($getImgName != "") {
            $nameFile .= $nameFilePicto;
        }
        if (!file_exists($dirFile . $nameFile . ".png") || $getDirect) {
            $src = new \Imagick($srcfilname);

            if ($getBg) {
                $background = "rgb(125, 125, 125)";
            } else {
                $background = "none";
            }
            $image = new \Imagick();
            $image->newImage(128, 128, $background);
            $image->setImageFormat("png");
            switch ($stringType) {
                case "circle":
                    $pointsTriangle = [['x' => 30, 'y' => 98], ['x' => 64, 'y' => 128], ['x' => 98, 'y' => 98]];
                    $triangle = ApiRestController::polygonMarker("rgb(255, 255, 255)", "rgb(255, 255, 255)", 0, $pointsTriangle);
                    $circlColor = ($typeElem == "photo") ? $color : "rgb(255, 255, 255)";
                    $circleColor = ApiRestController::circleMarker($circlColor, $color, 64, 55, 97, 97);
                    $image->drawImage($triangle);
                    $image->drawImage($circleColor);
                    if ($typeElem == "photo") {
                        $src->resizeImage(128, 128, \Imagick::FILTER_LANCZOS, 1);
                        $src->setImageFormat('png');
                        $circleMask = new \Imagick();
                        $circleMask->newImage(128, 128, "none");
                        $circleMask->setImageFormat("png");
                        $circleMask->drawImage(ApiRestController::circleMarker("rgb(0, 0, 0)", "rgb(0, 0, 0)", 64, 64, 64, 124));
                        $src->compositeImage($circleMask, \Imagick::COMPOSITE_COPYOPACITY, 0, 0);
                        $src->resizeImage(104, 104, \Imagick::FILTER_LANCZOS, 1);
                        $image->compositeImage($src, \Imagick::COMPOSITE_DEFAULT, 12, 3);
//                        $image = $src;
                    } else {
                        $src->resizeImage(80, 80, \Imagick::FILTER_LANCZOS, 1);
                        $src->colorizeImage($backgroundColor, 0.0);
//                        $image = $src;
                        $image->compositeImage($src, \Imagick::COMPOSITE_DEFAULT, 25, 14);
                    }
                    break;
                case "carre":
                    $pointsTriangle = [['x' => 30, 'y' => 110], ['x' => 64, 'y' => 128], ['x' => 98, 'y' => 110]];
                    $triangle = ApiRestController::polygonMarker("rgb(255, 255, 255)", "rgb(255, 255, 255)", 0, $pointsTriangle);
                    $points2 = [['x' => 2, 'y' => 2], ['x' => 125, 'y' => 2], ['x' => 125, 'y' => 108], ['x' => 2, 'y' => 108]];
                    if ($typeElem == "photo") {
                        $carreColor = ApiRestController::polygonMarker($color, "rgb(255, 255, 255)", 4, $points2);
                    } else {
                        $carreColor = ApiRestController::polygonMarker("rgb(255, 255, 255)", $color, 4, $points2);
                    }
                    $image->drawImage($triangle);
                    $image->drawImage($carreColor);
                    if ($typeElem == "photo") {
                        $src->resizeImage(119, 102, \Imagick::FILTER_LANCZOS, 1);
                        $image->compositeImage($src, \Imagick::COMPOSITE_DEFAULT, 4, 4);
                    } else {
                        $src->resizeImage(90, 90, \Imagick::FILTER_LANCZOS, 1);
                        $src->colorizeImage($backgroundColor, 0.0);
                        $image->compositeImage($src, \Imagick::COMPOSITE_DEFAULT, 20, 10);
                    }
                    break;
            }
            if ($getSmall) {
                $image->resizeImage(32, 32, \Imagick::FILTER_LANCZOS, 1);
            } else {
                $image->resizeImage(80, 80, \Imagick::FILTER_LANCZOS, 1);
            }
//            if ($getDrag) {
//                $image->resizeImage(35, 35, \Imagick::FILTER_LANCZOS, 1);
//                $imageDrag = new \Imagick();
//                $imageDrag->newImage(35, 40, $background);
//                $imageDrag->setImageFormat("png");
//                $imageDrag->compositeImage($image, \Imagick::COMPOSITE_DEFAULT, 0, 5);
//
//                $filnameMove = $_SERVER["DOCUMENT_ROOT"] . "/img/map_icon_move.png";
//                $srcDrag = new \Imagick($filnameMove);
//                $srcDrag->resizeImage(15, 15, \Imagick::FILTER_LANCZOS, 1);
//                $imageDrag->compositeImage($srcDrag, \Imagick::COMPOSITE_DEFAULT, 0, 0);
//
//                $image = $imageDrag;
//            }
            if ($getDirect) {
                return $image->getImageBlob();
            } else {
                $image->writeImage($dirFile . $nameFile . ".png");
            }
        }
        if (!$getDirect) {
            return "/uploads/gmap/marker/" . (($getSmall) ? "website/" : "mobile/") . $nameFile . ".png";
        }
    }

    protected
    static function circleMarker($strokeColor, $fillColor, $originX, $originY, $endX, $endY)
    {
        //Create a ImagickDraw object to draw into.
        $draw = new \ImagickDraw();

        $strokeColor = new \ImagickPixel($strokeColor);
        $fillColor = new \ImagickPixel($fillColor);

        $draw->setStrokeOpacity(1);
        $draw->setStrokeColor($strokeColor);
        $draw->setFillColor($fillColor);

        $draw->setStrokeWidth(4);
        $draw->setFontSize(72);

        $draw->circle($originX, $originY, $endX, $endY);
        return $draw;
    }

    protected
    static function polygonMarker($strokeColor, $fillColor, $strokeWidth, $points)
    {
        $draw = new \ImagickDraw();

        $draw->setStrokeOpacity(1);
        $draw->setStrokeColor($strokeColor);
        $draw->setStrokeWidth($strokeWidth);

        $draw->setFillColor($fillColor);

        $draw->polygon($points);
        return $draw;
    }

    protected
    function checkDuplicatedPublication(Publication $publication)
    {
        $manager = $this->getDoctrine()->getManager();
        $query = $manager
            ->createQueryBuilder('p')->select('p')
            ->from('NaturaPassPublicationBundle:Publication', 'p')
            ->where('p.owner = :user')
            ->andWhere('p.guid = :guid')
            ->andWhere($publication->getLegend() ? 'p.legend = :legend' : 'p.legend IS NULL')
            ->andWhere('p.id != :id');

        if ($publication->getCreated() != "") {
            $query->andWhere('p.created = :date');
            $query->setParameter('date', $publication->getCreated()->format("Y-m-d H:i:s"));
        }
        if ($publication->getLegend()) {
            $query->setParameter('legend', $publication->getLegend());
        }
        $query->setParameter('guid', $publication->getGuid());
        $query->setParameter('user', $this->getUser());
        $query->setParameter('id', $publication->getId());
        $response = $query->getQuery()->getResult();
        if (count($response)) {
            return $response;
        }
        return false;
    }

    protected
    function checkDuplicatedGroupMessage(GroupMessage $groupMessage)
    {
        $manager = $this->getDoctrine()->getManager();
        $query = $manager
            ->createQueryBuilder('p')->select('p')
            ->from('NaturaPassGroupBundle:GroupMessage', 'p')
            ->where('p.owner = :user')
            ->andWhere('p.content = :content')
            ->andWhere('p.group = :group')
            ->andWhere('p.id != :id');

        if ($groupMessage->getCreated() != "") {
            $query->andWhere('p.created = :date');
            $query->setParameter('date', $groupMessage->getCreated()->format("Y-m-d H:i:s"));
        }
        
        $query->setParameter('content', $groupMessage->getContent());
        $query->setParameter('user', $this->getUser());
        $query->setParameter('id', $groupMessage->getId());
        $query->setParameter('group', $groupMessage->getGroup()->getId());
        $response = $query->getQuery()->getResult();
        if (count($response)) {
            return $response[0];
        }
        return false;
    }

    /**
     * @return string
     */
    protected function getBaseUrl()
    {
        return $this->getRequest()->getScheme() . '://' . $this->getRequest()->getHttpHost();
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

}
