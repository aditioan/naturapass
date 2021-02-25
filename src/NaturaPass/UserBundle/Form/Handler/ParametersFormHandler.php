<?php

namespace NaturaPass\UserBundle\Form\Handler;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Form\Form;

/**
 * Description of ParametersHandler
 *
 * @author vincentvalot
 */
class ParametersFormHandler {

    protected $request;
    protected $form;
    protected $manager;

    public function __construct(Form $form, Request $request, EntityManagerInterface $manager) {
        $this->request = $request;
        $this->form = $form;
        $this->manager = $manager;
    }

    public function process() {
        if ($this->request->getMethod() === 'POST' || $this->request->getMethod() === 'PUT') {
            $this->form->handleRequest($this->request);

            if ($this->form->isValid()) {
                return $this->onSuccess($this->form->getData());
            }
        }

        return false;
    }

    public function onSuccess(\NaturaPass\UserBundle\Entity\User $user) {

        $parameters = $user->getParameters();

        $repo = $this->manager->getRepository('NaturaPassUserBundle:User');
        if ($publicationSharingForm = $this->form->get('publication_sharing')) {
            if ($withouts = $publicationSharingForm->get('withouts')->getData()) {
                if (!is_array($withouts)) {
                    $withouts = explode(',', $withouts);
                }
                foreach ($withouts as $id) {
                    $user = $repo->find($id);
                    if ($user) {
                        $parameters->getPublicationSharing()->addWithout($user);
                    }
                }
            }
        }

        $this->manager->persist($parameters);
        $this->manager->flush();

        return $parameters;
    }

}
