<?php

namespace NaturaPass\LoungeBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;

class LoungeUserType extends AbstractType {

    public function __construct() {

    }

    public function buildForm(FormBuilderInterface $builder, array $options) {
        $builder->add('name', 'text', array(
            'translation_domain' => 'lounge',
            'label' => 'lounge.attributes.name'
        ));

        parent::buildForm($builder, $options);
    }

    public function getName() {
        return 'naturapass_loungebundle_loungeusertype';
    }

}
