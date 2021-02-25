<?php

namespace Admin\PermissionBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Admin\SentinelleBundle\Entity\Zone;
use Admin\PermissionBundle\Form\Handler\PermissionHandler;
use Admin\PermissionBundle\Form\Type\PermissionType;

class PermissionController extends Controller
{

    public function listAction()
    {
        return $this->render('AdminPermissionBundle:Permission:angular.index.html.twig');
    }

}
