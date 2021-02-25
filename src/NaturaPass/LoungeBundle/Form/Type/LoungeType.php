<?php

namespace NaturaPass\LoungeBundle\Form\Type;

use NaturaPass\MainBundle\Form\Type\GeolocationType;
use NaturaPass\UserBundle\Entity\User;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use NaturaPass\LoungeBundle\Entity\Lounge;

class LoungeType extends AbstractType
{

    protected $user;
    protected $container;

    public function __construct(User $user, $container)
    {
        $this->user = $user;
        $this->container = $container;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add(
            'name',
            'text',
            array(
                'translation_domain' => $this->container->getParameter('translation_name') . 'lounge',
                'label' => 'lounge.attributes.name',
                'attr' => array(
                    'placeholder' => 'lounge.placeholder.name'
                )
            )
        )
            ->add(
                'description',
                'textarea',
                array(
                    'translation_domain' => $this->container->getParameter('translation_name') . 'lounge',
                    'label' => 'lounge.attributes.description',
                    'required' => false,
                    'attr' => array(
                        'placeholder' => 'lounge.placeholder.description'
                    )
                )
            )
            ->add(
                'geolocation',
                'hidden',
                array(
                    'translation_domain' => $this->container->getParameter('translation_name') . 'lounge',
                    'required' => false
                )
            )->add(
                'invitePersons',
                'hidden',
                array(
                    'required' => false
                )
            )->add(
                'groups',
                'hidden',
                array(
                    'required' => false,
                    'mapped' => false
                )
            )->add(
                'categories',
                'hidden',
                array(
                    'required' => false,
                    'mapped' => false
                )
            )->add(
                'access',
                'choice',
                array(
                    'translation_domain' => $this->container->getParameter('translation_name') . 'global',
                    'label' => 'label.access.label',
                    'choices' => array(
                        'label.access.access_list.protected' => Lounge::ACCESS_PROTECTED,
                        'label.access.access_list.semiprotected' => Lounge::ACCESS_SEMIPROTECTED,
                        'label.access.access_list.public' => Lounge::ACCESS_PUBLIC
                    ),
                    'choices_as_values' => true,
                    'choice_value' => function ($choice) {
                        return $choice;
                    },
                    'expanded' => true
                )
            )
            ->add(
                'meetingDate',
                'datetime',
                array(
                    'translation_domain' => $this->container->getParameter('translation_name') . 'lounge',
                    'label' => 'lounge.attributes.meetingDate',
                    'widget' => 'single_text',
                    'format' => 'dd/MM/yyyy HH:mm',
                    'required' => false,
                    'attr' => array(
                        'placeholder' => 'lounge.placeholder.meetingDate'
                    )
                )
            )
            ->add(
                'endDate',
                'datetime',
                array(
                    'translation_domain' => $this->container->getParameter('translation_name') . 'lounge',
                    'label' => 'lounge.attributes.endDate',
                    'widget' => 'single_text',
                    'format' => 'dd/MM/yyyy HH:mm',
                    'attr' => array(
                        'placeholder' => 'lounge.placeholder.endDate'
                    )
                )
            )
            ->add(
                'meetingAddress',
                new GeolocationType($this->container),
                array(
                    'label' => false,
                    'required' => false,
                    'attr' => array('class' => 'hide')
                )
            )
            ->add('allow_add', 'choice', array(
                'translation_domain' => $this->container->getParameter('translation_name') . 'lounge',
                'label' => 'label.allow_add',
                'choices' => array(
                    'label.all_members' => Lounge::ALLOW_ALL_MEMBERS,
                    'label.admin' => Lounge::ALLOW_ADMIN,
                ),
                'choices_as_values' => true,
                'choice_value' => function ($choice) {
                    return $choice;
                },
                'expanded' => true,
            ))
            ->add('allow_show', 'choice', array(
                'translation_domain' => $this->container->getParameter('translation_name') . 'lounge',
                'label' => 'label.allow_show',
                'choices' => array(
                    'label.all_members' => Lounge::ALLOW_ALL_MEMBERS,
                    'label.admin' => Lounge::ALLOW_ADMIN,
                ),
                'choices_as_values' => true,
                'choice_value' => function ($choice) {
                    return $choice;
                },
                'expanded' => true,
            ))
            ->add('allow_add_chat', 'choice', array(
                'translation_domain' => $this->container->getParameter('translation_name') . 'lounge',
                'label' => 'label.allow_add_chat',
                'choices' => array(
                    'label.all_members' => Lounge::ALLOW_ALL_MEMBERS,
                    'label.admin' => Lounge::ALLOW_ADMIN,
                ),
                'choices_as_values' => true,
                'choice_value' => function ($choice) {
                    return $choice;
                },
                'expanded' => true,
            ))
            ->add('allow_show_chat', 'choice', array(
                'translation_domain' => $this->container->getParameter('translation_name') . 'lounge',
                'label' => 'label.allow_show_chat',
                'choices' => array(
                    'label.all_members' => Lounge::ALLOW_ALL_MEMBERS,
                    'label.admin' => Lounge::ALLOW_ADMIN,
                ),
                'choices_as_values' => true,
                'choice_value' => function ($choice) {
                    return $choice;
                },
                'expanded' => true,
            ))
            ->add(
                'photo',
                new LoungeMediaType($this->container),
                array(
                    'required' => false
                )
            );

        $user = $this->user;
        $builder->addEventListener(
            \Symfony\Component\Form\FormEvents::SUBMIT,
            function (\Symfony\Component\Form\FormEvent $event) use ($user) {
                $lounge = $event->getForm()->getData();

                if (!$lounge->getOwner()) {
                    $lounge->setOwner($user);
                }
                if (is_null($lounge->getAllowAdd())) {
                    $lounge->setAllowAdd(Lounge::ALLOW_ALL_MEMBERS);
                }
                if (is_null($lounge->getAllowShow())) {
                    $lounge->setAllowShow(Lounge::ALLOW_ALL_MEMBERS);
                }
                if (is_null($lounge->getAllowAddChat())) {
                    $lounge->setAllowAddChat(Lounge::ALLOW_ALL_MEMBERS);
                }
                if (is_null($lounge->getAllowShowChat())) {
                    $lounge->setAllowShowChat(Lounge::ALLOW_ALL_MEMBERS);
                }

                $event->setData($lounge);
            }
        );
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(
            array(
                'data_class' => 'NaturaPass\LoungeBundle\Entity\Lounge'
            )
        );
    }

    public function getName()
    {
        return 'lounge';
    }

}
