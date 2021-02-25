<?php

namespace NaturaPass\PublicationBundle\Controller;

use NaturaPass\PublicationBundle\Form\Type\PublicationFormType;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use NaturaPass\MainBundle\Entity\Sharing;
use NaturaPass\UserBundle\Entity\UserFriend;

class DefaultController extends Controller
{

    public function indexAction()
    {
        $parameters = array(
            'mediaForm' => $this->createForm(new PublicationFormType($this->get('security.token_storage'), $this->container))->createView(),
            'publicationForm' => $this->createForm(new PublicationFormType($this->get('security.token_storage'), $this->container))->createView(),
            'editPublicationForm' => $this->createForm(new PublicationFormType($this->get('security.token_storage'), $this->container))->createView(),
            'editMediaPublicationForm' => $this->createForm(new PublicationFormType($this->get('security.token_storage'), $this->container, '_edit'))->createView(),
            'tmpUploadDir' => uniqid()
        );

        $this->get('session')->remove('upload_handler/publication.upload');

        return $this->render('NaturaPassPublicationBundle:Default:index.html.twig', $parameters);
    }

    /**
     * @param \NaturaPass\PublicationBundle\Entity\Publication $publication
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function showAction($publication)
    {
        return $this->render('NaturaPassPublicationBundle:Default:show.html.twig', array('publication' => $publication));
    }

}
