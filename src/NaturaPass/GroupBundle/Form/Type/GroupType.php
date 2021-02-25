<?php

namespace NaturaPass\GroupBundle\Form\Type;

use NaturaPass\UserBundle\Entity\User;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;
use NaturaPass\GroupBundle\Entity\Group;

class GroupType extends AbstractType
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
        $builder->add('name', 'text', array(
            'translation_domain' => $this->container->getParameter('translation_name') . 'group',
            'label' => 'group.attributes.name',
            'attr' => array(
                'placeholder' => 'group.placeholder.name'
            )
        ))
            ->add('description', 'textarea', array(
                'translation_domain' => $this->container->getParameter('translation_name') . 'group',
                'label' => 'group.attributes.description',
                'attr' => array(
                    'placeholder' => 'group.placeholder.description'
                )
            ))
            ->add('access', 'choice', array(
                'translation_domain' => $this->container->getParameter('translation_name') . 'global',
                'label' => 'label.access.label',
                'choices' => array(
                    'label.access.access_list.protected' => Group::ACCESS_PROTECTED,
                    'label.access.access_list.semiprotected' => Group::ACCESS_SEMIPROTECTED,
                    'label.access.access_list.public' => Group::ACCESS_PUBLIC
                ),
                'choices_as_values' => true,
                'choice_value' => function ($choice) {
                    return $choice;
                },
                'expanded' => true
            ))
            ->add('allow_add', 'choice', array(
                'translation_domain' => $this->container->getParameter('translation_name') . 'group',
                'label' => 'label.allow_add',
                'choices' => array(
                    'label.all_members' => Group::ALLOW_ALL_MEMBERS,
                    'label.admin' => Group::ALLOW_ADMIN,
                ),
                'choices_as_values' => true,
                'choice_value' => function ($choice) {
                    return $choice;
                },
                'expanded' => true,
            ))
            ->add('allow_show', 'choice', array(
                'translation_domain' => $this->container->getParameter('translation_name') . 'group',
                'label' => 'label.allow_show',
                'choices' => array(
                    'label.all_members' => Group::ALLOW_ALL_MEMBERS,
                    'label.admin' => Group::ALLOW_ADMIN,
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
                    'label.all_members' => Group::ALLOW_ALL_MEMBERS,
                    'label.admin' => Group::ALLOW_ADMIN,
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
                    'label.all_members' => Group::ALLOW_ALL_MEMBERS,
                    'label.admin' => Group::ALLOW_ADMIN,
                ),
                'choices_as_values' => true,
                'choice_value' => function ($choice) {
                    return $choice;
                },
                'expanded' => true,
            ))
            ->add('photo', new GroupMediaType($this->container), array(
                'required' => false
            ))
            ->add('notifications', 'collection', array(
                'type' => new GroupNotificationType(),
                'allow_add' => true,
                'allow_delete' => true,
                'by_reference' => false,
                "required" => false
            ))
            ->add('emails', 'collection', array(
                'type' => new GroupEmailType(),
                'allow_add' => true,
                'allow_delete' => true,
                'by_reference' => false,
                "required" => false
            ));

        $user = $this->user;
        $builder->addEventListener(FormEvents::SUBMIT, function (FormEvent $event) use ($user) {
            $group = $event->getForm()->getData();

            if (!$group->getOwner()) {
                $group->setOwner($user);
            }

            if (is_null($group->getAllowAdd())) {
                $group->setAllowAdd(Group::ALLOW_ALL_MEMBERS);
            }
            if (is_null($group->getAllowShow())) {
                $group->setAllowShow(Group::ALLOW_ALL_MEMBERS);
            }

            if (is_null($group->getAllowAddChat())) {
                $group->setAllowAddChat(Group::ALLOW_ALL_MEMBERS);
            }
            if (is_null($group->getAllowShowChat())) {
                $group->setAllowShowChat(Group::ALLOW_ALL_MEMBERS);
            }

            $event->setData($group);
        });
    }

    public function getName()
    {
        return 'group';
    }

}
