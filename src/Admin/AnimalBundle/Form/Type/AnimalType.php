<?php

namespace Admin\AnimalBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;

class AnimalType extends AbstractType
{

    protected $container;

    public function __construct($container)
    {
        $this->container = $container;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('name_fr', 'text', array(
            'translation_domain' => $this->container->getParameter('translation_name') . 'animal',
            'label' => 'animal.attributes.name',
            'attr' => array(
                'placeholder' => 'animal.placeholder.name'
            )
        ));
    }

    public function getName()
    {
        return 'animal';
    }

}
