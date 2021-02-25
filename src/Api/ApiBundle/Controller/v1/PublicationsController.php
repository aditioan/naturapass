<?php

namespace Api\ApiBundle\Controller\v1;

use Admin\SentinelleBundle\Entity\Locality;
use Doctrine\ORM\Query\Expr;
use Doctrine\ORM\Query\Expr\Join;
use Doctrine\ORM\QueryBuilder;
use NaturaPass\GraphBundle\Entity\Edge;
use NaturaPass\MainBundle\Component\Security\SecurityUtilities;
use NaturaPass\NotificationBundle\Entity\Publication\PublicationCommentedNotification;
use NaturaPass\NotificationBundle\Entity\Publication\PublicationLikedNotification;
use NaturaPass\NotificationBundle\Entity\Publication\PublicationSameCommentedNotification;
use NaturaPass\PublicationBundle\Entity\PublicationReport;
use NaturaPass\MediaBundle\Entity\BaseMedia;
use NaturaPass\PublicationBundle\Form\Type\PublicationFormType;
use Exception;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\HttpFoundation\Request;
use FOS\RestBundle\Controller\Annotations\View;
use FOS\RestBundle\Util\Codes;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use NaturaPass\PublicationBundle\Entity\Publication;
use NaturaPass\PublicationBundle\Entity\PublicationDeleted;
use NaturaPass\PublicationBundle\Entity\PublicationComment;
use NaturaPass\PublicationBundle\Entity\PublicationCommentAction;
use NaturaPass\PublicationBundle\Entity\PublicationAction;
use NaturaPass\MainBundle\Entity\Sharing;
use NaturaPass\PublicationBundle\Form\Handler\PublicationHandler;
use NaturaPass\UserBundle\Entity\User;
use NaturaPass\UserBundle\Entity\UserFriend;
use NaturaPass\MainBundle\Entity\Geolocation;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\HttpException;

/**
 * Description of PublicationsController
 *
 * @author vincentvalot
 */
class PublicationsController extends ApiRestController
{

    /**
     * Get the observations of a publication
     *
     * GET /publications/{publication_id}/observations
     *
     * @param Publication $publication
     * @return \FOS\RestBundle\View\View
     *
     * @ParamConverter("publication", class="NaturaPassPublicationBundle:Publication")
     * @View(serializerGroups={"ObservationDetail", "AttachmentDetail"})
     */
    public function getPublicationObservationsAction(Publication $publication)
    {
        $this->authorize();

        return $this->view(array('observations' => $publication->getObservations()));
    }

    /**
     * Gets a publication locality
     *
     * GET /publications/{publication_id}/locality
     *
     * @param Publication $publication
     * @throws HttpException
     *
     * @return \FOS\RestBundle\View\View
     *
     * @ParamConverter("publication", class="NaturaPassPublicationBundle:Publication")
     * @View(serializerGroups={"LocalityDetail"})
     */
    public function getPublicationLocalityAction(Publication $publication)
    {
        $this->authorize();

        $geolocation = $publication->getGeolocation();

        if ($geolocation instanceof Geolocation) {
            try {
                $locality = $publication->getLocality();

                if (!$locality instanceof Locality) {
                    $locality = $this->getGeolocationService()->findACity($geolocation, true);

                    if ($locality instanceof Locality) {
                        $publication->setLocality($locality);

                        $manager = $this->getDoctrine()->getManager();

                        $manager->persist($publication);
                        $manager->flush();
                    }
                }

                return $this->view(array('locality' => $locality));
            } catch (Exception $exception) {
                throw new HttpException(Codes::HTTP_INTERNAL_SERVER_ERROR, $exception->getMessage());
            }
        }

        throw new BadRequestHttpException($this->message('errors.publication.nogeolocation'));
    }

    /**
     * Retourne toutes les publications localisées dans une zone précise
     *
     * GET /publications/map?swLat=46.030580621651566&swLng=4.899201278686519&neLat=46.343846624129334&neLng=5.805573348999019&sharing=3&reset=1&group=2
     *
     * Coordonnées des points Nord-Est et Sud-Ouest aux extrémités de la map
     * Reset permet de réinitialiser les zones chargées
     * Group permet de charger uniquement les publications d'un groupe
     *
     * @param Request $request
     * @return \FOS\RestBundle\View\View
     *
     * @View(serializerGroups={"MediaDetail", "TagLess", "SharingLess", "SharingWithouts", "UserLess", "GeolocationLess", "PublicationGroup", "GroupLess"})
     */
    public function getPublicationsMapAction(Request $request)
    {
        $this->authorize();

        $swLat = $request->query->get('swLat', false);
        $swLng = $request->query->get('swLng', false);
        $neLat = $request->query->get('neLat', false);
        $neLng = $request->query->get('neLng', false);
        $sharing = $request->query->get('sharing', -1);
        $group = $request->query->get('group', false);

        if (!$swLat && !$swLng && !$neLat && !$neLng && !$sharing && !$group) {
            throw new HttpException(Codes::HTTP_BAD_REQUEST);
        }

        if ($request->query->has('reset')) {
            $this->get('session')->remove('naturapass_map/positions_loaded');
        }

        $qb = $this->getDoctrine()->getManager()->createQueryBuilder();

        if (!$this->getUser()->hasRole('ROLE_SUPER_ADMIN')) {
            if ($sharing >= Sharing::USER && $sharing <= Sharing::NATURAPASS) {
                $qb = $this->getSharingQueryBuilder('NaturaPassPublicationBundle:Publication', 'p', $sharing, 2);
            } else if ($group) {
                $group = $this->getDoctrine()->getRepository('NaturaPassGroupBundle:Group')->find($group);
                if (!is_object($group)) {
                    throw new HttpException(Codes::HTTP_BAD_REQUEST);
                } else {
                    if (!$group->isSubscriber($this->getUser())) {
                        throw new HttpException(Codes::HTTP_FORBIDDEN, $this->message('errors.group.subscriber.unregistered'));
                    }
                }
                $qb->select(array('p'))
                    ->from('NaturaPassPublicationBundle:Publication', 'p')
                    ->join('p.groups', 'gr', 'WITH', 'gr.id = :group')
                    ->setParameter(':group', $group->getId())
                    ->orderBy('p.created', 'DESC');
            }
        } else {
            $manager = $this->getDoctrine()->getManager();

            /**
             * @var QueryBuilder $qb
             */
            $class = 'NaturaPassPublicationBundle:Publication';
            $alias = 'p';
            $landmark = false;
            $qb = $manager->createQueryBuilder()->select($alias)
                ->from($class, $alias)
                ->join('NaturaPassMainBundle:Sharing', 's', Join::WITH, 's = ' . $alias . '.sharing');


            if (!$this->getUser()->hasRole('ROLE_SUPER_ADMIN')) {
                $wheres = $qb->expr()->orX();

                if ($sharing >= Sharing::USER) {
                    $wheres->add($qb->expr()->eq($alias . '.owner', ':owner'));
                    $qb->setParameter('owner', $this->getUser());
                }

                if ($sharing >= Sharing::FRIENDS) {
                    $friends = $this->getUser()->getFriends(UserFriend::TYPE_FRIEND);

                    if (!$friends->isEmpty()) {
//                        $wheres->add($qb->expr()->andx(
//                                        $qb->expr()->eq('s.share', Sharing::FRIENDS), $qb->expr()->in($alias . '.owner', ':friends')
//                        ));
//
                        $wheres->add($qb->expr()->orX(
                            $qb->expr()->andx(
                                $qb->expr()->eq('s.share', Sharing::FRIENDS), $qb->expr()->in($alias . '.owner', ':friends')
                            ), $qb->expr()->andx(
                            $qb->expr()->eq('s.share', Sharing::NATURAPASS), $qb->expr()->in($alias . '.owner', ':friends')
                        )
                        ));

                        $qb->setParameter('friends', $friends->toArray());
                    }
                }

                if ($sharing >= Sharing::NATURAPASS) {
                    $wheres->add($qb->expr()->eq('s.share', Sharing::NATURAPASS));
                }

                $qb->where($wheres);

                $qb->andWhere($qb->expr()->notIn(
                    ':connected', $manager->createQueryBuilder()->select('w.id')
                    ->from('NaturaPassMainBundle:Sharing', 's2')
                    ->innerJoin('s2.withouts', 'w')
                    ->where('s.id = s2.id')
                    ->getDql()
                ));
                $qb->setParameter('connected', $this->getUser()->getId());
            }

            if ($landmark < 2) {
                $qb->andWhere('p.landmark = :landmark');
                $qb->setParameter('landmark', $landmark);
            }
        }

        $qb->join('p.geolocation', 'g')
            ->andWhere(
                $qb->expr()->andx(
                    $qb->expr()->between('g.latitude', $swLat, $neLat), $qb->expr()->between('g.longitude', $swLng, $neLng)
                )
            );

        $alreadyLoaded = $this->get('session')->get('naturapass_map/positions_loaded');
        if (is_array($alreadyLoaded)) {
            foreach ($alreadyLoaded as $rectangle) {
                list($sw, $ne) = $rectangle;
                $qb->andWhere(
                    $qb->expr()->andx(
                        $qb->expr()->not(
                            $qb->expr()->andx(
                                $qb->expr()->between('g.latitude', $sw->getLatitude(), $ne->getLatitude()), $qb->expr()->between('g.longitude', $sw->getLongitude(), $ne->getLongitude())
                            )
                        )
                    )
                );
            }
        }

        $northEast = new Geolocation();
        $northEast->setLatitude($neLat)
            ->setLongitude($neLng);

        $southWest = new Geolocation();
        $southWest->setLatitude($swLat)
            ->setLongitude($swLng);

        $results = $qb->setMaxResults(500)
            ->getQuery()
            ->getResult();

        $alreadyLoaded[] = array($southWest, $northEast);
        $this->get('session')->set('naturapass_map/positions_loaded', $alreadyLoaded);

        $publications = array();
        foreach ($results as $publication) {
            $publications[] = $this->getFormatPublicationComment($publication);
        }

        return $this->view(array('publications' => $publications), Codes::HTTP_OK);
    }

    /**
     * Retourne toutes les publications localisées dans une zone précise
     *
     * GET /publications/map/filter?swLat=46.030580621651566&swLng=4.899201278686519&neLat=46.343846624129334&neLng=5.805573348999019&group=[8,3]&sharing=4&reset=1
     *
     * Coordonnées des points Nord-Est et Sud-Ouest aux extrémités de la map
     * Reset permet de réinitialiser les zones chargées
     * Group permet de charger uniquement les publications d'un groupe
     *
     * @param Request $request
     * @return \FOS\RestBundle\View\View
     *
     * @View(serializerGroups={"MediaDetail", "TagLess", "SharingLess", "SharingWithouts", "UserLess", "GeolocationLess", "PublicationGroup", "GroupLess"})
     */
    public function getPublicationsMapFilterAction(Request $request)
    {
        $this->authorize();

        $swLat = $request->query->get('swLat', false);
        $swLng = $request->query->get('swLng', false);
        $neLat = $request->query->get('neLat', false);
        $neLng = $request->query->get('neLng', false);
        $sharing = $request->query->get('sharing', -1);
        $group = $request->query->get('group', false);

        if (!$swLat && !$swLng && !$neLat && !$neLng && !$sharing && !$group) {
            throw new HttpException(Codes::HTTP_BAD_REQUEST);
        }

        if ($request->query->has('reset')) {
            $this->get('session')->remove('naturapass_map/positions_loaded_filter');
        }

        $filter = array();
        $result = false;
        if ($request->query->has('sharing') && $request->query->get('sharing') > -1) {
            $filter["sharing"] = $request->query->get('sharing');
            $result = true;
        }
        $getGroups = $request->query->get('group');
        $replaceGroups = str_replace(array("[", "]"), array("", ""), $getGroups);
        $groups = explode(",", $replaceGroups);
        $all_groups = $this->getUser()->getAllGroups();
        $groupValid = array();
        foreach ($groups as $id_group) {
            foreach ($all_groups as $group) {
                if ($id_group == $group->getId()) {
                    $groupValid[] = $id_group;
                    $result = true;
                }
            }
        }
        if (count($groupValid)) {
            $filter["group"] = $groupValid;
        }
        if ($this->getUser()->hasRole('ROLE_SUPER_ADMIN')) {
            $result = true;
        }
        $publications = array();
        if ($result) {
            $qb = $this->getSharingQueryBuilderFilter(
                'NaturaPassPublicationBundle:Publication', 'p', $filter
            );

            $qb->join('p.geolocation', 'ge')
                ->andWhere(
                    $qb->expr()->andx(
                        $qb->expr()->between('ge.latitude', $swLat, $neLat), $qb->expr()->between('ge.longitude', $swLng, $neLng)
                    )
                );

            $alreadyLoaded = $this->get('session')->get('naturapass_map/positions_loaded_filter');
            if (is_array($alreadyLoaded)) {
                foreach ($alreadyLoaded as $rectangle) {
                    list($sw, $ne) = $rectangle;
                    $qb->andWhere(
                        $qb->expr()->andx(
                            $qb->expr()->not(
                                $qb->expr()->andx(
                                    $qb->expr()->between('ge.latitude', $sw->getLatitude(), $ne->getLatitude()), $qb->expr()->between('ge.longitude', $sw->getLongitude(), $ne->getLongitude())
                                )
                            )
                        )
                    );
                }
            }

            $northEast = new Geolocation();
            $northEast->setLatitude($neLat)
                ->setLongitude($neLng);

            $southWest = new Geolocation();
            $southWest->setLatitude($swLat)
                ->setLongitude($swLng);

            $results = $qb->setMaxResults(500)
                ->getQuery()
                ->getResult();

            $alreadyLoaded[] = array($southWest, $northEast);
            $this->get('session')->set('naturapass_map/positions_loaded_filter', $alreadyLoaded);

            foreach ($results as $publication) {
                $publications[] = $this->getFormatPublicationComment($publication);
            }
        }

        return $this->view(array('publications' => $publications), Codes::HTTP_OK);
    }

    /**
     * Récupère les publications avec les options passées en paramètre
     *
     * GET /publications?limit=20&sharing=4&offset=20
     *
     * Valeurs possibles pour sharing (voir constantes de classes Publication)
     *      0 => ME
     *      1 => FRIENDS
     *      2 => KNOWING
     *      3 => NATURAPASS
     *      4 => PUBLIC
     * limit:   Nombre de valeurs retournés
     * offset:  Position à partir de laquelle il faut récupérer
     *
     * @param Request $request
     * @return \FOS\RestBundle\View\View
     *
     * @View(serializerGroups={"MediaDetail", "TagLess", "SharingLess", "SharingWithouts", "UserLess", "GeolocationLess", "PublicationGroup", "GroupLess"})
     */
    public function getPublicationsAction(Request $request)
    {
        $this->authorize();

        $qb = $this->getSharingQueryBuilder(
            'NaturaPassPublicationBundle:Publication', 'p', $request->query->get('sharing', Sharing::NATURAPASS)
        );
        $limit = $request->query->get('limit', 10);
        $offset = $request->query->get('offset', 0);

        $results = $qb->orderBy('p.created', 'DESC')
            ->setFirstResult($offset)
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();

        $publications = array();
        foreach ($results as $publication) {
            $publications[] = $this->getFormatPublicationComment($publication);
        }

        return $this->view(array('publications' => $publications), Codes::HTTP_OK);
    }

    /**
     * Récupère les publications avec les options passées en paramètre
     *
     * GET /publications/filter?group=[8,3]&limit=20&sharing=4&offset=20
     *
     * Valeurs possibles pour sharing (voir constantes de classes Publication)
     *      0 => ME
     *      1 => FRIENDS
     *      3 => NATURAPASS
     * limit:   Nombre de valeurs retournés
     * offset:  Position à partir de laquelle il faut récupérer
     *
     * @param Request $request
     * @return \FOS\RestBundle\View\View
     *
     * @View(serializerGroups={"MediaDetail", "TagLess", "UserLess", "GeolocationLess", "PublicationGroup", "GroupLess"})
     */
    public function getPublicationsFilterAction(Request $request)
    {
        $this->authorize();
        $filter = array();
        $result = false;
        if ($request->query->has('sharing') && $request->query->get('sharing') > -1) {
            $filter["sharing"] = $request->query->get('sharing');
            $result = true;
        }
        $getGroups = $request->query->get('group');
        $replaceGroups = str_replace(array("[", "]"), array("", ""), $getGroups);
        $groups = explode(",", $replaceGroups);
        $all_groups = $this->getUser()->getAllGroups();
        $groupValid = array();
        foreach ($groups as $id_group) {
            foreach ($all_groups as $group) {
                if ($id_group == $group->getId()) {
                    $groupValid[] = $id_group;
                    $result = true;
                }
            }
        }
        if ($this->getUser()->hasRole('ROLE_SUPER_ADMIN')) {
            $result = true;
        }
        if (count($groupValid)) {
            $filter["group"] = $groupValid;
        }
        $publications = array();
        if ($result) {
            $qb = $this->getSharingQueryBuilderFilter(
                'NaturaPassPublicationBundle:Publication', 'p', $filter
            );

            $limit = $request->query->get('limit', 10);
            $offset = $request->query->get('offset', 0);

            $results = $qb->orderBy('p.created', 'DESC')
                ->setFirstResult($offset)
                ->setMaxResults($limit)
                ->getQuery()
                ->getResult();
            foreach ($results as $publication) {
                $publications[] = $this->getFormatPublicationComment($publication);
            }
        }

        return $this->view(array('publications' => $publications), Codes::HTTP_OK);
    }

    /**
     * Récupère les publications d'un utilisateur avec les options passées en paramètre
     *
     * GET /publications/{user_id}/user?limit=20&offset=0
     *
     * limit:   Nombre de valeurs retournés
     * offset:  Position à partir de laquelle il faut récupérer
     *
     * @param Request $request
     * @param User $user
     *
     * @return \FOS\RestBundle\View\View
     *
     * @View(serializerGroups={"MediaDetail", "TagLess", "SharingLess", "SharingWithouts", "UserLess", "GeolocationLess", "PublicationGroup", "GroupLess"})
     * @ParamConverter("user", class="NaturaPassUserBundle:User")
     */
    public function getPublicationsUserAction(Request $request, User $user)
    {
        $this->authorize();

        $limit = $request->query->get('limit', 10);
        $offset = $request->query->get('offset', 0);

        $sharing = Sharing::NATURAPASS;

        if ($this->getUser() == $user) {
            $sharing = Sharing::USER;
        } else {
            if ($this->getUser()->hasFriendshipWith($user, array(UserFriend::CONFIRMED))) {
                $sharing = Sharing::FRIENDS;
            }
        }

        $manager = $this->getDoctrine()->getManager();
        $qb = $manager->createQueryBuilder()->select('p')
            ->from('NaturaPassPublicationBundle:Publication', 'p')
            ->innerJoin('p.sharing', 's')
            ->where('p.owner = :owner')
            ->orderBy('p.created', 'DESC')
            ->setParameter('owner', $user)
            ->setMaxResults($limit)
            ->setFirstResult($offset);

        if (!$this->isConnected('ROLE_SUPER_ADMIN')) {
            $qb
                ->andWhere($qb->expr()->between('s.share', $sharing, Sharing::NATURAPASS))
                ->andWhere($qb->expr()->notIn(
                    ':connected', $manager->createQueryBuilder()->select('w.id')
                    ->from('NaturaPassMainBundle:Sharing', 's2')
                    ->innerJoin('s2.withouts', 'w')
                    ->where('s.id = s2.id')
                    ->getDql()
                ));
            $qb->setParameter('connected', $this->getUser()->getId());
        }

        $results = $qb->getQuery()->getResult();

        $publications = array();
        foreach ($results as $publication) {
            $publications[] = $this->getFormatPublicationComment($publication);
        }

        return $this->view(array('publications' => $publications), Codes::HTTP_OK);
    }

    /**
     * Récupère les commentaires d'une publication, triés par date de création décroissante
     *
     * GET /publication/{publication_id}/comments?limit=20&loaded=5
     *
     * limit:   Nombre de commentaires à retourner au maximum
     * loaded:  Nombre de commentaires déjà chargé
     *
     *
     * @param Request $request
     * @param \NaturaPass\PublicationBundle\Entity\Publication $publication
     * @return \FOS\RestBundle\View\View
     *
     * @ParamConverter("publication", class="NaturaPassPublicationBundle:Publication")
     */
    public function getPublicationCommentsAction(Request $request, Publication $publication)
    {
        $limit = $request->query->get('limit', 20);
        $loaded = $request->query->get('loaded', 0);

        $total = count($publication->getComments());

        if (($total - $loaded) < $limit) {
            $limit = $total - $loaded;
        }

        $offset = $loaded;

        $repo = $this->getDoctrine()->getManager()->getRepository('NaturaPassPublicationBundle:PublicationComment');
        $return = $repo->findBy(array('publication' => $publication), array('created' => 'DESC'), $limit, $offset);
        $comments = array();
        foreach ($return as $comment) {
            $comments[] = $this->getFormatComment($comment);
        }

        return $this->view(array('comments' => $comments, 'loaded' => count($comments)), Codes::HTTP_OK);
    }

    /**
     * Récupère les utilisateurs d'une publication ayant réalisé une action sur cette dernière
     *
     * GET /publication/{publication_id}/users/{state}/action
     *
     * @param \NaturaPass\PublicationBundle\Entity\Publication $publication
     * @param int $state
     *
     * @ParamConverter("publication", class="NaturaPassPublicationBundle:Publication")
     *
     * @View(serializerGroups={"UserDetail", "PublicationUserAction"})
     *
     * @return \FOS\RestBundle\View\View
     */
    public function getPublicationUsersActionAction(Publication $publication, $state)
    {
        $users = array();
        foreach ($publication->getActions($state) as $actions) {
            $users[] = array(
                'fullname' => $actions->getUser()->getFullname(),
                'usertag' => $actions->getUser()->getUsertag(),
                'profilepicture' => $actions->getUser()->getProfilePicture() ? $actions->getUser()->getProfilePicture()->getThumb() : $this->getAssetHelper()->getUrl('img/default-avatar.jpg'),
            );
        }

        return $this->view(array('users' => $users), Codes::HTTP_OK);
    }

    /**
     * Récupère la publication d'identifiant passé en paramètre
     *
     * GET /publications/{publication}
     *
     *
     * @param \NaturaPass\PublicationBundle\Entity\Publication $publication
     * @return \FOS\RestBundle\View\View
     * @throws HttpException
     *
     * @View(serializerGroups={"MediaDetail", "TagLess", "SharingLess", "SharingWithouts", "UserLess", "GeolocationLess", "PublicationGroup", "GroupLess"})
     * @ParamConverter("publication", class="NaturaPassPublicationBundle:Publication")
     */
    public function getPublicationAction(Publication $publication)
    {
        $this->authorize();
        $this->authorizePublication($publication);

        return $this->view(
            array('publication' => $this->getFormatPublicationComment($publication)), Codes::HTTP_OK
        );
    }

    /**
     * Récupère les actions sur une publication
     *
     * GET /publications/{publication}/actions
     *
     *
     * @param \NaturaPass\PublicationBundle\Entity\Publication $publication
     * @return \FOS\RestBundle\View\View
     *
     * @ParamConverter("publication", class="NaturaPassPublicationBundle:Publication")
     */
    public function getPublicationActionsAction(Publication $publication)
    {
        $this->authorize();
        $this->authorizePublication($publication);

        $results = $publication->getActions();
        $likes = array();
        $unlikes = array();

        foreach ($results as $action) {
            if ($action->getState() === PublicationAction::STATE_LIKE) {
                $likes[] = $this->getFormatUser($action->getUser());
            } else {
                $unlikes[] = $this->getFormatUser($action->getUser());
            }
        }

        return $this->view(array('likes' => $likes, 'unlikes' => $unlikes), Codes::HTTP_OK);
    }

    /**
     * Ajoute un like utilisateur sur un commentaire de publication
     *
     * PUT /publications/{comment}/comment/like
     *
     *
     * @param \NaturaPass\PublicationBundle\Entity\PublicationComment $comment
     *
     * @ParamConverter("comment", class="NaturaPassPublicationBundle:PublicationComment")
     *
     * @return \FOS\RestBundle\View\View
     */
    public function putPublicationCommentLikeAction(PublicationComment $comment)
    {
        $this->authorize();
        $this->authorizePublication($comment->getPublication());

        $manager = $this->getDoctrine()->getManager();

        $like = $manager->getRepository('NaturaPassPublicationBundle:PublicationCommentAction')->findOneBy(
            array(
                'user' => $this->getUser(),
                'comment' => $comment
            )
        );

        if (!$like) {
            $like = new PublicationCommentAction;
            $like->setComment($comment)
                ->setUser($this->getUser());
        }

        $like->setState(PublicationAction::STATE_LIKE);
        $manager->persist($like);
        $manager->flush();

        return $this->view(
            array(
                'likes' => $comment->getActions(PublicationCommentAction::STATE_LIKE)->count(),
                'unlikes' => $comment->getActions(PublicationCommentAction::STATE_UNLIKE)->count(),
            ), Codes::HTTP_OK
        );
    }

    /**
     * Fait pivoter l'image de la publication
     *
     * PUT /publications/{publication}/rotate/{deg}
     *
     *
     * @param \NaturaPass\PublicationBundle\Entity\Publication $publication
     * @param int $degree
     *
     * @ParamConverter("publication", class="NaturaPassPublicationBundle:Publication")
     *
     * @View(serializerGroups={"MediaDetail"})
     *
     * @return \FOS\RestBundle\View\View
     * @throws HttpException
     */
    public function putPublicationRotateAction(Publication $publication, $degree)
    {
        $this->authorize($publication->getOwner());

        $media = $publication->getMedia();
        if (is_object($media)) {
            $path = $media->rotate($degree);

            $manager = $this->getDoctrine()->getManager();
            $manager->persist($media);
            $manager->flush();

            BaseMedia::_unlink($path);

            return $this->view(array('media' => $media), Codes::HTTP_OK);
        }

        throw new HttpException(Codes::HTTP_BAD_REQUEST, $this->message('errors.publication.empty.media'));
    }

    /**
     * Crop l'image de la publication
     *
     * PUT /publications/{publication}/crop/{deg}
     *
     * @param \NaturaPass\PublicationBundle\Entity\Publication $publication
     * @param Request $request
     *
     * @ParamConverter("publication", class="NaturaPassPublicationBundle:Publication")
     *
     * @View(serializerGroups={"MediaDetail"})
     *
     * @return \FOS\RestBundle\View\View
     * @throws HttpException
     */
    public function putPublicationCropAction(Publication $publication, Request $request)
    {
        $this->authorize($publication->getOwner());

        $coords = $request->request->get('coords', false);

        $media = $publication->getMedia();
        if (is_object($media) && $coords) {
            $old = $media->crop($coords);

            $manager = $this->getDoctrine()->getManager();
            $manager->persist($media);
            $manager->flush();

            BaseMedia::_unlink($old);

            return $this->view(array('media' => $media), Codes::HTTP_OK);
        }
        throw new HttpException(Codes::HTTP_BAD_REQUEST);
    }

    /**
     * Ajoute un unlike utilisateur sur un commentaire de publication
     *
     * PUT /publications/{comment}/comment/unlike
     *
     *
     * @param \NaturaPass\PublicationBundle\Entity\PublicationComment $comment
     *
     * @ParamConverter("comment", class="NaturaPassPublicationBundle:PublicationComment")
     *
     * @return \FOS\RestBundle\View\View
     */
    public function putPublicationCommentUnlikeAction(PublicationComment $comment)
    {
        $this->authorize();
        $this->authorizePublication($comment->getPublication());

        $manager = $this->getDoctrine()->getManager();

        $like = $manager->getRepository('NaturaPassPublicationBundle:PublicationCommentAction')->findOneBy(
            array(
                'user' => $this->getUser(),
                'comment' => $comment
            )
        );

        if (!$like) {
            $like = new PublicationCommentAction;
            $like->setComment($comment)
                ->setUser($this->getUser());
        }

        $like->setState(PublicationAction::STATE_UNLIKE);
        $manager->persist($like);
        $manager->flush();

        return $this->view(
            array(
                'likes' => $comment->getActions(PublicationCommentAction::STATE_LIKE)->count(),
                'unlikes' => $comment->getActions(PublicationCommentAction::STATE_UNLIKE)->count(),
            ), Codes::HTTP_OK
        );
    }

    /**
     * Ajoute un unlike utilisateur sur une publication
     *
     * PUT /publications/{publication}/unlike
     *
     *
     * @param \NaturaPass\PublicationBundle\Entity\Publication
     *
     * @ParamConverter("publication", class="NaturaPassPublicationBundle:Publication")
     *
     * @return \FOS\RestBundle\View\View
     */
    public function putPublicationUnlikeAction(Publication $publication)
    {
        $this->authorize();
        $this->authorizePublication($publication);

        $manager = $this->getDoctrine()->getManager();

        $like = $manager->getRepository('NaturaPassPublicationBundle:PublicationAction')->findOneBy(
            array(
                'user' => $this->getUser(),
                'publication' => $publication
            )
        );

        if (!$like) {
            $like = new PublicationAction;
            $like->setPublication($publication)
                ->setUser($this->getUser());
        }

        $like->setState(PublicationAction::STATE_UNLIKE);

//        $this->delay(function () use ($publication) {
//            $this->getGraphService()->deleteEdgeOf($this->getUser(), Edge::PUBLICATION_LIKED, $publication->getId());
//        });

        $manager->persist($like);
        $manager->flush();

//        $this->getGraphService()->generateEdge($this->getUser(), $publication->getOwner(), Edge::PUBLICATION_UNLIKED, $publication->getId());

        return $this->view(
            array(
                'likes' => $publication->getActions(PublicationAction::STATE_LIKE)->count(),
                'unlikes' => $publication->getActions(PublicationAction::STATE_UNLIKE)->count(),
            ), Codes::HTTP_OK
        );
    }

    /**
     * Edition d'une publication par son auteur
     *
     * PUT /publications/{publication}
     *
     * Content-Type: Form-Data (pour média) ou JSON (sans média)
     *      publication[content] = "Aujourd'hui j'ai tué 5 lapins"
     *
     *      publication[sharing][share] = 3,
     *      publication[sharing][withouts] = [17, 18] // Identifiants d'utilisateurs non autorisés à voir la publication
     *
     *      publication[groups] = [1, 2, 3]
     *
     *      publication[geolocation][latitude] = "45.75",
     *      publication[geolocation][longitude] = "4.85",
     *      publication[geolocation][address] = "Adresse au format Google"
     *
     *      publication[media][legend] = "Un des lapins en question"
     *      publication[media][tags] = ["Chasse", "Natura"]
     *
     * La géolocalisation, les groupes, les exclus (withouts) étant des entités directement liées à la publication, si elle ne sont pas présentes des données de mise à jour, elles seront supprimées
     *
     *
     * @param \NaturaPass\PublicationBundle\Entity\Publication $publication
     * @param Request $request
     *
     * @ParamConverter("publication", class="NaturaPassPublicationBundle:Publication")
     *
     * @return \FOS\RestBundle\View\View
     */
    public function putPublicationAction(Publication $publication, Request $request)
    {
        $this->authorize($publication->getOwner());

        $params = $request->request->get('publication');
        if (empty($params["created"])) {
            $params["created"] = $publication->getCreated()->format(\DateTime::ATOM);
        }
        $arrayPublication = array(
            'publication' => $params);
        $requestPublication = new Request($_GET, $arrayPublication, array(), $_COOKIE, $_FILES, $_SERVER);
        $form = $this->createForm(
            new PublicationFormType($this->getSecurityTokenStorage(), $this->container), $publication, array('csrf_protection' => false, 'method' => 'PUT')
        );


        $handler = new PublicationHandler($form, $requestPublication, $this->getDoctrine()->getManager(), $this->getSecurityTokenStorage(), $this->getGeolocationService());

        if ($handler->process()) {
            return $this->view($this->success(), Codes::HTTP_OK);
        }

        return $this->view($form->getErrors(true, false), Codes::HTTP_BAD_REQUEST);
    }

    /**
     * Edition de la geolocation d'une publication par son auteur
     *
     * PUT /publications/{publication}/geolocation
     *
     * {
     *      "geolocation": {
     *          "latitude": "54.2323",
     *          "longitude": "4.4334",
     *          "address": "59 Avenue du Revermont"
     *      }
     * }
     *
     * @param \NaturaPass\PublicationBundle\Entity\Publication $publication
     * @param Request $request
     *
     * @ParamConverter("publication", class="NaturaPassPublicationBundle:Publication")
     *
     * @return \FOS\RestBundle\View\View
     * @throws HttpException
     */
    public function putPublicationGeolocationAction(Publication $publication, Request $request)
    {
        $this->authorize($publication->getOwner());

        $request = $request->request;

        $latitude = $request->get('geolocation[latitude]', false, true);
        $longitude = $request->get('geolocation[longitude]', false, true);
        $address = $request->get('geolocation[address]', false, true);
        $altitude = $request->get('geolocation[altitude]', false, true);

        if ($latitude && $longitude && $address) {
            $geolocation = $publication->getGeolocation();

            $geolocation->setLatitude($latitude)
                ->setLongitude($longitude)
                ->setAddress($address)
                ->setAltitude($altitude);

            $em = $this->getDoctrine()->getManager();

            $locality = $this->getGeolocationService()->findACity($geolocation);
            if ($locality instanceof Locality) {
                $publication->setLocality($locality);

                $em->persist($publication);
            }

            $em->persist($geolocation);
            $em->flush();

            return $this->view($this->success(), Codes::HTTP_OK);
        }

        throw new HttpException(Codes::HTTP_BAD_REQUEST, $this->message('errors.publication.geolocation'));
    }

    /**
     * Edition d'un commentaire par son auteur
     *
     * PUT /publications/{comment}/comment
     *
     * JSON
     * {
     *      "content": "version modifiée"
     * }
     *
     *
     * @param \NaturaPass\PublicationBundle\Entity\PublicationComment $comment
     * @param Request $request
     *
     * @ParamConverter("comment", class="NaturaPassPublicationBundle:PublicationComment")
     *
     * @return \FOS\RestBundle\View\View
     * @throws HttpException
     */
    public function putPublicationCommentAction(PublicationComment $comment, Request $request)
    {
        $this->authorize($comment->getOwner());
        $this->authorizePublication($comment->getPublication());

        if ($content = $request->request->get('content', false)) {

            $comment->setContent(SecurityUtilities::sanitize($content));

            $em = $this->getDoctrine()->getManager();

            $em->persist($comment);
            $em->flush();

            return $this->view($this->success(), Codes::HTTP_OK);
        }

        throw new HttpException(Codes::HTTP_BAD_REQUEST, $this->message('errors.publication.empty.comment'));
    }

    /**
     * Ajoute un like utilisateur sur une publication
     *
     * PUT /publications/{publication}/like
     *
     *
     * @param \NaturaPass\PublicationBundle\Entity\Publication
     *
     * @ParamConverter("publication", class="NaturaPassPublicationBundle:Publication")
     *
     * @return \FOS\RestBundle\View\View
     */
    public function putPublicationLikeAction(Publication $publication)
    {
        $this->authorize();
        $this->authorizePublication($publication);

        $manager = $this->getDoctrine()->getManager();

        $like = $manager->getRepository('NaturaPassPublicationBundle:PublicationAction')->findOneBy(
            array(
                'user' => $this->getUser(),
                'publication' => $publication
            )
        );

        if (!$like) {
            $like = new PublicationAction;
            $like->setPublication($publication)
                ->setUser($this->getUser());
        }

        $like->setState(PublicationAction::STATE_LIKE);

        $manager->persist($like);
        $manager->flush();


        if ($this->getUser() != $publication->getOwner()) {
            $this->getNotificationService()->queue(
                new PublicationLikedNotification($publication), $publication->getOwner()
            );

//            $this->delay(function () use ($publication) {
//                $this->getGraphService()->generateEdge($this->getUser(), $publication->getOwner(), Edge::PUBLICATION_LIKED, $publication->getId());
//            });
        }

        return $this->view(
            array(
                'likes' => $publication->getActions(PublicationAction::STATE_LIKE)->count(),
                'unlikes' => $publication->getActions(PublicationAction::STATE_UNLIKE)->count(),
            ), Codes::HTTP_OK
        );
    }

    /**
     * Ajoute une publication utilisateur
     *
     * POST /publications
     *
     * Content-Type: Form-Data (pour média) ou JSON (sans média)
     *      publication[content] = "Aujourd'hui j'ai tué 5 lapins"
     *      publication[legend] = "Legend"
     *      publication[date] = "2014-07-16+16:00"
     *
     *      publication[sharing][share] = 3,
     *      publication[sharing][withouts] = [17, 18] // Identifiants d'utilisateurs non autorisés à voir la publication
     *
     *      publication[groups] = [1, 2, 3]
     *      publication[hunts] = [1, 2, 3]
     *
     *      publication[geolocation][latitude] = "45.75",
     *      publication[geolocation][longitude] = "4.85",
     *      publication[geolocation][address] = "Adresse au format Google"
     *
     *      publication[media][legend] = "Un des lapins en question"
     *      publication[media][tags] = ["Chasse", "Natura"]
     *      publication[media][file] = Données de fichier
     *
     *      publication[landmark] = true or false
     *
     * L'identifiant utilisateur sera ajouté automatiquement par celui actuellement connecté avec cette session
     *
     *
     * @param Request $request
     * @return \FOS\RestBundle\View\View
     *
     * @View(serializerGroups={"MediaDetail", "TagLess", "SharingLess", "SharingWithouts", "UserLess", "GeolocationLess", "PublicationGroup", "GroupLess"})
     */
    public function postPublicationAction(Request $request)
    {
        $this->authorize();

//        if (!$request->request->get('publication[_token]', false, true)) {
//            $params = array('publication' => $request->request->get('publication'));
//            $params['publication']['_token'] = $this->get('form.csrf_provider')->generateCsrfToken('publication');
//            $request->request->replace($params);
//        }

        $params = $request->request->get('publication');

        if (!$request->files->has('publication[media][file]', false, true) && $this->get('session')->has(
                'upload_handler/publication.upload'
            ) && isset($params['media']['legend'], $params['media']['tags'])
        ) {
            $file = new File($this->get('session')->get(
                'upload_handler/publication.upload'
            ));

            $params = array('publication' => array('media' => array('file' => $file)));
            $request->files->replace($params);
        }

        $form = $this->createForm(new PublicationFormType($this->getSecurityTokenStorage(), $this->container), new Publication());
        $handler = new PublicationHandler($form, $request, $this->getDoctrine()->getManager(), $this->getSecurityTokenStorage(), $this->getGeolocationService());

        if ($publication = $handler->process()) {
            $this->get('session')->remove('upload_handler/publication.upload');

            return $this->view(
                array(
                    'publication' => $this->getFormatPublicationComment($publication)
                ), Codes::HTTP_CREATED
            );
        }

        return $this->view($form->getErrors(true, false), Codes::HTTP_BAD_REQUEST);
    }

    /**
     * Reporte une publication (disparait pour l'utilisateur connecté)
     *
     * POST /publications/{publication}/signals
     *
     * Content-Type: Form-Data (pour média) ou JSON (sans média)
     *      publication[explanation] = "C'est pas joli"
     *
     *
     *
     * @param Request $request
     * @param \NaturaPass\PublicationBundle\Entity\Publication $publication
     *
     * @ParamConverter("publication", class="NaturaPassPublicationBundle:Publication")
     * @View(serializerGroups={"MediaDetail", "TagLess", "SharingLess"})
     *
     * @return \FOS\RestBundle\View\View
     * @throws HttpException
     */
    public function postPublicationSignalAction(Request $request, Publication $publication)
    {
        $this->authorize();
        $this->authorizePublication($publication);

        if (!$publication->isReported($this->getUser()) && $publication->getOwner()->getId() !== $this->getUser()->getId()) {
            $report = new PublicationReport();

            $report->setUser($this->getUser())
                ->setPublication($publication)
                ->setExplanation($request->request->get('publication[explanation]', '', true));

            $em = $this->getDoctrine()->getManager();

            $em->persist($report);
            $em->flush();

            $message = \Swift_Message::newInstance()
                ->setContentType("text/html")
                ->setSubject($this->get('translator')->trans('publication.report.subject', array(), $this->container->getParameter("translation_name") . 'email'))
                ->setFrom($this->getUser()->getEmail())
                ->setTo($this->container->getParameter("email_to"))
                ->addBcc($this->container->getParameter("email_bcc"))
                ->setBody(
                    $this->get('templating')->render(
                        'NaturaPassEmailBundle:Publication:report-publication.html.twig', array(
                            'fullname' => $report->getUser()->getFullName(),
                            'publication' => $report->getPublication(),
                            'explanation' => $report->getExplanation()
                        )
                    )
                );
            $this->get('mailer')->send($message);

            return $this->view($this->success(), Codes::HTTP_OK);
        }

        throw new HttpException(Codes::HTTP_BAD_REQUEST, $this->message('errors.publication.signal'));
    }

    /**
     * Ajoute un commentaire sur une publication
     *
     * POST /publications/{publication}/comments
     *
     * Les données doivent être formatées sous la forme
     * {
     *      "content": "Wahou quel beau lapin :)"
     * }
     *
     *
     * @param Request $request
     * @param \NaturaPass\PublicationBundle\Entity\Publication
     *
     * @ParamConverter("publication", class="NaturaPassPublicationBundle:Publication")
     *
     * @return \FOS\RestBundle\View\View
     * @throws HttpException
     */
    public function postPublicationCommentAction(Request $request, Publication $publication)
    {
        $this->authorize();
        $this->authorizePublication($publication);

        $content = $request->request->get('content', '');

        if (strlen($content) === 0) {
            throw new HttpException(Codes::HTTP_BAD_REQUEST, $this->message('errors.publication.empty.comment'));
        }

        $comment = new PublicationComment;

        $comment->setPublication($publication)
            ->setOwner($this->getUser())
            ->setContent(SecurityUtilities::sanitize($content));

        $manager = $this->getDoctrine()->getManager();

        $manager->persist($comment);
        $manager->flush();

        if ($this->getUser()->getId() != $publication->getOwner()->getId()) {

//            $this->delay(function () use ($publication) {
//                $this->getGraphService()->generateEdge($this->getUser(), $publication->getOwner(), Edge::PUBLICATION_COMMENTED, $publication->getId());
//            });

            $this->getNotificationService()->queue(
                new PublicationCommentedNotification($publication, $comment), $publication->getOwner()
            );

            $this->getEmailService()->generate(
                'publication.comment', array(), array($publication->getOwner()), 'NaturaPassEmailBundle:Publication:comment-email.html.twig', array('fullname' => $this->getUser()->getFullname(), 'comment' => $publication->getFirstWordLastComment(), 'publication' => $publication)
            );
        }

        $arrayOwnerss = $publication->getNLastOwnerComment();
        $arrayOwnerss->removeElement($this->getUser());
        $arrayOwnerss->removeElement($publication->getOwner());

        foreach ($arrayOwnerss->toArray() as $owner) {
            $this->getNotificationService()->queue(
                new PublicationSameCommentedNotification($publication, $comment), $owner
            );

            $this->getEmailService()->generate(
                'publication.same_comment', array(), array($owner), 'NaturaPassEmailBundle:Publication:same-comment-email.html.twig', array('fullname' => $this->getUser()->getFullname(), 'owner' => $publication->getOwner()->getFullName(), 'date' => $publication->getCreated()->format('d/m/Y'), 'comment' => $publication->getFirstWordLastComment(), 'publication' => $publication)
            );
        }

        return $this->view($this->getAddFormatComment($comment), Codes::HTTP_CREATED);
    }

    /**
     * Suppression d'une publication par son auteur
     *
     * DELETE /publications/{publication}
     *
     *
     * @param \NaturaPass\PublicationBundle\Entity\Publication $publication
     *
     * @ParamConverter("publication", class="NaturaPassPublicationBundle:Publication")
     *
     * @return \FOS\RestBundle\View\View
     */
    public function deletePublicationAction(Publication $publication)
    {
        $users = array($publication->getOwner());
        if ($publication->getOwner() != $this->getUser() && ($this->getUser()->hasRole("ROLE_ADMIN") || $this->getUser()->hasRole("ROLE_SUPER_ADMIN"))) {
            $users[] = $this->getUser();
        }
        $this->authorize($users);

        $em = $this->getDoctrine()->getManager();
        $id = $publication->getId();
//        $this->delay(function () use ($id) {
//            $this->getGraphService()->deleteEdgeByObject(Edge::PUBLICATION, $id);
//        });

        $publicationDeleted = new PublicationDeleted();
        $publicationDeleted->setId($publication->getId());
        if (!is_null($publication->getGeolocation())) {
            $geolocation = new Geolocation();
            $geolocation->setAddress($publication->getGeolocation()->getAddress());
            $geolocation->setAltitude($publication->getGeolocation()->getAltitude());
            $geolocation->setLatitude($publication->getGeolocation()->getLatitude());
            $geolocation->setLongitude($publication->getGeolocation()->getLongitude());
            $publicationDeleted->setGeolocation($geolocation);
        }

        $em->persist($publicationDeleted);
        $em->remove($publication);
        $em->flush();

        return $this->view($this->success(), Codes::HTTP_OK);
    }

    /**
     * Suppression d'un commentaire par son auteur
     *
     * DELETE /publications/{comment}/comment
     *
     *
     * @param \NaturaPass\PublicationBundle\Entity\PublicationComment $comment
     *
     * @ParamConverter("comment", class="NaturaPassPublicationBundle:PublicationComment")
     *
     * @return \FOS\RestBundle\View\View
     */
    public function deletePublicationCommentAction(PublicationComment $comment)
    {
        $this->authorize(array($comment->getOwner(), $comment->getPublication()->getOwner()));

        $em = $this->getDoctrine()->getManager();

//        $this->delay(function () use ($comment) {
//            $this->getGraphService()->deleteEdgeOf($this->getUser(), Edge::PUBLICATION_COMMENTED, $comment->getPublication()->getId());
//        });

        $em->remove($comment);
        $em->flush();

        return $this->view($this->success(), Codes::HTTP_OK);
    }

    /**
     * Supprime une action de type like/unlike utilisateur sur une publication
     *
     * DELETE /publications/{publication}/action
     *
     *
     * @param \NaturaPass\PublicationBundle\Entity\Publication
     *
     * @ParamConverter("publication", class="NaturaPassPublicationBundle:Publication")
     *
     * @return \FOS\RestBundle\View\View
     */
    public function deletePublicationActionAction(Publication $publication)
    {
        $this->authorize();
        $this->authorizePublication($publication);

        $manager = $this->getDoctrine()->getManager();

        $like = $manager->getRepository('NaturaPassPublicationBundle:PublicationAction')->findOneBy(
            array(
                'user' => $this->getUser(),
                'publication' => $publication
            )
        );
//        $this->delay(function () use ($publication) {
//            $this->getGraphService()->deleteEdgeOf($this->getUser(), Edge::PUBLICATION_LIKED, $publication->getId());
//        });

        if ($like) {
            $manager->remove($like);
            $manager->flush();
        }

        return $this->view(
            array(
                'likes' => $publication->getActions(PublicationAction::STATE_LIKE)->count(),
                'unlikes' => $publication->getActions(PublicationAction::STATE_UNLIKE)->count(),
            ), Codes::HTTP_OK
        );
    }

    /**
     * Supprime une action de type like/unlike utilisateur sur un commentaire de publication
     *
     * DELETE /publications/{comment}/comment/action
     *
     *
     * @param \NaturaPass\PublicationBundle\Entity\PublicationComment $comment
     *
     * @ParamConverter("comment", class="NaturaPassPublicationBundle:PublicationComment")
     *
     * @return \FOS\RestBundle\View\View
     */
    public function deletePublicationCommentActionAction(PublicationComment $comment)
    {
        $this->authorize();
        $this->authorizePublication($comment->getPublication());

        $manager = $this->getDoctrine()->getManager();

        $action = $manager->getRepository('NaturaPassPublicationBundle:PublicationCommentAction')->findOneBy(
            array(
                'user' => $this->getUser(),
                'comment' => $comment
            )
        );

        if ($action) {
            $manager->remove($action);
            $manager->flush();
        }

        return $this->view(
            array(
                'likes' => $comment->getActions(PublicationCommentAction::STATE_LIKE)->count(),
                'unlikes' => $comment->getActions(PublicationCommentAction::STATE_UNLIKE)->count()
            )
        );
    }

}
