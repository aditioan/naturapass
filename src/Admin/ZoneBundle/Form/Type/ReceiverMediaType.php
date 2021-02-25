<?php

namespace Admin\ZoneBundle\Form\Type;

use NaturaPass\MediaBundle\Form\Type\BaseMediaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Description of ReceiverMediaType
 *
 */
class ReceiverMediaType extends BaseMediaType {

    public function buildForm(FormBuilderInterface $builder, array $options) {
        parent::buildForm($builder, $options);
        $builder->remove('name');
    }

    public function configureOptions(OptionsResolver $resolver) {
        $resolver->setDefaults(array(
            'data_class' => 'Admin\SentinelleBundle\Entity\ReceiverMedia',
            'intention' => 'receivermedia'
        ));
    }

    public function getName() {
        return 'receivermedia';
    }

}
