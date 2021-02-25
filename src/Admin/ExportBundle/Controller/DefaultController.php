<?php

namespace Admin\ExportBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;

class DefaultController extends Controller
{

    public function searchAction(Request $request)
    {
        $this->get('session')->remove('naturapass_admin/listing');
        return $this->render('AdminExportBundle:Default:angular.search.html.twig', array());
    }

    public function listingAction(Request $request)
    {
        if ($request->getMethod() == 'POST') {
            $this->get('session')->set('naturapass_admin/listing', $request->request);
        }
        return $this->render('AdminExportBundle:Default:angular.listing.html.twig', array());
    }

}
