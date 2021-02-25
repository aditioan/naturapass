<?php

namespace NaturaPass\LoungeBundle\Form\Type;

use NaturaPass\MediaBundle\Form\Type\BaseMediaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Description of LoungeMediaType
 *
 * @author vincentvalot
 */
class LoungeMediaType extends BaseMediaType {

    public function buildForm(FormBuilderInterface $builder, array $options) {
        parent::buildForm($builder, $options);
        $builder->remove('name');
    }

    public function configureOptions(OptionsResolver $resolver) {
        $resolver->setDefaults(array(
            'data_class' => 'NaturaPass\LoungeBundle\Entity\LoungeMedia',
            'intention' => 'loungemedia'
        ));
    }

    public function getName() {
        return 'loungemedia';
    }

}
