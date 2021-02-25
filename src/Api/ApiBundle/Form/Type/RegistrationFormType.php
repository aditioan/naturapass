<?php

namespace Api\ApiBundle\Form\Type;

use NaturaPass\UserBundle\Form\Type\UserMediaType;
use NaturaPass\UserBundle\Entity\User;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use FOS\UserBundle\Form\Type\RegistrationFormType as BaseType;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

class RegistrationFormType extends BaseType {

    protected $securityContext;

    public function __construct(TokenStorageInterface $securityContext) {
        $this->securityContext = $securityContext;

        parent::__construct('NaturaPass\UserBundle\Entity\User');
    }

    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options) {
        $builder->add('courtesy', 'choice', array(
                    'label' => 'user.attributes.courtesy',
                    'required' => true,
                    'choices' => array(User::COURTESY_UNDEFINED => 'user.courtesy.undefined', User::COURTESY_MISTER => 'user.courtesy.mister', User::COURTESY_MADAM => 'user.courtesy.madam'),
                    'choices_as_values' => false,
                ))
                ->add('photo', new UserMediaType($this->securityContext), array(
                    'mapped' => false,
                    'required' => false
                ))
                ->add('lastname', 'text', array(
                    'label' => 'user.attributes.firstname',
                    'required' => true
                ))
                ->add('firstname', 'text', array(
                    'label' => 'user.attributes.lastname',
                    'required' => true
                ))
                ->add('birthday', 'date', array(
                    'widget'=> 'single_text',
                    'format'=>'d/M/y',
                    'label' => 'user.attributes.birthday',
                    'required' => false
                ))
                ->add('email', 'email', array(
                    'label' => 'user.attributes.email'
                ))
                ->add('facebook_id', 'text', array(
                    'required' => false
                ))
                ->add('password', 'password', array(
        ));
    }

    public function configureOptions(OptionsResolver $resolver) {
        $resolver->setDefaults(array(
            'data_class' => 'NaturaPass\UserBundle\Entity\User',
            'intention' => 'registration'
        ));
    }

    /**
     * @return string
     */
    public function getName() {
        return 'user';
    }

}
