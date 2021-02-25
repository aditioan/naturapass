<?php

namespace NaturaPass\GroupBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Description of GroupEmailType
 *
 */
class GroupEmailType extends AbstractType
{

    public function __construct()
    {
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('type', 'text')
            ->add('wanted', 'integer');
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => 'NaturaPass\GroupBundle\Entity\GroupEmail'
        ));
    }

    public function getName()
    {
        return 'GroupEmail';
    }

}