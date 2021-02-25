<?php
/**
 * Created by PhpStorm.
 * User: vincentvalot
 * Date: 07/08/15
 * Time: 10:48
 */

namespace Api\ApiBundle\Controller\v2\Groups;

use Api\ApiBundle\Controller\v2\ApiRestController;
use Api\ApiBundle\Controller\v2\Serialization\GroupSerialization;
use FOS\RestBundle\Util\Codes;
use NaturaPass\GroupBundle\Entity\Group;
use NaturaPass\GroupBundle\Entity\GroupUser;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;

class GroupSubscribersController extends ApiRestController {

    /**
     * Return the group subscribers depending of the accesses passed by parameter
     * The default behaviour is to return all the subscribers
     *
     * GET /v2/groups/{group_id}/subscribers?accesses[]=2&accesses[]=3
     *
     * @param \NaturaPass\GroupBundle\Entity\Group $group
     * @param Request $request
     *
     * @return \FOS\RestBundle\View\View
     * @throws HttpException
     *
     * @ParamConverter("group", class="NaturaPassGroupBundle:Group")
     */
    public function getSubscribersAction(Request $request, Group $group) {
        $this->authorize();

        if ($group->getAccess() == Group::ACCESS_PROTECTED
            && !$group->isSubscriber(
                $this->getUser(),
                array(GroupUser::ACCESS_DEFAULT, GroupUser::ACCESS_ADMIN)
            )
        ) {
            throw new HttpException(Codes::HTTP_FORBIDDEN, $this->message('codes.403'));
        }

        $default = array(GroupUser::ACCESS_INVITED, GroupUser::ACCESS_RESTRICTED, GroupUser::ACCESS_DEFAULT, GroupUser::ACCESS_ADMIN);

        $accesses = $request->query->get('accesses', false);
        if (is_array($accesses)) {
            $default = $accesses;
        }

        return $this->view(
            array(
                'subscribers' => GroupSerialization::serializeGroupSubscribers(
                    $group->getSubscribers($default)->toArray(),
                    $this->getUser()
                )
            ),
            Codes::HTTP_OK
        );
    }
}