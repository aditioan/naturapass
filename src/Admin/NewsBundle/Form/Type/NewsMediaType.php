<?php

namespace Admin\NewsBundle\Form\Type;

use NaturaPass\MediaBundle\Form\Type\BaseMediaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Description of BrandMediaType
 *
 */
class NewsMediaType extends BaseMediaType {

    public function buildForm(FormBuilderInterface $builder, array $options) {
        parent::buildForm($builder, $options);
        $builder->remove('name');
    }

    public function configureOptions(OptionsResolver $resolver) {
        $resolver->setDefaults(array(
            'data_class' => 'Admin\NewsBundle\Entity\NewsMedia',
            'intention' => 'newsmedia'
        ));
    }

    public function getName() {
        return 'newsmedia';
    }

}
