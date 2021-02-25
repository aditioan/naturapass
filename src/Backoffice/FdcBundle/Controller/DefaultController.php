<?php

namespace Backoffice\FdcBundle\Controller;

use Api\ApiBundle\Controller\v2\Serialization\ObservationSerialization;
use NaturaPass\PublicationBundle\Entity\PublicationMedia;
use NaturaPass\UserBundle\Entity\User;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;

class DefaultController extends Controller
{

    public function searchAction(Request $request)
    {
        $this->get('session')->remove('naturapass_backoffice/listing');
        return $this->render('BackofficeFdcBundle:Default:angular.search.html.twig', array());
    }

    public function listingAction(Request $request)
    {
        if ($request->getMethod() == 'POST') {
            $this->get('session')->set('naturapass_backoffice/listing', $request->request);
        }
        return $this->render('BackofficeFdcBundle:Default:angular.listing.html.twig', array());
    }

}
