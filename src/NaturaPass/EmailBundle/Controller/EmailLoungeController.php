<?php

/**
 * Created by PhpStorm.
 * User: vincentvalot
 * Date: 18/07/14
 * Time: 12:51
 */

namespace NaturaPass\EmailBundle\Controller;

use NaturaPass\LoungeBundle\Entity\Lounge;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;

class EmailLoungeController extends Controller
{

    /**
     * @param $lounge
     * @param $fullname
     * @param $message
     * @return \Symfony\Component\HttpFoundation\Response
     *
     * @ParamConverter("lounge", class="NaturaPassLoungeBundle:Lounge")
     */
    public function inviteAction(Lounge $lounge, $fullname, $message)
    {

        return $this->render('NaturaPassEmailBundle:Lounge:invite-email.html.twig', array(
            'lounge' => $lounge,
            'fullname' => $fullname,
            'message' => $message
        ));
    }

    /**
     * @param $lounge
     * @param $fullname
     * @return \Symfony\Component\HttpFoundation\Response
     *
     * @ParamConverter("lounge", class="NaturaPassLoungeBundle:Lounge")
     */
    public function inviteWithoutMessageAction($lounge, $fullname)
    {

        return $this->render('NaturaPassEmailBundle:Lounge:invite-email-without-message.html.twig', array(
            'lounge' => $lounge,
            'fullname' => $fullname,
        ));
    }

    /**
     * @param $lounge
     * @param $fullname
     * @return \Symfony\Component\HttpFoundation\Response
     *
     * @ParamConverter("lounge", class="NaturaPassLoungeBundle:Lounge")
     */
    public function geolocateAction($lounge, $fullname)
    {

        return $this->render('NaturaPassEmailBundle:Lounge:geolocate-email.html.twig', array(
            'lounge' => $lounge,
            'fullname' => $fullname
        ));
    }

    /**
     * @param $lounge
     * @param $statut
     * @param $fullname
     * @return \Symfony\Component\HttpFoundation\Response
     *
     *
     * @ParamConverter("lounge", class="NaturaPassLoungeBundle:Lounge")
     */
    public function participateAction($lounge, $statut, $fullname)
    {

        return $this->render('NaturaPassEmailBundle:Lounge:participate-email.html.twig', array(
            'lounge' => $lounge,
            'statut' => $statut,
            'statutname' => $this->get('translator')->transChoice('lounge.state.participate.long', $statut, array(), 'lounge'),
            'fullname' => $fullname
        ));
    }

    /**
     * @param $lounge
     * @return \Symfony\Component\HttpFoundation\Response
     *
     * @ParamConverter("lounge", class="NaturaPassLoungeBundle:Lounge")
     */
    public function chatAction($lounge)
    {

        return $this->render('NaturaPassEmailBundle:Lounge:chat-email.html.twig', array(
            'lounge' => $lounge
        ));
    }

    /**
     * @param $lounge
     * @param $fullname
     * @return \Symfony\Component\HttpFoundation\Response
     *
     * @ParamConverter("lounge", class="NaturaPassLoungeBundle:Lounge")
     */
    public function validInviteAction($lounge, $fullname)
    {

        return $this->render('NaturaPassEmailBundle:Lounge:valid-invite.html.twig', array(
            'lounge' => $lounge,
            'fullname' => $fullname
        ));
    }

}
