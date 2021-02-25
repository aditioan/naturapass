<?php

namespace NaturaPass\UserBundle\Form\Type;

use NaturaPass\UserBundle\Entity\User;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;
use FOS\UserBundle\Form\Type\RegistrationFormType as BaseType;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

class ProfileGeneralFormType extends BaseType
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
            ->add(
                'courtesy',
                'choice',
                array(
                    'translation_domain' => $this->container->getParameter('translation_name') . 'user',
                    'label' => 'user.attributes.courtesy',
                    'required' => false,
                    'expanded' => true,
                    'multiple' => false,
                    'choices' => array(
                        'user.courtesy.mister' => User::COURTESY_MISTER,
                        'user.courtesy.madam' => User::COURTESY_MADAM
                    ),
                    'choices_as_values' => true,
                    'choice_value' => function ($choice) {
                        return $choice;
                    },
                )
            )
            ->add(
                'lastname',
                'text',
                array(
                    'translation_domain' => $this->container->getParameter('translation_name') . 'user',
                    'label' => 'user.attributes.lastname',
                    'attr' => array('placeholder' => 'user.attributes.firstname'),
                    'required' => true
                )
            )
            ->add(
                'firstname',
                'text',
                array(
                    'translation_domain' => $this->container->getParameter('translation_name') . 'user',
                    'label' => 'user.attributes.firstname',
                    'attr' => array('placeholder' => 'user.attributes.lastname'),
                    'required' => true
                )
            )
            ->add(
                'email',
                'hidden',
                array(
                    'translation_domain' => $this->container->getParameter('translation_name') . 'user',
                    'label' => 'user.attributes.email',
                    'attr' => array('placeholder' => 'user.attributes.email'),
                    'data' => $this->securityContext->getToken()->getUser()->getEmail()
                )
            )
            ->add(
                'birthday',
                'birthday',
                array(
                    'translation_domain' => $this->container->getParameter('translation_name') . 'user',
                    "label" => 'user.attributes.birthday',
                    'format' => 'dd/MM/yyyy',
                    'required' => false,
                )
            );

        $user = $this->securityContext->getToken()->getUser();
        $builder->addEventListener(
            FormEvents::PRE_SET_DATA,
            function (FormEvent $event) use ($user) {
                $user = $event->getData();
                $form = $event->getForm();

                if ($user && $user->getId()) {
                    $form->remove('plainPassword');
                }
            }
        );
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(
            array(
                'data_class' => 'NaturaPass\UserBundle\Entity\User',
                'intention' => 'profile'
            )
        );
    }

    /**
     * @return string
     */
    public function getName()
    {
        return 'user';
    }

}
