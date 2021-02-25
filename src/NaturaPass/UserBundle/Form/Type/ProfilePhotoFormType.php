<?php

namespace NaturaPass\UserBundle\Form\Type;

use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use FOS\UserBundle\Form\Type\RegistrationFormType as BaseType;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

class ProfilePhotoFormType extends BaseType
{

    protected $securityContext;
    protected $container;

    public function __construct(TokenStorageInterface $securityContext, $container)
    {
        $this->securityContext = $securityContext;
        $this->container = $container;

        parent::__construct('NaturaPass\UserBundle\Entity\User');
    }

    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('photo', new UserMediaType($this->securityContext), array(
                'translation_domain' => $this->container->getParameter('translation_name') . 'user',
                'mapped' => false,
                'required' => false
            ));
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => 'NaturaPass\UserBundle\Entity\User',
            'intention' => 'user_photo'
        ));
    }

    /**
     * @return string
     */
    public function getName()
    {
        return 'user_photo';
    }

}
