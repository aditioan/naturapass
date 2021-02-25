<?php

namespace NaturaPass\LoungeBundle\Form\Handler;

use Doctrine\ORM\EntityManagerInterface;
use NaturaPass\LoungeBundle\Entity\LoungeMedia;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Form\Form;
use NaturaPass\LoungeBundle\Entity\Lounge;
use NaturaPass\LoungeBundle\Entity\LoungeUser;

/**
 * Description of LoungeHandler
 *
 * @author vincentvalot
 */
class LoungeHandler {

    protected $request;
    protected $form;
    protected $manager;

    public function __construct(Form $form, Request $request, EntityManagerInterface $manager) {
        $this->request = $request;
        $this->form = $form;
        $this->manager = $manager;
    }

    /**
     * @return \NaturaPass\LoungeBundle\Entity\Lounge
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
     * @param \NaturaPass\LoungeBundle\Entity\Lounge $lounge
     * @return \NaturaPass\LoungeBundle\Entity\Lounge $lounge
     */
    public function onSuccess(Lounge $lounge) {
        $edit = $lounge->getId();

        if ($photo = $this->request->files->get('lounge[photo][file]', false, true)) {
            $media = new LoungeMedia();
            $media->setFile($photo);

            $this->manager->remove($lounge->getPhoto());

            $lounge->setPhoto($media);
        }
        if ($lounge->getGeolocation() === "on" || $lounge->getGeolocation() == 1) {
            $lounge->setGeolocation(1);
        } else {
            $lounge->setGeolocation(0);
        }

        $this->manager->persist($lounge);
        $this->manager->flush();

        if (is_null($edit) || $edit = "") {
            $loungeUser = new LoungeUser();
            $loungeUser->setUser($lounge->getOwner())
                    ->setLounge($lounge)
                    ->setAccess(LoungeUser::ACCESS_ADMIN)
                    ->setParticipation(LoungeUser::PARTICIPATION_YES);
            $this->manager->persist($loungeUser);
            $this->manager->flush();
        }

        return $lounge;
    }

}
