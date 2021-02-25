<?php

namespace NaturaPass\UserBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class InvitationTaskType extends AbstractType {

    public function buildForm(FormBuilderInterface $builder, array $options) {
        $builder
                ->add('invitations', 'collection', array(
                    'type' => new InvitationFormType(),
                    'label' => false,
                    'allow_add' => true,
                    'allow_delete' => true,
                    'by_reference' => false,
                ));
    }

    public function configureOptions(OptionsResolver $resolver) {
        $resolver->setDefaults(array(
            'data_class' => 'NaturaPass\UserBundle\Entity\InvitationTask',
            'cascade_validation' => true,
                // 'intention' => 'invitation'
        ));
    }

    public function getName() {
        return 'invitation_task';
    }

}
