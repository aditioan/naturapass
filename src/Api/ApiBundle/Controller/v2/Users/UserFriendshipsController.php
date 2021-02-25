<?php

/**
 * Created by PhpStorm.
 * User: vincentvalot
 * Date: 29/07/15
 * Time: 09:11
 */

namespace Api\ApiBundle\Controller\v2\Users;

use Api\ApiBundle\Controller\v2\ApiRestController;
use Api\ApiBundle\Controller\v2\Serialization\UserSerialization;
use Doctrine\ORM\QueryBuilder;
use FOS\RestBundle\Util\Codes;
use NaturaPass\GraphBundle\Entity\Edge;
use NaturaPass\GraphBundle\Repository\RecommendationRepository;
use NaturaPass\NotificationBundle\Entity\User\UserFriendshipAskedNotification;
use NaturaPass\NotificationBundle\Entity\User\UserFriendshipConfirmedNotification;
use NaturaPass\UserBundle\Entity\User;
use NaturaPass\UserBundle\Entity\UserFriend;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;

class UserFriendshipsController extends ApiRestController {

    /**
     * Retourne les amis d'un utilisateur
     *
     * GET /v2/users/{user}/friends?mutual=1&sort[field]=id/fullname/firstname/lastname&sort[order]=asc/desc
     *
     * @param Request $request
     * @param \NaturaPass\UserBundle\Entity\User $user
     *
     * @return \FOS\RestBundle\View\View
     *
     * @ParamConverter("user", class="NaturaPassUserBundle:User")
     */
    public function getFriendsAction(Request $request, User $user) {
        $this->authorize();

        if ($request->query->get('mutual')) {
            $friends = $user->getMutualFriendsWith($this->getUser());
        } else {
            if ($this->getUser() != $user && !$user->getParameters()->getFriends()) {
                throw new HttpException(Codes::HTTP_BAD_REQUEST, $this->message('errors.user.no_friend_sharing'));
            }

            $friends = $user->getFriends();
        }
        $friends=UserSerialization::serializeUsers($friends, $this->getUser());
        if ($request->query->get('sort'))
        {
            $sort=$request->query->get('sort');
            if (isset($sort['field']))
            {
                $sortedFriends=array();
                $aValues=array();
                foreach ($friends as $key=>$friend)
                 {
                    $aValues[$key]=$friend[$request->query->get('sort')['field']];
                 }
                asort($aValues); 
                foreach ($aValues as $key=>$value)
                {
                   $sortedFriends[]=$friends[$key];
                }
                
                 if (isset($sort['order']) && ($sort['order']=='desc'))
                {
                     $sortedFriends=array_reverse($sortedFriends);
                }
                $friends=$sortedFriends;
            }
        }
        return $this->view(
                        array(
                    'friends' => $friends,
                        ), Codes::HTTP_OK
        );
    }

    /**
     * Add a friendship between two users
     *
     * POST /v2/users/{receiver}/friendships
     *
     * @param \NaturaPass\UserBundle\Entity\User $receiver
     * @return \FOS\RestBundle\View\View
     * @throws HttpException
     *
     * @ParamConverter("receiver", class="NaturaPassUserBundle:User")
     */
    public function postFriendshipAction(User $receiver) {
        $this->authorize();

        if ($this->getUser() == $receiver) {
            throw new HttpException(Codes::HTTP_BAD_REQUEST, $this->message('errors.user.friendship.self'));
        }

        $manager = $this->getDoctrine()->getManager();

        /**
         * @var QueryBuilder $qb
         */
        $qb = $manager->getRepository('NaturaPassUserBundle:UserFriend')->createQueryBuilder('uf');

        $results = $qb->select('uf')
                ->where(
                        $qb->expr()->andX(
                                $qb->expr()->eq('uf.friend', $receiver->getId()), $qb->expr()->eq('uf.user', $this->getUser()->getId())
                        )
                )
                ->orWhere(
                        $qb->expr()->andX(
                                $qb->expr()->eq('uf.friend', $this->getUser()->getId()), $qb->expr()->eq('uf.user', $receiver->getId())
                        )
                )
                ->getQuery()
                ->getResult();

        $friendship = null;

        if (count($results) > 0) {
            $friendship = $results[0];
        }

        /**
         * If a friendship already exist and it has been rejected
         */
        if ($friendship instanceof UserFriend) {
            if ($friendship->getState() === UserFriend::REJECTED) {
                $friendship->setState(UserFriend::ASKED);
            } else {
                throw new HttpException(Codes:: HTTP_BAD_REQUEST, $this->message('errors.user.friendship.already'));
            }
        } else {
            $friendship = new UserFriend();
            $friendship->setUser($this->getUser())
                    ->setFriend($receiver)
                    ->setState(UserFriend::ASKED);
        }

        $manager->persist($friendship);
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

        $this->delay(
                function () use ($manager, $receiver) {
            /**
             * @var RecommendationRepository $repository
             */
            $repository = $manager->getRepository('NaturaPassGraphBundle:Recommendation');
            $repository->deleteRecommendationBetween($this->getUser(), $receiver);
        }
        );

        return $this->view(
                        array(
                    'relation' => UserSerialization::serializeUserRelation($receiver, $this->getUser())
                        ), Codes::HTTP_OK
        );
    }

    /**
     * Confirm a friendship originally sent by the sender
     *
     * PUT /users/{sender}/friendship
     *
     * @param \NaturaPass\UserBundle\Entity\User $sender
     * @return \FOS\RestBundle\View\View
     * @throws HttpException
     *
     * @ParamConverter("sender", class="NaturaPassUserBundle:User")
     */
    public function putFriendshipAction(User $sender) {
        $this->authorize();

        /**
         * @var QueryBuilder $qb
         */
        $qb = $this->getDoctrine()->getManager()
                ->getRepository('NaturaPassUserBundle:UserFriend')
                ->createQueryBuilder('uf');

        $results = $qb->select('uf')
                ->where(
                        $qb->expr()->andX(
                                $qb->expr()->eq('uf.friend', $this->getUser()->getId()), $qb->expr()->eq('uf.user', $sender->getId())
                        )
                )
                ->getQuery()
                ->getResult();

        $friendship = null;

        if (count($results) > 0) {
            $friendship = $results[0];
        }

        if ($friendship instanceof UserFriend) {
            $manager = $this->getDoctrine()->getManager();

            $friendship->setState(UserFriend::CONFIRMED);

            $manager->persist($friendship);
            $manager->flush();

            $this->delay(
                    function () use ($manager, $sender) {
                /**
                 * @var RecommendationRepository $repository
                 */
                $repository = $manager->getRepository('NaturaPassGraphBundle:Recommendation');
                $repository->deleteRecommendationBetween($this->getUser(), $sender);

                $this->getGraphService()->generateEdge($this->getUser(), $sender, Edge::FRIENDSHIP_FRIEND);
            }
            );

            $this->getNotificationService()->queue(
                    new UserFriendshipConfirmedNotification($sender), $sender
            );

            return $this->view(
                            array(
                        'relation' => UserSerialization::serializeUserRelation($sender, $this->getUser())
                            ), Codes::HTTP_OK
            );
        }

        throw new HttpException(Codes:: HTTP_BAD_REQUEST, $this->message('errors.user.friendship.nowaiting'));
    }

    /**
     * Remove a friendship between two users.
     * If the friendship was in asked state, it will not be removed but his state will be changed to REJECTED
     *
     * DELETE /v2/users/{receiver}/friendship
     *
     * @param \NaturaPass\UserBundle\Entity\User $receiver
     * @return \FOS\RestBundle\View\View
     * @throws HttpException
     *
     * @ParamConverter("receiver", class="NaturaPassUserBundle:User")
     */
    public function deleteFriendshipAction(User $receiver) {
        $this->authorize();

        $manager = $this->getDoctrine()->getManager();

        /**
         * @var QueryBuilder $qb
         */
        $qb = $manager->getRepository('NaturaPassUserBundle:UserFriend')->createQueryBuilder('uf');

        $results = $qb->select('uf')
                ->where(
                        $qb->expr()->andX(
                                $qb->expr()->eq('uf.friend', $receiver->getId()), $qb->expr()->eq('uf.user', $this->getUser()->getId())
                        )
                )
                ->orWhere(
                        $qb->expr()->andX(
                                $qb->expr()->eq('uf.friend', $this->getUser()->getId()), $qb->expr()->eq('uf.user', $receiver->getId())
                        )
                )
                ->getQuery()
                ->getResult();

        $friendship = null;

        if (count($results) > 0) {
            $friendship = $results[0];
        }

        if ($friendship instanceof UserFriend) {
            $this->delay(function() use ($receiver) {
                $this->getGraphService()->deleteEdgeBetween($this->getUser(), $receiver, Edge::FRIENDSHIP_FRIEND);
            });

            if ($friendship->getState() === UserFriend::ASKED) {
                $friendship->setState(UserFriend::REJECTED);

                $manager->persist($friendship);
                $manager->flush();

                return $this->view(
                                array(
                            'relation' => UserSerialization::serializeUserRelation($receiver, $this->getUser())
                                ), Codes::HTTP_OK);
            }

            $manager->remove($friendship);
            $manager->flush();

            return $this->view($this->success(), Codes::HTTP_OK);
        }

        throw new HttpException(Codes:: HTTP_BAD_REQUEST, $this->message('errors.user.friendship.nonexistent'));
    }

}
