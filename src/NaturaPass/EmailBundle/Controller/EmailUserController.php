<?php

/**
 * Created by PhpStorm.
 * User: vincentvalot
 * Date: 18/07/14
 * Time: 14:25
 */

namespace NaturaPass\EmailBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use NaturaPass\UserBundle\Entity\Device;

class EmailUserController extends Controller {

    public function invitationAction($fullname, $email) {
        return $this->render('NaturaPassEmailBundle:User:invitation-email.html.twig', array(
                    'user_fullname' => $fullname,
                    'email' => $email
        ));
    }

    public function friendAction($user_fullname, $fullname, $user_tag) {
        return $this->render('NaturaPassEmailBundle:User:friend-email.html.twig', array(
                    'user_fullname' => $user_fullname,
                    'fullname' => $fullname,
                    'user_tag' => $user_tag
        ));
    }

    public function changePasswordAction($fullname, $token) {
        return $this->render('NaturaPassEmailBundle:User:change-password.html.twig', array(
                    'fullname' => $fullname,
                    'lien' => $token
        ));
    }

    /**
     * @param Device $device
     * @param $token
     * @return \Symfony\Component\HttpFoundation\Response
     *
     * @ParamConverter("device", class="NaturaPassUserBundle:Device")
     */
    public function verifyDeviceAction(Device $device, $token) {
        return $this->render('NaturaPassEmailBundle:User:verify-device.html.twig', array(
                    'userDevice' => $device->hasOwner($this->getUser()),
                    'device' => $device,
                    'token' => $token
        ));
    }

    public function confirmAction($fullname, $link) {
        return $this->render('NaturaPassEmailBundle:User:registration.html.twig', array(
                    'fullname' => $fullname,
                    'link' => $link
        ));
    }

    public function registerAction($fullname) {
        return $this->render('NaturaPassEmailBundle:User:register_api.html.twig', array(
                    'fullname' => $fullname
        ));
    }

}
