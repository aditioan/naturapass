<?php

namespace Admin\ZoneBundle\Form\Handler;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Form\Form;
use Admin\SentinelleBundle\Entity\Zone;

/**
 * Description of ZoneHandler
 *
 */
class ZoneHandler {

    protected $request;
    protected $form;
    protected $manager;

    public function __construct(Form $form, Request $request, EntityManagerInterface $manager) {
        $this->request = $request;
        $this->form = $form;
        $this->manager = $manager;
    }

    /**
     * @return \Admin\SentinelleBundle\Entity\Zone
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
     * @param \Admin\SentinelleBundle\Entity\Zone $zone
     * @return \Admin\SentinelleBundle\Entity\Zone $zone
     */
    public function onSuccess(Zone $zone) {

        foreach ($zone->getLocalities() as $locality) {
            $locality->setZone($zone);
            $this->manager->persist($locality);
        }
        $this->manager->persist($zone);
        $this->manager->flush();

        return $zone;
    }

}
