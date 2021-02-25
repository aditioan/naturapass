<?php

namespace Admin\DistributorBundle\Form\Type;

use NaturaPass\MediaBundle\Form\Type\BaseMediaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Description of DistributorMediaType
 *
 */
class DistributorMediaType extends BaseMediaType {

    public function buildForm(FormBuilderInterface $builder, array $options) {
        parent::buildForm($builder, $options);
        $builder->remove('name');
    }

    public function configureOptions(OptionsResolver $resolver) {
        $resolver->setDefaults(array(
            'data_class' => 'Admin\DistributorBundle\Entity\DistributorMedia',
            'intention' => 'distributormedia'
        ));
    }

    public function getName() {
        return 'distributormedia';
    }

}
