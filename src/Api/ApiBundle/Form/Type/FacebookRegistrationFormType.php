<?php

namespace Api\ApiBundle\Form\Type;

use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

class FacebookRegistrationFormType extends RegistrationFormType
{

    public function __construct(TokenStorageInterface $securityContext, $container)
    {
        parent::__construct($securityContext, $container);
    }

    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        parent::buildForm($builder, $options);

        $builder->remove('password');
        $builder->remove('facebook_id');

        $builder->add('facebook_id', 'text', array(
            'required' => true
        ));
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        parent::configureOptions($resolver);
    }

}
