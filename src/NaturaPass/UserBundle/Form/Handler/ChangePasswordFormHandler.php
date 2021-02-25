<?php

namespace NaturaPass\UserBundle\Form\Handler;

use FOS\UserBundle\Model\UserManagerInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;

class ChangePasswordFormHandler extends \FOS\UserBundle\Form\Handler\ChangePasswordFormHandler {

    public function __construct(FormInterface $form, Request $request, UserManagerInterface $userManager) {
        parent::__construct($form, $request, $userManager);
    }
}