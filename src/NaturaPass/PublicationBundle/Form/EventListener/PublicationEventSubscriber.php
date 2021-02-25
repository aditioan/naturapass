<?php
/**
 * Created by PhpStorm.
 * User: vincentvalot
 * Date: 03/09/14
 * Time: 12:01
 */

namespace NaturaPass\PublicationBundle\Form\EventListener;


use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

class PublicationEventSubscriber implements EventSubscriberInterface {

    protected $securityContext;

    public function __construct(TokenStorageInterface $securityContext) {
        $this->securityContext = $securityContext;
    }

    public static function getSubscribedEvents()
    {
        // Tells the dispatcher that you want to listen on the form.pre_set_data
        // event and that the preSetData method should be called.
        return array(
            FormEvents::SUBMIT => 'submit',
        );
    }

    public function submit(FormEvent $event) {
        $publication = $event->getForm()->getData();

        if ($publication->getId() === null) {
            $publication->setOwner($this->securityContext->getToken()->getUser());
        }

        $event->setData($publication);
    }
}