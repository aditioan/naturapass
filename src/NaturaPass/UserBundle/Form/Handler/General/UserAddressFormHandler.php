<?php
/**
 * Created by PhpStorm.
 * User: vincentvalot
 * Date: 01/09/14
 * Time: 16:57
 */

namespace NaturaPass\UserBundle\Form\Handler\General;


use Doctrine\ORM\EntityManagerInterface;
use NaturaPass\UserBundle\Entity\UserAddress;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;

class UserAddressFormHandler {

    protected $request;
    protected $form;
    protected $manager;

    public function __construct(FormInterface $form, Request $request, EntityManagerInterface $manager) {
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

    public function onSuccess(UserAddress $address) {
        if ($address->isFavorite()) {
            $addresses = $address->getOwner()->getAddresses();
            foreach ($addresses as $address) {
                if ($address->isFavorite()) {
                    $address->setFavorite(false);
                    $this->manager->persist($address);
                }
            }
        }

        $this->manager->persist($address);
        $this->manager->flush();

        return $address;
    }

} 