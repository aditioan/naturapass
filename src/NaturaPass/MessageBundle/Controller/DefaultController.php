<?php

namespace NaturaPass\MessageBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;

class DefaultController extends Controller
{
    public function indexAction($name)
    {
        return $this->render('NaturaPassMessageBundle:Default:index.html.twig', array('name' => $name));
    }
}
