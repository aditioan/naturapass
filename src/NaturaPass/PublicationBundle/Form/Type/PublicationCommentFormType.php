<?php

namespace NaturaPass\PublicationBundle\Form\Type;

use NaturaPass\MainBundle\Form\Type\GeolocationType;
use NaturaPass\MainBundle\Form\Type\SharingType;
use NaturaPass\PublicationBundle\Form\EventListener\PublicationEventSubscriber;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

/**
 * Description of CommentFormType
 *
 * @author vincentvalot
 */
class PublicationCommentFormType extends AbstractType {

    public function buildForm(FormBuilderInterface $builder, array $options) {
        $builder->add('content', 'textarea');
    }

    public function configureOptions(OptionsResolver $resolver) {
        $resolver->setDefaults(array(
                'csrf_protection' => false,
                'data_class' => 'NaturaPass\PublicationBundle\Entity\PublicationComment',
            ));
    }

    public function getName() {
        return 'comment';
    }

}
