<?php

namespace NaturaPass\GroupBundle\Form\Type;

use NaturaPass\MediaBundle\Form\Type\BaseMediaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Description of GroupMediaType
 *
 * @author vincentvalot
 */
class GroupMediaType extends BaseMediaType {

    public function buildForm(FormBuilderInterface $builder, array $options) {
        parent::buildForm($builder, $options);
        $builder->remove('name');
    }

    public function configureOptions(OptionsResolver $resolver) {
        $resolver->setDefaults(array(
            'data_class' => 'NaturaPass\GroupBundle\Entity\GroupMedia',
            'intention' => 'groupmedia'
        ));
    }

    public function getName() {
        return 'groupmedia';
    }

}
