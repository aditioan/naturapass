<?php

namespace NaturaPass\ObservationBundle\Form\Type;

use NaturaPass\MainBundle\Form\Type\GeolocationType;
use NaturaPass\PublicationBundle\Entity\Publication;
use NaturaPass\UserBundle\Entity\User;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;
use NaturaPass\LoungeBundle\Entity\Lounge;

class AttachmentType extends AbstractType {

    public function __construct() {
    }

    public function buildForm(FormBuilderInterface $builder, array $options) {
        $builder
            ->add('label', 'entity', array(
                'class' => 'AdminSentinelleBundle:CardLabel',
            ))
            ->add('value', 'text');
    }

    public function configureOptions(OptionsResolver $resolver) {
        $resolver->setDefaults(array(
            'data_class' => 'NaturaPass\ObservationBundle\Entity\Attachment'
        ));
    }

    public function getName() {
        return 'attachment';
    }

}
