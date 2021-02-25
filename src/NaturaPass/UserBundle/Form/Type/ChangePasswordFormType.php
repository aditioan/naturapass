<?php

namespace NaturaPass\UserBundle\Form\Type;

use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

class ChangePasswordFormType extends \FOS\UserBundle\Form\Type\ChangePasswordFormType {

    protected $securityContext;

    public function __construct(TokenStorageInterface $securityContext) {
        $this->securityContext = $securityContext;
    }

    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options) {
        parent::buildForm($builder, $options);
    }

    public function configureOptions(OptionsResolver $resolver) {
        parent::configureOptions($resolver);
    }

    /**
     * @return string
     */
    public function getName() {
        return 'change_password';
    }

}
