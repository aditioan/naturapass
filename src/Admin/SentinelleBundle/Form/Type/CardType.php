<?php

namespace Admin\SentinelleBundle\Form\Type;

use Admin\SentinelleBundle\Entity\Card;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class CardType extends AbstractType
{

    protected $container;

    public function __construct($container)
    {
        $this->container = $container;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('name', 'text', array(
                'translation_domain' => $this->container->getParameter('translation_name') . 'card',
                'label' => 'card.attributes.name',
                'attr' => array(
                    'placeholder' => 'card.placeholder.name'
                )
            ))
            ->add('visible', 'choice', array(
                'choices' => array(
                    Card::VISIBLE_ON => 'Visible',
                    Card::VISIBLE_OFF => 'Non visible'
                ),
                'choices_as_values' => false,
                'empty_data' => Card::VISIBLE_ON,
                'required' => false
            ));
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => 'Admin\SentinelleBundle\Entity\Card'
        ));
    }

    public function getName()
    {
        return 'card';
    }

}
