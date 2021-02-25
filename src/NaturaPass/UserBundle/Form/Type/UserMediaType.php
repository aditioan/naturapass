<?php

namespace NaturaPass\UserBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

/**
 * Description of PublicationType
 *
 * @author vincentvalot
 */
class UserMediaType extends AbstractType {

    public function buildForm(FormBuilderInterface $builder, array $options) {
        $builder->add('file', 'file', array(
                    'required' => false
        ));
    }

    public function configureOptions(OptionsResolver $resolver) {
        parent::configureOptions($resolver);

        $resolver->setDefaults(array(
            'data_class' => 'NaturaPass\UserBundle\Entity\UserMedia',
            'intention' => 'usermedia'
        ));
    }

    public function getName() {
        return 'usermedia';
    }

}