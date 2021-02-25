<?php

/**
 * Created by PhpStorm.
 * User: vincentvalot
 * Date: 18/07/14
 * Time: 12:27
 */

namespace NaturaPass\EmailBundle\Controller;

use NaturaPass\GroupBundle\Entity\Group;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;

class EmailGroupController extends Controller {

    /**
     * @param $group
     * @param $fullname
     * @param $message
     * @return \Symfony\Component\HttpFoundation\Response
     *
     * @ParamConverter("group", class="NaturaPassGroupBundle:Group")
     */
    public function inviteAction($group, $fullname, $message) {
        return $this->render('NaturaPassEmailBundle:Group:invite-email.html.twig', array(
                    'group' => $group,
                    'fullname' => $fullname,
                    'message' => $message
        ));
    }

    /**
     * @param $group
     * @param $fullname
     * @return \Symfony\Component\HttpFoundation\Response
     *
     * @ParamConverter("group", class="NaturaPassGroupBundle:Group")
     */
    public function inviteWithoutMessageAction($group, $fullname) {

        return $this->render('NaturaPassEmailBundle:Group:invite-email-without-message.html.twig', array(
                    'group' => $group,
                    'fullname' => $fullname
        ));
    }

    /**
     * @param $group
     * @param $senders
     *
     * @return \Symfony\Component\HttpFoundation\Response
     *
     * @ParamConverter("group", class="NaturaPassGroupBundle:Group")
     */
    public function publicationAddedAction($group, $senders) {

        return $this->render('NaturaPassEmailBundle:Group:publication-added.html.twig', array(
                    'group' => $group,
                    'senders' => $senders
        ));
    }

    /**
     * @param $group
     * @param $fullname
     * @return \Symfony\Component\HttpFoundation\Response
     *
     * @ParamConverter("group", class="NaturaPassGroupBundle:Group")
     */
    public function validInviteAction($group, $fullname) {

        return $this->render('NaturaPassEmailBundle:Group:valid-invite.html.twig', array(
            'group' => $group,
            'fullname' => $fullname
        ));
    }

    /**
     * @param Group $group
     * @param $fullname
     * @return \Symfony\Component\HttpFoundation\Response
     *
     * @ParamConverter("group", class="NaturaPassGroupBundle:Group")
     */
    public function accessValidatedAction(Group $group, $fullname) {

        return $this->render('NaturaPassEmailBundle:Group:access-validated.html.twig', array(
            'group' => $group,
            'fullname' => $fullname
        ));
    }

}
