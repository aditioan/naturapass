<?php

namespace Admin\ZoneBundle\Form\Handler;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Form\Form;
use Admin\SentinelleBundle\Entity\Receiver;

/**
 * Description of ReceiverHandler
 *
 */
class ReceiverHandler {

    protected $request;
    protected $form;
    protected $manager;

    public function __construct(Form $form, Request $request, EntityManagerInterface $manager) {
        $this->request = $request;
        $this->form = $form;
        $this->manager = $manager;
    }

    /**
     * @return \Admin\SentinelleBundle\Entity\Receiver
     */
    public function process() {
        if ($this->request->getMethod() === 'POST' || $this->request->getMethod() === 'PUT') {
            $this->form->handleRequest($this->request);

            if ($this->form->isValid()) {
                return $this->onSuccess($this->form->getData());
            }
        }

        return false;
    }

    /**
     * @param \Admin\SentinelleBundle\Entity\Receiver $receiver
     * @return \Admin\SentinelleBundle\Entity\Receiver $receiver
     */
    public function onSuccess(Receiver $receiver) {

//        foreach ($receiver->getLocalities() as $locality) {
//            $locality->addReceiver($receiver);
//            $this->manager->persist($locality);
//        }
        $this->manager->persist($receiver);
        $this->manager->flush();

        return $receiver;
    }

}
