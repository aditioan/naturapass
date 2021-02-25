<?php
/**
 * Created by PhpStorm.
 * User: vincentvalot
 * Date: 07/08/15
 * Time: 15:02
 */

namespace Api\ApiBundle\Controller\v2\Hunts;

use Api\ApiBundle\Controller\v2\ApiRestController;
use Api\ApiBundle\Controller\v2\Serialization\HuntSerialization;
use FOS\RestBundle\Util\Codes;
use NaturaPass\LoungeBundle\Entity\Lounge;
use NaturaPass\LoungeBundle\Entity\LoungeUser;
use NaturaPass\NotificationBundle\Entity\Lounge\LoungeStatusChangedNotification;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use NaturaPass\UserBundle\Entity\User;
use NaturaPass\NotificationBundle\Entity\Lounge\SocketOnly\LoungeSubscriberParticipationNotification;

class HuntSubscribersController extends ApiRestController
{

    /**
     * Return the hunt subscribers depending of the accesses passed by parameter
     * The default behaviour is to return all the subscribers and non members
     *
     * GET /v2/hunts/{hunt_id}/subscribers?accesses[]=2&accesses[]=3&nonmembers=0
     *
     * @param Request $request
     * @param \NaturaPass\LoungeBundle\Entity\Lounge $hunt
     *
     * @return \FOS\RestBundle\View\View
     * @throws HttpException
     *
     * @ParamConverter("hunt", class="NaturaPassLoungeBundle:Lounge")
     */
    public function getSubscribersAction(Request $request, Lounge $hunt)
    {
        $this->authorize();

        if ($hunt->getAccess() == Lounge::ACCESS_PROTECTED && !$hunt->isSubscriber(
                $this->getUser(),
                array(
                    LoungeUser::ACCESS_DEFAULT,
                    LoungeUser::ACCESS_ADMIN
                )
            )
        ) {
            throw new HttpException(Codes::HTTP_FORBIDDEN, $this->message('codes.403'));
        }

        $default = array(LoungeUser::ACCESS_INVITED, LoungeUser::ACCESS_RESTRICTED, LoungeUser::ACCESS_DEFAULT, LoungeUser::ACCESS_ADMIN);

        $accesses = $request->query->get('accesses', false);
        if (is_array($accesses)) {
            $default = $accesses;
        }

        $data = array(
            'subscribers' => HuntSerialization::serializeHuntSubscribers(
                $hunt->getSubscribers($default)->toArray(),
                $this->getUser()
            )
        );

        if ($request->query->get('nonmembers', true) != 0) {
            $data['non_members'] = HuntSerialization::serializeHuntNotMembers($hunt->getSubscribersNotMember()->toArray());
        }

        return $this->view($data, Codes::HTTP_OK);
    }

    /**
     * Mets à jour la participation + le commentaire publique d'un utilisateur à un salon
     *
     * PUT /v2/hunts/{HUNT_ID}/subscribers/{SUBSCRIBER_ID}/participationcomment
     *
     * JSON LIE
     * {
     *      "participation": [0 => Ne participe pas, 1 => Participe, 2 => Peut-être],
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
    public function putSubscribersParticipationcommentAction(Lounge $lounge, User $subscriber, Request $request)
    {
        $this->authorize(array_merge(array($subscriber), $lounge->getAdmins()->toArray()));
        if ($request->request->has('participation') && $request->request->has('content')) {
            if ($loungeUser = $lounge->isSubscriber($subscriber)) {
                $manager = $this->getDoctrine()->getManager();
                $loungeUser->setPublicComment($request->request->get('content'));
                $participation = $request->request->get('participation');
                $loungeUser->setParticipation($participation);
                if ($loungeUser->getParticipation() !== LoungeUser::PARTICIPATION_YES) {
                    $loungeUser->setGeolocation(false);
                }
                $manager->persist($loungeUser);
                $manager->flush();
                $receivers = array();
                foreach ($lounge->getAdmins() as $admin) {
                    if ($admin != $subscriber) {
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
}