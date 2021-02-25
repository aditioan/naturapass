<?php

/**
 * Created by PhpStorm.
 * User: vincentvalot
 * Date: 18/07/14
 * Time: 14:13
 */

namespace NaturaPass\EmailBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;

class EmailPublicationController extends Controller {

    /**
     * @param $publication
     * @param $fullname
     * @return \Symfony\Component\HttpFoundation\Response
     *
     * @ParamConverter("publication", class="NaturaPassPublicationBundle:Publication")
     */
    public function commentAction($publication, $fullname) {

        return $this->render('NaturaPassEmailBundle:Publication:comment-email.html.twig', array(
                    'publication' => $publication,
                    'fullname' => $fullname,
                    'comment' => $publication->getFirstWordLastComment()
        ));
    }

    /**
     * @param $publication
     * @param $fullname
     * @return \Symfony\Component\HttpFoundation\Response
     *
     * @ParamConverter("publication", class="NaturaPassPublicationBundle:Publication")
     */
    public function samecommentAction($publication, $fullname) {

        return $this->render('NaturaPassEmailBundle:Publication:same-comment-email.html.twig', array(
                    'publication' => $publication,
                    'fullname' => $fullname,
                    'comment' => $publication->getFirstWordLastComment(),
                    'owner' => $publication->getOwner()->getFullName(),
                    'date' => $publication->getCreated()->format('d/m/Y'),
        ));
    }

    /**
     * @param $publication
     * @param $fullname
     * @return \Symfony\Component\HttpFoundation\Response
     *
     * @ParamConverter("publication", class="NaturaPassPublicationBundle:Publication")
     */
    public function likeAction($publication, $fullname) {

        return $this->render('NaturaPassEmailBundle:Publication:like-email.html.twig', array(
                    'publication' => $publication,
                    'fullname' => $fullname
        ));
    }

}
