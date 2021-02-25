<?php

namespace NaturaPass\UserBundle\Form\Handler;

use Symfony\Component\Form\Form;
use Symfony\Component\HttpFoundation\Request;

class InvitationTaskHandler
{
    protected $request;
    protected $form;

    public function __construct(Form $form, Request $request)
    {
        $this->form = $form;
        $this->request = $request;
    }

  public function process()
  {
  }

  protected function onSuccess($data)
  {
  }
}