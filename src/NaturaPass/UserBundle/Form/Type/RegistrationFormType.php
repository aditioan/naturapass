<?php

namespace NaturaPass\UserBundle\Form\Type;

use NaturaPass\UserBundle\Entity\User;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use FOS\UserBundle\Form\Type\RegistrationFormType as BaseType;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

class RegistrationFormType extends BaseType
{

    protected $securityContext;

    public function __construct(TokenStorageInterface $securityContext)
    {
        $this->securityContext = $securityContext;

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
                    'translation_domain' => 'fosuser',
                    'label' => 'user.attributes.courtesy',
                    'required' => true,
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
                    'preferred_choices' => array(User::COURTESY_MISTER)
                )
            )
            ->add(
                'photo',
                new UserMediaType($this->securityContext),
                array(
                    'translation_domain' => 'user',
                    'mapped' => false,
                    'required' => false
                )
            )
            ->add(
                'lastname',
                'text',
                array(
                    'translation_domain' => 'fosuser',
                    'label' => false,
                    'attr' => array('placeholder' => 'user.attributes.lastname'),
                    'required' => true
                )
            )
            ->add(
                'firstname',
                'text',
                array(
                    'translation_domain' => 'fosuser',
                    'label' => false,
                    'attr' => array('placeholder' => 'user.attributes.firstname'),
                    'required' => true
                )
            )
            ->add(
                'birthday',
                'birthday',
                array(
                    'translation_domain' => 'fosuser',
                    "label" => 'user.attributes.birthday',
                    'format' => 'dd/MM/yyyy',
                    'placeholder' => " "
                )
            )
            ->add(
                'email',
                'email',
                array(
                    'translation_domain' => 'fosuser',
                    'label' => false,
                    'attr' => array('placeholder' => 'user.attributes.email')
                )
            )
            ->add(
                'plainPassword',
                'repeated',
                array(
                    'translation_domain' => 'fosuser',
                    'type' => 'password',
                    'first_options' => array('attr' => array('placeholder' => 'user.attributes.password')),
                    'second_options' => array('attr' => array('placeholder' => 'user.attributes.passverif')),
                    'invalid_message' => 'fos_user.password.mismatch',
                    'options' => array('required' => true, 'label' => false),
                )
            );

        if ($this->securityContext->getToken()->getUser() instanceof User) {
            $builder->get("courtesy")->setData(array(User::COURTESY_MISTER));
        }

        $user = $this->securityContext->getToken()->getUser();
        $builder->addEventListener(
            \Symfony\Component\Form\FormEvents::PRE_SET_DATA,
            function (\Symfony\Component\Form\FormEvent $event) use ($user) {
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
                'intention' => 'registration'
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
