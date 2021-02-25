<?php

namespace NaturaPass\UserBundle\Form\Handler;

class InvitationFormHandler
{

    protected $request;
    protected $form;
    protected $mailer;

    public function __construct($form, $request, $mailer)
    {
        $this->form = $form;
        $this->request = $request;
        $this->mailer = $mailer;
    }

    public function process($userEmail) {
       
        if ('POST' === $this->request->getMethod() || 'PUT' === $this->request->getMethod()) {
            $this->form->bind($this->request);

            if ($this->form->isValid()) {
                $data = $this->form->getData();

                return true;
            }
        }

        return false;
    }

    protected function onSuccess($data, $userEmail) {
    }

}