<?php

namespace Admin\GraphBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;

class DefaultController extends Controller
{
    public function pertinencesAction()
    {
        return $this->render('AdminGraphBundle:Default:pertinences.html.twig');
    }
}
